<?php

namespace App\Domain\Orders;

use App\Domain\Catalog\Queries\PublicCatalog;
use App\Models\AuditLog;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DraftOrderService
{
    public function __construct(
        private readonly PublicCatalog $catalog,
        private readonly ReservationManager $reservations
    ) {
    }

    /**
     * Creates a technical RESERVED draft only. No shipping price, tax, financial total,
     * payment intent or paid order is ever created here.
     */
    public function create(User $buyer, string $addressId, string $idempotencyKey): array
    {
        if (! config('marketplace.order_drafts_enabled', false)) {
            throw new HttpException(503, 'Reservas de teste ainda não estão habilitadas.');
        }

        return DB::transaction(function () use ($buyer, $addressId, $idempotencyKey): array {
            // One buyer lock serializes distinct idempotency keys and active-draft quotas.
            User::query()->whereKey($buyer->id)->lockForUpdate()->firstOrFail();

            $existing = DB::table('orders')->where('user_id', $buyer->id)
                ->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $this->detail($buyer, $existing->id);
            }

            $active = DB::table('orders')->where('user_id', $buyer->id)
                ->where('status', 'reserved')->orderBy('id')->lockForUpdate()->get();

            foreach ($active as $previous) {
                if ($previous->expires_at && Carbon::parse($previous->expires_at)->lessThanOrEqualTo(now())) {
                    $this->reservations->releaseLocked($previous, 'expired');
                } else {
                    throw ValidationException::withMessages([
                        'draft' => 'Há uma reserva ativa nesta conta. Conclua o teste ou cancele-a antes de criar outra.',
                    ]);
                }
            }

            $address = CustomerAddress::query()
                ->where('user_id', $buyer->id)->findOrFail($addressId);
            $lines = DB::table('cart_items')->where('user_id', $buyer->id)
                ->orderBy('offer_id')->lockForUpdate()->get();

            if ($lines->isEmpty() || $lines->count() > 20) {
                throw ValidationException::withMessages([
                    'cart' => 'Adicione entre 1 e 20 ofertas válidas antes de criar a reserva.',
                ]);
            }

            $items = [];
            $total = 0;

            // Lock stock consistently by offer_id. A concurrent buyer cannot consume
            // the same units before this transaction commits.
            foreach ($lines as $line) {
                $offer = $this->catalog->visibleOffers()->whereKey($line->offer_id)->first();
                if (! $offer || $line->quantity < 1 || $line->quantity > 20 || $offer->currency !== 'BRL') {
                    throw ValidationException::withMessages(['cart' => 'O carrinho contém uma oferta indisponível.']);
                }

                $stock = DB::table('stock_levels')->where('offer_id', $offer->id)
                    ->where('seller_id', $offer->seller_id)->lockForUpdate()->first();

                if (! $stock || $stock->quantity_on_hand - $stock->quantity_reserved < $line->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => 'Estoque insuficiente; atualize o carrinho.',
                    ]);
                }

                // Public visibility is rechecked AFTER the stock row lock.
                if (! $this->catalog->visibleOffers()->whereKey($offer->id)->exists()) {
                    throw ValidationException::withMessages(['cart' => 'Uma oferta deixou de estar disponível.']);
                }

                $lineTotal = (int) $offer->price_cents * (int) $line->quantity;
                $total += $lineTotal;
                $items[] = [
                    'offer' => $offer,
                    'quantity' => (int) $line->quantity,
                    'line_total_cents' => $lineTotal,
                    'stock' => $stock,
                ];
            }

            $orderId = (string) Str::ulid();
            $expiresAt = now()->addMinutes(max(1, min(60, (int) config('marketplace.draft_reservation_minutes', 15))));
            $now = now();

            DB::table('orders')->insert([
                'id' => $orderId,
                'user_id' => $buyer->id,
                'idempotency_key' => $idempotencyKey,
                'status' => 'reserved',
                'expires_at' => $expiresAt,
                'items_total_cents' => $total,
                'shipping_total_cents' => null,
                'grand_total_cents' => null,
                'currency' => 'BRL',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Snapshots are immutable. Seller API must not expose buyer address on an unpaid draft.
            $addressSnapshot = json_encode([
                'recipient_name' => $address->recipient_name,
                'phone' => $address->phone,
                'postal_code' => $address->postal_code,
                'street' => $address->street,
                'number' => $address->number,
                'complement' => $address->complement,
                'neighborhood' => $address->neighborhood,
                'city' => $address->city,
                'state' => $address->state,
            ], JSON_THROW_ON_ERROR);

            $groups = collect($items)->groupBy(fn (array $item) => $item['offer']->seller_id);

            foreach ($groups as $sellerId => $group) {
                $suborderId = (string) Str::ulid();
                DB::table('suborders')->insert([
                    'id' => $suborderId,
                    'order_id' => $orderId,
                    'seller_id' => $sellerId,
                    'status' => 'reserved',
                    'items_total_cents' => $group->sum('line_total_cents'),
                    'shipping_cents' => null,
                    'total_cents' => null,
                    'delivery_address_snapshot' => $addressSnapshot,
                    'currency' => 'BRL',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($group as $item) {
                    $offer = $item['offer'];
                    DB::table('suborder_items')->insert([
                        'id' => (string) Str::ulid(),
                        'suborder_id' => $suborderId,
                        'seller_id' => $sellerId,
                        'offer_id' => $offer->id,
                        'name_snapshot' => $offer->product->name,
                        'sku_snapshot' => $offer->sku,
                        'quantity' => $item['quantity'],
                        'unit_price_cents' => $offer->price_cents,
                        'line_total_cents' => $item['line_total_cents'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    DB::table('stock_reservations')->insert([
                        'id' => (string) Str::ulid(),
                        'suborder_id' => $suborderId,
                        'seller_id' => $sellerId,
                        'offer_id' => $offer->id,
                        'quantity' => $item['quantity'],
                        'status' => 'active',
                        'expires_at' => $expiresAt,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    DB::table('stock_levels')->where('offer_id', $offer->id)
                        ->where('seller_id', $sellerId)->update([
                            'quantity_reserved' => $item['stock']->quantity_reserved + $item['quantity'],
                            'updated_at' => $now,
                        ]);
                }
            }

            DB::table('order_events')->insert([
                'id' => (string) Str::ulid(),
                'order_id' => $orderId,
                'suborder_id' => null,
                'actor_user_id' => $buyer->id,
                'event_type' => 'order.draft.reserved',
                'metadata' => json_encode([
                    'suborders' => $groups->count(), 'lines' => count($items),
                ], JSON_THROW_ON_ERROR),
                'created_at' => $now,
            ]);
            AuditLog::query()->create([
                'actor_user_id' => $buyer->id,
                'action' => 'order.draft.reserved',
                'metadata' => ['order_id' => $orderId, 'suborders' => $groups->count()],
            ]);

            return $this->detail($buyer, $orderId);
        }, 3);
    }

    public function cancel(User $buyer, string $orderId): array
    {
        return DB::transaction(function () use ($buyer, $orderId): array {
            $order = DB::table('orders')->where('user_id', $buyer->id)
                ->where('id', $orderId)->lockForUpdate()->first();
            abort_unless($order, 404);

            if ($order->status === 'reserved') {
                $reason = $order->expires_at && Carbon::parse($order->expires_at)->lessThanOrEqualTo(now()) ? 'expired' : 'cancelled';
                $this->reservations->releaseLocked($order, $reason, $buyer->id);
            } elseif (! in_array($order->status, ['expired', 'cancelled'], true)) {
                throw ValidationException::withMessages([
                    'order' => 'Este pedido não pode ser cancelado por este fluxo de reserva.',
                ]);
            }

            return $this->detail($buyer, $orderId);
        }, 3);
    }

    public function list(User $buyer): array
    {
        $orders = DB::table('orders')->where('user_id', $buyer->id)
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(20);

        return [
            'data' => $orders->getCollection()->map(fn ($order) => $this->summary($order))->all(),
            'meta' => [
                'current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ];
    }

    public function detail(User $buyer, string $orderId): array
    {
        $order = DB::table('orders')->where('id', $orderId)
            ->where('user_id', $buyer->id)->first();
        abort_unless($order, 404);

        $suborders = DB::table('suborders')->join('sellers', 'sellers.id', '=', 'suborders.seller_id')
            ->where('suborders.order_id', $orderId)->orderBy('suborders.id')
            ->get(['suborders.*', 'sellers.trade_name']);

        $groups = $suborders->map(function ($suborder): array {
            $lines = DB::table('suborder_items')->where('suborder_id', $suborder->id)
                ->orderBy('id')->get(['offer_id', 'name_snapshot', 'sku_snapshot',
                    'quantity', 'unit_price_cents', 'line_total_cents']);

            return [
                'id' => $suborder->id,
                'seller' => ['id' => $suborder->seller_id, 'name' => $suborder->trade_name],
                'status' => $suborder->status,
                'items_total_cents' => $suborder->items_total_cents,
                'shipping_cents' => null,
                'total_cents' => null,
                'delivery_address' => json_decode($suborder->delivery_address_snapshot, true),
                'items' => $lines->all(),
            ];
        })->all();

        return [
            'data' => array_merge($this->summary($order), ['suborders' => $groups]),
        ];
    }

    public function forSeller(string $sellerId, int $userId): array
    {
        abort_unless(DB::table('seller_memberships')
            ->where('seller_id', $sellerId)->where('user_id', $userId)
            ->where('status', 'active')->exists(), 403);

        // Unpaid reservations: no buyer name, address, phone or shipping snapshot.
        $rows = DB::table('suborders')->join('orders', 'orders.id', '=', 'suborders.order_id')
            ->where('suborders.seller_id', $sellerId)
            ->orderByDesc('suborders.created_at')->limit(20)
            ->get(['suborders.id', 'suborders.status', 'suborders.items_total_cents',
                'suborders.created_at', 'orders.expires_at']);

        return ['data' => $rows->map(fn ($row) => (array) $row)->all(), 'payments_enabled' => false];
    }

    public function adminSnapshot(): array
    {
        return ['data' => [
            'reserved_drafts' => DB::table('orders')->where('status', 'reserved')
                ->where('expires_at', '>', now())->count(),
            'expired_pending_cleanup' => DB::table('orders')->where('status', 'reserved')
                ->where('expires_at', '<=', now())->count(),
            'paid_orders_enabled' => false,
        ]];
    }

    private function summary(object $order): array
    {
        return [
            'id' => $order->id,
            'status' => $order->status === 'reserved' && $order->expires_at && Carbon::parse($order->expires_at)->lessThanOrEqualTo(now())
                ? 'expired_pending_cleanup' : $order->status,
            'items_total_cents' => $order->items_total_cents,
            'shipping_total_cents' => null,
            'grand_total_cents' => null,
            'expires_at' => $order->expires_at,
            'currency' => $order->currency,
            'checkout_enabled' => false,
            'payments_enabled' => false,
        ];
    }
}
