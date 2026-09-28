<?php

namespace App\Domain\Catalog\Actions;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\SellerOffer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewSellerOffer
{
    public function execute(SellerOffer $offer, User $actor, string $decision, ?string $reason = null): SellerOffer
    {
        abort_unless($actor->platform_role === 'admin', 403);

        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['decision' => 'Decisão inválida.']);
        }

        if ($decision === 'rejected' && mb_strlen(trim((string) $reason)) < 10) {
            throw ValidationException::withMessages(['reason' => 'Informe o motivo da rejeição.']);
        }

        return DB::transaction(function () use ($offer, $actor, $decision, $reason): SellerOffer {
            $locked = SellerOffer::query()->with(['seller', 'product.category'])->lockForUpdate()->findOrFail($offer->id);
            $product = Product::query()->lockForUpdate()->findOrFail($locked->product_id);

            if ($locked->review_status !== 'pending' || $product->review_status !== 'pending') {
                throw ValidationException::withMessages(['offer' => 'Oferta já analisada ou indisponível.']);
            }

            if ($decision === 'approved' && (
                ! in_array($locked->seller->status, ['approved', 'active'], true)
                || ! $locked->product->category->is_active
            )) {
                throw ValidationException::withMessages(['offer' => 'A empresa e a categoria devem estar aprovadas.']);
            }

            $product->forceFill([
                'review_status' => $decision,
                'reviewed_at' => now(),
                'reviewed_by' => $actor->id,
            ])->save();

            $locked->forceFill([
                'review_status' => $decision,
                'reviewed_at' => now(),
                'reviewed_by' => $actor->id,
            ])->save();

            AuditLog::query()->create([
                'actor_user_id' => $actor->id,
                'seller_id' => $locked->seller_id,
                'action' => 'catalog.offer.'.$decision,
                'metadata' => [
                    'offer_id' => $locked->id,
                    'product_id' => $product->id,
                    'reason' => $reason,
                ],
            ]);

            // A revisão do anúncio não ativa o vendedor nem libera checkout.
            return $locked;
        });
    }
}

