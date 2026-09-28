<?php

namespace App\Domain\Wishlist;

use App\Domain\Catalog\Presenters\PublicOfferData;
use App\Domain\Catalog\Queries\PublicCatalog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WishlistManager
{
    public function __construct(private readonly PublicCatalog $catalog)
    {
    }

    public function read(User $user): array
    {
        $ids = DB::table('wishlist_items')->where('user_id', $user->id)
            ->orderByDesc('created_at')->pluck('offer_id');

        if ($ids->isEmpty()) {
            return ['items' => []];
        }

        $offers = $this->catalog->visibleOffers()->whereIn('seller_offers.id', $ids->all())
            ->get()->keyBy('id');

        return ['items' => $ids->map(fn (string $id) =>
            $offers->has($id) ? PublicOfferData::from($offers->get($id)) : null
        )->filter()->values()->all()];
    }

    public function add(User $user, string $offerId): array
    {
        $offer = $this->catalog->visibleOffers()->findOrFail($offerId);
        $now = now();

        DB::table('wishlist_items')->upsert([
            ['user_id' => $user->id, 'offer_id' => $offer->id,
                'created_at' => $now, 'updated_at' => $now],
        ], ['user_id', 'offer_id'], ['updated_at']);

        return $this->read($user);
    }

    public function remove(User $user, string $offerId): array
    {
        DB::table('wishlist_items')->where('user_id', $user->id)
            ->where('offer_id', $offerId)->delete();

        return $this->read($user);
    }
}

