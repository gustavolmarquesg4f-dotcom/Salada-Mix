<?php

namespace App\Domain\Seller\Actions;

use App\Models\AuditLog;
use App\Models\Seller;
use App\Models\SellerReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewSellerApplication
{
    public function execute(Seller $seller, User $actor, string $decision, ?string $reason = null): Seller
    {
        abort_unless($actor->platform_role === 'admin', 403);

        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['decision' => 'Decisão inválida.']);
        }

        return DB::transaction(function () use ($seller, $actor, $decision, $reason): Seller {
            $locked = Seller::query()->lockForUpdate()->findOrFail($seller->id);

            if (! in_array($locked->status, ['submitted', 'under_review'], true)) {
                throw ValidationException::withMessages(['seller' => 'A empresa não está disponível para esta decisão.']);
            }

            $previous = $locked->status;
            $locked->forceFill([
                'status' => $decision,
                'approved_at' => $decision === 'approved' ? now() : null,
                'approved_by' => $decision === 'approved' ? $actor->id : null,
            ])->save();

            SellerReview::query()->create([
                'seller_id' => $locked->id,
                'actor_user_id' => $actor->id,
                'decision' => $decision,
                'from_status' => $previous,
                'to_status' => $decision,
                'reason' => $reason,
            ]);

            AuditLog::query()->create([
                'actor_user_id' => $actor->id,
                'seller_id' => $locked->id,
                'action' => 'seller.application.'.$decision,
                'metadata' => ['from' => $previous, 'to' => $decision],
            ]);

            // "approved" não é "active": PSP, logística e moderação ainda precisam ser homologados.
            return $locked;
        });
    }
}

