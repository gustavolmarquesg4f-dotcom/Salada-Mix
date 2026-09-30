<?php

namespace App\Domain\Orders;

use App\Domain\Cart\CartManager;
use App\Domain\Shipping\HmlSandboxQuoteService;
use App\Models\User;
use App\Support\HmlDemo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class HmlSandboxOrderService
{
    public function __construct(
        private readonly CartManager $cart,
        private readonly HmlSandboxQuoteService $quotes
    ) {
    }

    public function create(User $buyer, string $idempotencyKey, array $selectedQuotes): string
    {
        HmlDemo::requireEnabled();
        $existingBeforeQuote = DB::table('demo_orders')->where('user_id', $buyer->id)
            ->where('idempotency_key', $idempotencyKey)->first();
        if ($existingBeforeQuote) {
            return $existingBeforeQuote->id;
        }

        $quoteSnapshot = $this->quotes->forBuyer($buyer);

        return DB::transaction(function () use ($buyer, $idempotencyKey, $selectedQuotes, $quoteSnapshot): string {
            User::query()->whereKey($buyer->id)->lockForUpdate()->firstOrFail();
            $existing = DB::table('demo_orders')->where('user_id', $buyer->id)
                ->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing->id;
            }

            $snapshot = $this->cart->read($buyer);
            if ($snapshot['items'] === [] || count($snapshot['items']) > 20 || collect($snapshot['items'])->contains(fn (array $line): bool => ! $line['available'])) {
                throw ValidationException::withMessages(['cart' => 'Revise a sacola antes de reservar o estoque DEMO.']);
            }
            if ($this->quotes->cartHash($snapshot, (string) $quoteSnapshot['address']->postal_code) !== $quoteSnapshot['cart_hash']) {
                throw ValidationException::withMessages(['shipping' => 'A sacola mudou; recalcule o frete sandbox.']);
            }

            $groups = collect($snapshot['items'])->groupBy(fn (array $line): string => $line['offer']['seller']['id']);
            $shippingRows = [];
            $shippingTotal = 0;

            foreach ($groups as $sellerId => $lines) {
                $quoteId = $selectedQuotes[$sellerId] ?? null;
                if (! is_string($quoteId)) {
                    throw ValidationException::withMessages(['shipping' => 'Selecione um frete sandbox para cada loja.']);
                }
                $quote = DB::table('demo_shipping_quotes')->where('id', $quoteId)
                    ->where('user_id', $buyer->id)->where('seller_id', $sellerId)
                    ->where('cart_hash', $quoteSnapshot['cart_hash'])->where('expires_at', '>', now())
                    ->first();
                if (! $quote) {
                    throw ValidationException::withMessages(['shipping' => 'Cotação expirada ou incompatível. Recalcule o frete sandbox.']);
                }
                $shippingTotal += (int) $quote->amount_cents;
                $shippingRows[$sellerId] = $quote;
            }

            $orderId = (string) Str::ulid();
            $now = now();
            $expiresAt = $now->copy()->addMinutes(15);
            $subtotal = 0;
            $items = [];

            foreach ($snapshot['items'] as $line) {
                $sellerId = $line['offer']['seller']['id'];
                $offer = DB::table('seller_offers')->join('products', 'products.id', '=', 'seller_offers.product_id')
                    ->join('sellers', 'sellers.id', '=', 'seller_offers.seller_id')
                    ->where('seller_offers.id', $line['offer_id'])->where('products.slug', 'like', 'demo-%')
                    ->where('sellers.trade_name', 'like', '%(DEMO)')
                    ->first(['seller_offers.id', 'seller_offers.seller_id', 'seller_offers.sku',
                        'seller_offers.price_cents', 'products.name', 'sellers.trade_name']);
                if (! $offer || $offer->seller_id !== $sellerId || (int) $offer->price_cents !== (int) $line['offer']['price_cents']) {
                    throw ValidationException::withMessages(['cart' => 'Produto DEMO alterado. Atualize a sacola.']);
                }

                $stock = DB::table('stock_levels')->where('offer_id', $offer->id)
                    ->where('seller_id', $sellerId)->lockForUpdate()->first();
                $quantity = (int) $line['quantity'];
                if (! $stock || ((int) $stock->quantity_on_hand - (int) $stock->quantity_reserved) < $quantity) {
                    throw ValidationException::withMessages(['cart' => 'Estoque DEMO insuficiente durante a reserva.']);
                }

                $lineTotal = (int) $offer->price_cents * $quantity;
                $subtotal += $lineTotal;
                $items[] = [$offer, $quantity, $lineTotal];

                $updated = DB::table('stock_levels')->where('offer_id', $offer->id)
                    ->where('seller_id', $sellerId)
                    ->whereRaw('(quantity_on_hand - quantity_reserved) >= ?', [$quantity])
                    ->increment('quantity_reserved', $quantity, ['updated_at' => $now]);
                if ($updated !== 1) {
                    throw ValidationException::withMessages(['cart' => 'Concorrência de estoque detectada. Refaça a tentativa.']);
                }
            }

            DB::table('demo_orders')->insert([
                'id' => $orderId, 'user_id' => $buyer->id, 'idempotency_key' => $idempotencyKey,
                'status' => 'created_demo', 'payment_status' => 'pending_demo',
                'expires_at' => $expiresAt, 'paid_at' => null,
                'items_total_cents' => $subtotal, 'shipping_total_cents' => $shippingTotal,
                'grand_total_cents' => $subtotal + $shippingTotal,
                'address_snapshot' => json_encode((array) $quoteSnapshot['address'], JSON_THROW_ON_ERROR),
                'created_at' => $now, 'updated_at' => $now,
            ]);

            foreach ($items as [$offer, $quantity, $lineTotal]) {
                DB::table('demo_order_items')->insert([
                    'id' => (string) Str::ulid(), 'demo_order_id' => $orderId,
                    'seller_id' => $offer->seller_id, 'offer_id' => $offer->id,
                    'seller_snapshot' => $offer->trade_name, 'name_snapshot' => $offer->name,
                    'sku_snapshot' => $offer->sku, 'quantity' => $quantity,
                    'unit_price_cents' => $offer->price_cents, 'line_total_cents' => $lineTotal,
                ]);
                DB::table('demo_stock_reservations')->insert([
                    'id' => (string) Str::ulid(), 'demo_order_id' => $orderId,
                    'seller_id' => $offer->seller_id, 'offer_id' => $offer->id,
                    'quantity' => $quantity, 'status' => 'active', 'expires_at' => $expiresAt,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            foreach ($shippingRows as $sellerId => $quote) {
                DB::table('demo_order_shipments')->insert([
                    'id' => (string) Str::ulid(), 'demo_order_id' => $orderId, 'seller_id' => $sellerId,
                    'quote_id' => $quote->id, 'service_code' => $quote->service_code,
                    'service_name' => $quote->service_name, 'amount_cents' => $quote->amount_cents,
                    'estimated_days' => $quote->estimated_days,
                    'destination_postal_code' => $quote->destination_postal_code,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            $this->event($orderId, 'demo.order.reserved', ['expires_at' => $expiresAt->toIso8601String()]);
            return $orderId;
        }, 3);
    }

    public function detail(User $buyer, string $orderId): array
    {
        HmlDemo::requireEnabled();
        $this->expireIfDue($buyer, $orderId);
        $record = DB::table('demo_orders')->where('id', $orderId)->where('user_id', $buyer->id)->first();
        abort_unless($record, 404);

        return [
            'record' => $record,
            'items' => DB::table('demo_order_items')->where('demo_order_id', $orderId)->get(),
            'shipments' => DB::table('demo_order_shipments')->where('demo_order_id', $orderId)->get(),
            'reservations' => DB::table('demo_stock_reservations')->where('demo_order_id', $orderId)->get(),
            'payments' => DB::table('demo_payment_attempts')->where('demo_order_id', $orderId)->orderBy('created_at')->get(),
            'events' => DB::table('demo_order_events')->where('demo_order_id', $orderId)->orderBy('created_at')->orderBy('id')->get(),
        ];
    }

    public function cancel(User $buyer, string $orderId): void
    {
        HmlDemo::requireEnabled();
        DB::transaction(function () use ($buyer, $orderId): void {
            $order = DB::table('demo_orders')->where('id', $orderId)->where('user_id', $buyer->id)
                ->lockForUpdate()->first();
            abort_unless($order, 404);
            if ($order->status !== 'created_demo') {
                throw ValidationException::withMessages(['order' => 'Somente pedido sandbox aguardando pagamento pode ser cancelado.']);
            }
            $this->releaseLocked($order, 'cancelled_demo');
        }, 3);
    }

    public function transition(User $buyer, string $orderId, string $action): void
    {
        HmlDemo::requireEnabled();
        DB::transaction(function () use ($buyer, $orderId, $action): void {
            $order = DB::table('demo_orders')->where('id', $orderId)->where('user_id', $buyer->id)
                ->lockForUpdate()->first();
            abort_unless($order, 404);
            $map = [
                'prepare' => ['paid_demo', 'preparing_demo'],
                'ship' => ['preparing_demo', 'shipped_demo'],
                'deliver' => ['shipped_demo', 'delivered_demo'],
            ];
            abort_unless(isset($map[$action]), 422);
            [$from, $to] = $map[$action];
            if ($order->status !== $from) {
                throw ValidationException::withMessages(['action' => 'Etapa incompatível com o estado atual do pedido sandbox.']);
            }
            DB::table('demo_orders')->where('id', $orderId)->update(['status' => $to, 'updated_at' => now()]);
            $this->event($orderId, 'demo.order.'.$action);
        }, 3);
    }

    public function releaseLocked(object $order, string $status): void
    {
        $reservations = DB::table('demo_stock_reservations')->where('demo_order_id', $order->id)
            ->where('status', 'active')->orderBy('offer_id')->get();
        foreach ($reservations as $reservation) {
            $stock = DB::table('stock_levels')->where('offer_id', $reservation->offer_id)
                ->where('seller_id', $reservation->seller_id)->lockForUpdate()->first();
            if (! $stock || (int) $stock->quantity_reserved < (int) $reservation->quantity) {
                throw ValidationException::withMessages(['stock' => 'Ledger de reserva DEMO inconsistente.']);
            }
            DB::table('stock_levels')->where('offer_id', $reservation->offer_id)->update([
                'quantity_reserved' => (int) $stock->quantity_reserved - (int) $reservation->quantity,
                'updated_at' => now(),
            ]);
            DB::table('demo_stock_reservations')->where('id', $reservation->id)->update([
                'status' => $status === 'expired_demo' ? 'expired' : 'released', 'updated_at' => now(),
            ]);
        }
        DB::table('demo_orders')->where('id', $order->id)->update([
            'status' => $status, 'payment_status' => $status === 'cancelled_demo' ? 'cancelled_demo' : $order->payment_status,
            'updated_at' => now(),
        ]);
        $this->event($order->id, 'demo.order.'.($status === 'expired_demo' ? 'expired' : 'cancelled'));
    }

    private function expireIfDue(User $buyer, string $orderId): void
    {
        DB::transaction(function () use ($buyer, $orderId): void {
            $order = DB::table('demo_orders')->where('id', $orderId)->where('user_id', $buyer->id)
                ->lockForUpdate()->first();
            if ($order && $order->status === 'created_demo' && $order->expires_at
                && Carbon::parse($order->expires_at)->lessThanOrEqualTo(now())) {
                $this->releaseLocked($order, 'expired_demo');
            }
        }, 3);
    }

    private function event(string $orderId, string $type, array $metadata = []): void
    {
        DB::table('demo_order_events')->insert([
            'id' => (string) Str::ulid(), 'demo_order_id' => $orderId,
            'event_type' => $type, 'created_at' => now(),
        ]);
    }
}
