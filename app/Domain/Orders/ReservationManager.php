<?php

namespace App\Domain\Orders;

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use LogicException;

class ReservationManager
{
    /**
     * Caller MUST own a FOR UPDATE lock on this order inside one DB transaction.
     * Idempotent: only an order still in reserved state may release active holds.
     */
    public function releaseLocked(object $order, string $reason, ?int $actorUserId = null): bool
    {
        if ($order->status !== 'reserved') {
            return false;
        }

        if (! in_array($reason, ['cancelled', 'expired'], true)) {
            throw new LogicException('Invalid reservation release reason.');
        }

        $suborders = DB::table('suborders')->where('order_id', $order->id)
            ->orderBy('id')->pluck('id');

        $holds = DB::table('stock_reservations')
            ->whereIn('suborder_id', $suborders->all())
            ->where('status', 'active')->orderBy('offer_id')->get();

        foreach ($holds as $hold) {
            $stock = DB::table('stock_levels')
                ->where('offer_id', $hold->offer_id)->where('seller_id', $hold->seller_id)
                ->lockForUpdate()->first();

            if (! $stock || $stock->quantity_reserved < $hold->quantity) {
                // Never mark a hold released if that would make the ledger disagree with stock.
                throw new LogicException('Stock reservation ledger is inconsistent.');
            }

            DB::table('stock_levels')->where('offer_id', $hold->offer_id)
                ->where('seller_id', $hold->seller_id)
                ->update([
                    'quantity_reserved' => $stock->quantity_reserved - $hold->quantity,
                    'updated_at' => now(),
                ]);

            DB::table('stock_reservations')->where('id', $hold->id)
                ->where('status', 'active')->update([
                    'status' => $reason === 'expired' ? 'expired' : 'released',
                    'updated_at' => now(),
                ]);
        }

        DB::table('suborders')->where('order_id', $order->id)->update([
            'status' => $reason, 'updated_at' => now(),
        ]);
        DB::table('orders')->where('id', $order->id)->update([
            'status' => $reason,
            'cancelled_at' => $reason === 'cancelled' ? now() : null,
            'updated_at' => now(),
        ]);

        DB::table('order_events')->insert([
            'id' => (string) Str::ulid(),
            'order_id' => $order->id,
            'suborder_id' => null,
            'actor_user_id' => $actorUserId,
            'event_type' => 'order.draft.'.$reason,
            'metadata' => json_encode(['released_holds' => $holds->count()], JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
        AuditLog::query()->create([
            'actor_user_id' => $actorUserId,
            'action' => 'order.draft.'.$reason,
            'metadata' => ['order_id' => $order->id, 'released_holds' => $holds->count()],
        ]);

        return true;
    }

    /** Schedule this every minute. Each order is a separate transaction and lock. */
    public function expireDue(int $limit = 50): int
    {
        $ids = DB::table('orders')->where('status', 'reserved')
            ->where('expires_at', '<=', now())->orderBy('expires_at')->limit($limit)->pluck('id');
        $released = 0;

        foreach ($ids as $id) {
            $released += (int) DB::transaction(function () use ($id): bool {
                $order = DB::table('orders')->where('id', $id)->lockForUpdate()->first();

                if (! $order || $order->status !== 'reserved' || ! $order->expires_at
                    || Carbon::parse($order->expires_at)->greaterThan(now())) {
                    return false;
                }

                return $this->releaseLocked($order, 'expired');
            }, 3);
        }

        return $released;
    }
}
