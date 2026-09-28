<?php

namespace App\Domain\Cart;

use App\Domain\Catalog\Presenters\PublicOfferData;
use App\Domain\Catalog\Queries\PublicCatalog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartManager
{
    public function __construct(private readonly PublicCatalog $catalog)
    {
    }

    /** Cart has live prices only, no stock reservation or financial order. */
    public function read(User $user): array
    {
        $lines = DB::table('cart_items')->where('user_id', $user->id)
            ->orderBy('created_at')->orderBy('offer_id')->get();

        if ($lines->isEmpty()) {
            return ['items' => [], 'subtotal_cents' => 0, 'checkout_enabled' => false];
        }

        $visible = $this->catalog->visibleOffers()
            ->whereIn('seller_offers.id', $lines->pluck('offer_id')->all())
            ->get()->keyBy('id');

        $items = [];
        $subtotal = 0;

        foreach ($lines as $line) {
            $offer = $visible->get($line->offer_id);
            $available = $offer
                && ($offer->stock->quantity_on_hand - $offer->stock->quantity_reserved) >= $line->quantity;

            if (! $available) {
                $items[] = ['offer_id' => $line->offer_id, 'quantity' => $line->quantity,
                    'available' => false, 'offer' => null, 'line_total_cents' => null];

                continue;
            }

            $subtotal += $offer->price_cents * $line->quantity;
            $items[] = [
                'offer_id' => $offer->id,
                'quantity' => $line->quantity,
                'available' => true,
                'offer' => PublicOfferData::from($offer),
                'line_total_cents' => $offer->price_cents * $line->quantity,
            ];
        }

        return ['items' => $items, 'subtotal_cents' => $subtotal, 'checkout_enabled' => false];
    }

    /** Absolute quantity; repeated request is idempotent. The client never supplies a price or seller ID. */
    public function setQuantity(User $user, string $offerId, int $quantity): array
    {
        if ($quantity < 1 || $quantity > 20) {
            throw ValidationException::withMessages(['quantity' => 'A quantidade deve estar entre 1 e 20.']);
        }

        $offer = $this->catalog->visibleOffers()->findOrFail($offerId);

        if (($offer->stock->quantity_on_hand - $offer->stock->quantity_reserved) < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Quantidade superior à disponibilidade atual.']);
        }

        $now = now();
        DB::table('cart_items')->upsert([
            ['user_id' => $user->id, 'offer_id' => $offer->id, 'quantity' => $quantity,
                'created_at' => $now, 'updated_at' => $now],
        ], ['user_id', 'offer_id'], ['quantity', 'updated_at']);

        // This does not reserve stock; checkout will revalidate availability atomically.
        return $this->read($user);
    }

    public function remove(User $user, string $offerId): array
    {
        DB::table('cart_items')->where('user_id', $user->id)
            ->where('offer_id', $offerId)->delete();

        return $this->read($user);
    }
}

