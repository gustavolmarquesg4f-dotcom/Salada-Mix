<?php

namespace App\Domain\Shipping;

use App\Domain\Cart\CartManager;
use App\Models\User;
use App\Support\HmlDemo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class HmlSandboxQuoteService
{
    public function __construct(
        private readonly CartManager $cart,
        private readonly HmlSandboxShippingProvider $provider
    ) {
    }

    public function forBuyer(User $buyer, ?string $addressId = null): array
    {
        HmlDemo::requireEnabled();

        $snapshot = $this->cart->read($buyer);
        if ($snapshot['items'] === [] || collect($snapshot['items'])->contains(fn (array $line): bool => ! $line['available'])) {
            throw ValidationException::withMessages(['cart' => 'A sacola DEMO precisa conter apenas produtos disponíveis.']);
        }

        $addressQuery = DB::table('customer_addresses')->where('user_id', $buyer->id);
        if ($addressId !== null) {
            $addressQuery->where('id', $addressId);
        } else {
            $addressQuery->orderByDesc('is_default')->orderBy('created_at');
        }
        $address = $addressQuery->first();
        if (! $address) {
            throw ValidationException::withMessages(['address' => 'Endereço de entrega não encontrado para esta conta.']);
        }

        $cartHash = $this->cartHash($snapshot, (string) $address->postal_code);
        $groups = collect($snapshot['items'])->groupBy(fn (array $line): string => $line['offer']['seller']['id']);
        $result = [];

        foreach ($groups as $sellerId => $lines) {
            $origin = DB::table('shipping_origins')->where('seller_id', $sellerId)
                ->where('is_default', true)->where('is_active', true)->first();
            if (! $origin) {
                throw ValidationException::withMessages(['shipping' => 'Uma loja DEMO está sem origem de envio.']);
            }

            $ids = $lines->pluck('offer_id')->all();
            $rows = DB::table('seller_offers')
                ->join('products', 'products.id', '=', 'seller_offers.product_id')
                ->where('seller_offers.seller_id', $sellerId)->whereIn('seller_offers.id', $ids)
                ->get(['seller_offers.id', 'products.weight_grams', 'products.length_cm', 'products.width_cm', 'products.height_cm'])
                ->keyBy('id');

            $packages = [];
            foreach ($lines as $line) {
                $product = $rows->get($line['offer_id']);
                if (! $product || ! $product->weight_grams || ! $product->length_cm || ! $product->width_cm || ! $product->height_cm) {
                    throw ValidationException::withMessages(['shipping' => 'Produto DEMO sem peso ou dimensões para cotação.']);
                }
                $packages[] = [
                    'weight_grams' => (int) $product->weight_grams,
                    'length_cm' => (int) $product->length_cm,
                    'width_cm' => (int) $product->width_cm,
                    'height_cm' => (int) $product->height_cm,
                    'quantity' => (int) $line['quantity'],
                ];
            }

            $quotes = DB::table('demo_shipping_quotes')
                ->where('user_id', $buyer->id)->where('seller_id', $sellerId)
                ->where('cart_hash', $cartHash)->where('destination_postal_code', $address->postal_code)
                ->where('expires_at', '>', now())->orderBy('amount_cents')->get();

            if ($quotes->count() < 2) {
                DB::table('demo_shipping_quotes')->where('user_id', $buyer->id)
                    ->where('seller_id', $sellerId)->where('expires_at', '<=', now())->delete();
                $now = now();
                foreach ($this->provider->quote((string) $sellerId, (string) $origin->postal_code, (string) $address->postal_code, $packages) as $quote) {
                    DB::table('demo_shipping_quotes')->insert([
                        'id' => (string) Str::ulid(), 'user_id' => $buyer->id, 'seller_id' => $sellerId,
                        'cart_hash' => $cartHash, 'service_code' => $quote['code'], 'service_name' => $quote['name'],
                        'amount_cents' => $quote['amount_cents'], 'estimated_days' => $quote['estimated_days'],
                        'destination_postal_code' => $address->postal_code, 'expires_at' => $now->copy()->addMinutes(15),
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
                $quotes = DB::table('demo_shipping_quotes')
                    ->where('user_id', $buyer->id)->where('seller_id', $sellerId)
                    ->where('cart_hash', $cartHash)->where('expires_at', '>', now())
                    ->orderBy('amount_cents')->get();
            }

            $result[] = [
                'seller' => $lines->first()['offer']['seller'],
                'items' => $lines->values()->all(),
                'quotes' => $quotes->map(fn (object $quote): array => [
                    'id' => $quote->id, 'code' => $quote->service_code, 'name' => $quote->service_name,
                    'amount_cents' => (int) $quote->amount_cents, 'estimated_days' => (int) $quote->estimated_days,
                    'expires_at' => $quote->expires_at,
                ])->all(),
            ];
        }

        return [
            'cart' => $snapshot, 'address' => $address, 'cart_hash' => $cartHash,
            'groups' => $result, 'provider' => 'HML_SANDBOX', 'real_carrier' => false,
        ];
    }

    public function cartHash(array $snapshot, string $postalCode): string
    {
        $lines = collect($snapshot['items'])->map(fn (array $line): array => [
            'offer_id' => $line['offer_id'], 'quantity' => (int) $line['quantity'],
            'price_cents' => (int) ($line['offer']['price_cents'] ?? 0),
        ])->sortBy('offer_id')->values()->all();

        return hash('sha256', json_encode(['postal_code' => $postalCode, 'lines' => $lines], JSON_THROW_ON_ERROR));
    }
}
