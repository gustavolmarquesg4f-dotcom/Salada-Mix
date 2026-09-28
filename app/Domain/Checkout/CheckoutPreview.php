<?php

namespace App\Domain\Checkout;

use App\Domain\Cart\CartManager;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckoutPreview
{
    public function __construct(private readonly CartManager $cart)
    {
    }

    /** Read-only grouping. No quote, reservation, order or payment is created. */
    public function forBuyer(User $buyer): array
    {
        $cart = $this->cart->read($buyer);
        $groups = [];
        $unavailable = 0;

        foreach ($cart['items'] as $line) {
            if (! $line['available']) {
                $unavailable++;

                continue;
            }

            $seller = $line['offer']['seller'];
            $sellerId = $seller['id'];

            if (! isset($groups[$sellerId])) {
                $hasOrigin = DB::table('shipping_origins')->where('seller_id', $sellerId)
                    ->where('is_default', true)->where('is_active', true)->exists();

                $groups[$sellerId] = [
                    'seller' => $seller,
                    'items' => [],
                    'items_subtotal_cents' => 0,
                    'shipping' => [
                        'status' => $hasOrigin ? 'provider_not_configured' : 'origin_not_configured',
                        'amount_cents' => null,
                        'estimated_days' => null,
                    ],
                    'total_cents' => null,
                ];
            }

            $groups[$sellerId]['items'][] = $line;
            $groups[$sellerId]['items_subtotal_cents'] += $line['line_total_cents'];
        }

        return [
            'groups' => array_values($groups),
            'items_subtotal_cents' => $cart['subtotal_cents'],
            'unavailable_items_count' => $unavailable,
            'grand_total_cents' => null,
            'checkout_enabled' => false,
            'reason' => 'Cotação real de frete e integração de pagamento ainda não homologadas.',
        ];
    }
}
