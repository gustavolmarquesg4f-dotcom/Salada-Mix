<?php

namespace App\Domain\Catalog\Actions;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerOffer;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateSellerOffer
{
    public function execute(Seller $seller, User $actor, array $data): SellerOffer
    {
        abort_unless(
            $seller->memberships()->where('user_id', $actor->id)
                ->where('status', 'active')->whereIn('role', ['owner', 'manager'])->exists(),
            403
        );

        if (! in_array($seller->status, ['approved', 'active'], true)) {
            throw ValidationException::withMessages(['seller' => 'A empresa precisa ser aprovada antes de cadastrar ofertas.']);
        }

        $sku = Str::upper(trim($data['sku']));

        if ($seller->offers()->where('sku', $sku)->exists()) {
            throw ValidationException::withMessages(['sku' => 'SKU já utilizado nesta empresa.']);
        }

        return DB::transaction(function () use ($seller, $actor, $data, $sku): SellerOffer {
            $category = Category::query()->where('is_active', true)->findOrFail($data['category_id']);
            $slug = Str::slug($data['name']) ?: 'produto';
            $slug .= '-'.Str::lower((string) Str::ulid());

            $product = Product::query()->create([
                'category_id' => $category->id,
                'created_by_seller_id' => $seller->id,
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'weight_grams' => $data['weight_grams'], 'length_cm' => $data['length_cm'],
                'width_cm' => $data['width_cm'], 'height_cm' => $data['height_cm'],
                'review_status' => 'pending',
            ]);

            $offer = SellerOffer::query()->create([
                'seller_id' => $seller->id,
                'product_id' => $product->id,
                'sku' => $sku,
                'price_cents' => $data['price_cents'],
                'currency' => 'BRL',
                'review_status' => 'pending',
            ]);

            StockLevel::query()->create([
                'offer_id' => $offer->id,
                'seller_id' => $seller->id,
                'quantity_on_hand' => $data['stock_quantity'],
                'quantity_reserved' => 0,
            ]);

            DB::table('inventory_movements')->insert([
                'id' => (string) Str::ulid(),
                'offer_id' => $offer->id,
                'seller_id' => $seller->id,
                'actor_user_id' => $actor->id,
                'quantity_delta' => $data['stock_quantity'],
                'reason' => 'initial_registration',
                'created_at' => now(),
            ]);

            AuditLog::query()->create([
                'actor_user_id' => $actor->id,
                'seller_id' => $seller->id,
                'action' => 'catalog.offer.submitted',
                'metadata' => ['offer_id' => $offer->id, 'product_id' => $product->id],
            ]);

            return $offer;
        });
    }
}

