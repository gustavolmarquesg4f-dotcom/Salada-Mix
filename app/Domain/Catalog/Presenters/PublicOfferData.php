<?php

namespace App\Domain\Catalog\Presenters;

use App\Models\SellerOffer;

class PublicOfferData
{
    public static function from(SellerOffer $offer, bool $details = false): array
    {
        $data = [
            'id' => $offer->id,
            'name' => $offer->product->name,
            'slug' => $offer->product->slug,
            'category' => [
                'name' => $offer->product->category->name,
                'slug' => $offer->product->category->slug,
            ],
            'seller' => [
                'id' => $offer->seller->id,
                'name' => $offer->seller->trade_name,
            ],
            'price_cents' => $offer->price_cents,
            'currency' => 'BRL',
            'available' => ($offer->stock->quantity_on_hand - $offer->stock->quantity_reserved) > 0,
            'url' => route('storefront.offer', $offer),
        ];

        if ($details) {
            $data['description'] = $offer->product->description;
        }

        return $data;
    }
}

