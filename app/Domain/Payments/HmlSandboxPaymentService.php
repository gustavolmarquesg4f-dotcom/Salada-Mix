<?php

namespace App\Domain\Payments;

use App\Domain\Orders\HmlSandboxOrderService;
use App\Models\User;
use App\Support\HmlDemo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class HmlSandboxPaymentService
{
    public function __construct(private readonly HmlSandboxOrderService $orders)
    {
    }

    public function attempt(User $buyer, string $orderId, string $outcome, string $idempotencyKey): void
    {
        HmlDemo::requireEnabled();
        if (! in_array($outcome, ['approve', 'decline'], true)) {
            throw ValidationException::withMessages(['outcome' => 'Resultado sandbox inválido.']);
        }

        DB::transaction(function () use ($buyer, $orderId, $outcome, $idempotencyKey): void {
            $order = DB::table('demo_orders')->where('id', $orderId)->where('user_id', $buyer->id)
                ->lockForUpdate()->first();
            abort_unless($order, 404);

            $existing = DB::table('demo_payment_attempts')->where('demo_order_id', $orderId)
                ->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                if ($existing->outcome !== $outcome) {
                    throw ValidationException::withMessages(['payment' => 'A chave idempotente já foi utilizada com outro resultado.']);
                }
                return;
            }

            if ($order->status !== 'created_demo') {
                throw ValidationException::withMessages(['payment' => 'O pedido sandbox não está aguardando pagamento.']);
            }
            if ($order->expires_at && Carbon::parse($order->expires_at)->lessThanOrEqualTo(now())) {
                $this->orders->releaseLocked($order, 'expired_demo');
                throw ValidationException::withMessages(['payment' => 'A reserva expirou. Monte outro pedido sandbox.']);
            }

            $now = now();
            DB::table('demo_payment_attempts')->insert([
                'id' => (string) Str::ulid(), 'demo_order_id' => $orderId,
                'idempotency_key' => $idempotencyKey, 'provider' => 'hml_sandbox',
                'provider_reference' => 'HML-'.Str::upper((string) Str::ulid()),
                'outcome' => $outcome, 'status' => $outcome === 'approve' ? 'approved_demo' : 'declined_demo',
                'amount_cents' => $order->grand_total_cents, 'created_at' => $now, 'updated_at' => $now,
            ]);

            if ($outcome === 'decline') {
                DB::table('demo_orders')->where('id', $orderId)->update([
                    'payment_status' => 'declined_demo', 'updated_at' => $now,
                ]);
                $this->event($orderId, 'demo.payment.declined');
                return;
            }

            $reservations = DB::table('demo_stock_reservations')->where('demo_order_id', $orderId)
                ->where('status', 'active')->orderBy('offer_id')->get();
            if ($reservations->isEmpty()) {
                throw ValidationException::withMessages(['stock' => 'Reserva de estoque DEMO não encontrada.']);
            }

            foreach ($reservations as $reservation) {
                $stock = DB::table('stock_levels')->where('offer_id', $reservation->offer_id)
                    ->where('seller_id', $reservation->seller_id)->lockForUpdate()->first();
                if (! $stock || (int) $stock->quantity_reserved < (int) $reservation->quantity
                    || (int) $stock->quantity_on_hand < (int) $reservation->quantity) {
                    throw ValidationException::withMessages(['stock' => 'Estoque DEMO inconsistente no fechamento do pagamento.']);
                }
                DB::table('stock_levels')->where('offer_id', $reservation->offer_id)->update([
                    'quantity_on_hand' => (int) $stock->quantity_on_hand - (int) $reservation->quantity,
                    'quantity_reserved' => (int) $stock->quantity_reserved - (int) $reservation->quantity,
                    'updated_at' => $now,
                ]);
                DB::table('demo_stock_reservations')->where('id', $reservation->id)->update([
                    'status' => 'consumed', 'updated_at' => $now,
                ]);
            }

            DB::table('demo_orders')->where('id', $orderId)->update([
                'status' => 'paid_demo', 'payment_status' => 'approved_demo',
                'paid_at' => $now, 'updated_at' => $now,
            ]);
            $this->event($orderId, 'demo.payment.approved');
        }, 3);
    }

    private function event(string $orderId, string $type): void
    {
        DB::table('demo_order_events')->insert([
            'id' => (string) Str::ulid(), 'demo_order_id' => $orderId,
            'event_type' => $type, 'created_at' => now(),
        ]);
    }
}
