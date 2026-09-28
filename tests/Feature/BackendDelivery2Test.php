<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerOffer;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendDelivery2Test extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_exposes_categories_and_only_eligible_offers(): void
    {
        $beauty = $this->category('Beleza', 'beleza');
        $technology = $this->category('Tecnologia', 'tecnologia');

        $visible = $this->offer($beauty, 'Sérum facial', 4990, 'active', 'approved', 10, 'SKU1');
        $this->offer($technology, 'Câmera', 12000, 'approved', 'approved', 2, 'SKU2');
        $this->offer($technology, 'Fone pendente', 18000, 'active', 'pending', 8, 'SKU3');
        $this->offer($technology, 'Controle esgotado', 16000, 'active', 'approved', 0, 'SKU4');

        $this->getJson(route('api.catalog.categories'))->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('data.0.slug', 'beleza');

        $res = $this->getJson(route('api.catalog.offers.index'))->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $visible->id)
            ->assertJsonPath('data.0.seller.name', $visible->seller->trade_name)
            ->assertJsonPath('data.0.price_cents', 4990)
            ->assertJsonPath('data.0.available', true);

        $this->assertStringNotContainsString('contact_email', $res->getContent());
        $this->assertStringNotContainsString('cnpj', $res->getContent());
        $this->getJson(route('api.catalog.offers.show', $visible))->assertOk()
            ->assertJsonPath('data.description', 'Descrição pública do anúncio.');

        $this->getJson(route('api.catalog.offers.show', SellerOffer::query()->where('sku', 'SKU2')->first()))
            ->assertNotFound();
    }

    public function test_search_filters_by_product_seller_category_and_price_in_cents(): void
    {
        $cat = $this->category('Beleza', 'beleza');
        $tech = $this->category('Tecnologia', 'tecnologia');
        $a = $this->offer($cat, 'Sérum facial', 4990, 'active', 'approved', 5, 'SKUA');
        $b = $this->offer($cat, 'Kit facial', 8990, 'active', 'approved', 5, 'SKUB');
        $this->offer($tech, 'Controle gamer', 12990, 'active', 'approved', 5, 'SKUC');

        $this->getJson(route('api.catalog.offers.index', [
            'q' => 'facial', 'category' => 'beleza', 'max_price_cents' => 6000,
        ]))->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $a->id);

        $this->getJson(route('api.catalog.offers.index', [
            'category' => 'beleza', 'sort' => 'price_desc', 'per_page' => 1,
        ]))->assertOk()->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1)->assertJsonPath('data.0.id', $b->id);

        $this->getJson(route('api.catalog.offers.index', ['q' => 'Loja SKUC']))
            ->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_invalid_search_input_and_price_range_are_rejected(): void
    {
        $this->getJson(route('api.catalog.offers.index', ['sort' => 'DROP TABLE']))
            ->assertUnprocessable()->assertJsonValidationErrors('sort');

        $this->getJson(route('api.catalog.offers.index', ['per_page' => 1000]))
            ->assertUnprocessable()->assertJsonValidationErrors('per_page');

        $this->getJson(route('api.catalog.offers.index', [
            'min_price_cents' => 9000, 'max_price_cents' => 1000,
        ]))->assertUnprocessable()->assertJsonValidationErrors('max_price_cents');
    }

    public function test_cart_requires_verified_user_and_uses_live_server_price(): void
    {
        $offer = $this->offer($this->category('Beleza', 'beleza'), 'Sérum', 4990);
        $user = User::factory()->create();

        $this->get(route('buyer.cart.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('buyer.cart.index'))->assertRedirect(route('verification.notice'));

        $this->actingAs($user)
            ->putJson(route('buyer.cart.put', $offer), [
                'quantity' => 2, 'price_cents' => 1, 'seller_id' => 'someone-else',
            ])->assertOk()
            ->assertJsonPath('items.0.offer.price_cents', 4990)
            ->assertJsonPath('items.0.line_total_cents', 9980)
            ->assertJsonPath('subtotal_cents', 9980)
            ->assertJsonPath('checkout_enabled', false);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id, 'offer_id' => $offer->id, 'quantity' => 2,
        ]);
        $this->assertSame(0, $offer->stock->fresh()->quantity_reserved);

        $this->actingAs($user)->putJson(route('buyer.cart.put', $offer), [
            'quantity' => 2,
        ])->assertOk();
        $this->assertDatabaseCount('cart_items', 1);
        $this->actingAs($user)->deleteJson(route('buyer.cart.destroy', $offer))
            ->assertOk()->assertJsonPath('subtotal_cents', 0);
    }

    public function test_cart_rejects_hidden_offer_overstock_and_invalid_quantity(): void
    {
        $offer = $this->offer($this->category('Beleza', 'beleza'), 'Sérum', 4990, 'active', 'approved', 2);
        $user = User::factory()->create();

        $this->actingAs($user)->putJson(route('buyer.cart.put', $offer), ['quantity' => 3])
            ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->actingAs($user)->putJson(route('buyer.cart.put', $offer), ['quantity' => 21])
            ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->assertDatabaseCount('cart_items', 0);

        $offer->seller->forceFill(['status' => 'suspended'])->save();
        $this->actingAs($user)->putJson(route('buyer.cart.put', $offer), ['quantity' => 1])->assertNotFound();
    }

    public function test_cart_keeps_unavailable_line_without_exposing_private_offer(): void
    {
        $offer = $this->offer($this->category('Beleza', 'beleza'), 'Sérum', 4990);
        $user = User::factory()->create();
        $this->actingAs($user)->putJson(route('buyer.cart.put', $offer), ['quantity' => 2])->assertOk();
        $offer->seller->forceFill(['status' => 'suspended'])->save();

        $this->actingAs($user)->getJson(route('buyer.cart.index'))->assertOk()
            ->assertJsonPath('items.0.available', false)
            ->assertJsonPath('items.0.offer', null)
            ->assertJsonPath('items.0.line_total_cents', null)
            ->assertJsonPath('checkout_enabled', false)
            ->assertJsonPath('subtotal_cents', 0);
    }

    public function test_cart_and_favorites_are_isolated_by_buyer(): void
    {
        $offer = $this->offer($this->category('Moda', 'moda'), 'Bolsa', 19900);
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->actingAs($alice)->putJson(route('buyer.cart.put', $offer), ['quantity' => 1])->assertOk();
        $this->actingAs($alice)->putJson(route('buyer.wishlist.put', $offer))->assertOk()
            ->assertJsonCount(1, 'items');
        $this->actingAs($alice)->putJson(route('buyer.wishlist.put', $offer))->assertOk();
        $this->assertDatabaseCount('wishlist_items', 1);

        $this->actingAs($bob)->getJson(route('buyer.cart.index'))->assertOk()->assertJsonCount(0, 'items');
        $this->actingAs($bob)->getJson(route('buyer.wishlist.index'))->assertOk()->assertJsonCount(0, 'items');
        $this->actingAs($bob)->deleteJson(route('buyer.cart.destroy', $offer))->assertOk();
        $this->actingAs($bob)->deleteJson(route('buyer.wishlist.destroy', $offer))->assertOk();
        $this->assertDatabaseHas('cart_items', ['user_id' => $alice->id, 'offer_id' => $offer->id]);
        $this->assertDatabaseHas('wishlist_items', ['user_id' => $alice->id, 'offer_id' => $offer->id]);
    }

    public function test_favorite_becomes_invisible_when_seller_is_suspended(): void
    {
        $offer = $this->offer($this->category('Moda', 'moda'), 'Bolsa', 19900);
        $user = User::factory()->create();

        $this->actingAs($user)->putJson(route('buyer.wishlist.put', $offer))->assertOk();
        $offer->seller->forceFill(['status' => 'suspended'])->save();

        $this->actingAs($user)->getJson(route('buyer.wishlist.index'))
            ->assertOk()->assertJsonCount(0, 'items');
        $this->actingAs($user)->putJson(route('buyer.wishlist.put', $offer))->assertNotFound();
    }

    private function category(string $name, string $slug): Category
    {
        return Category::query()->create([
            'name' => $name, 'slug' => $slug, 'is_active' => true, 'position' => 1,
        ]);
    }

    private function offer(
        Category $category,
        string $name,
        int $price,
        string $sellerStatus = 'active',
        string $review = 'approved',
        int $stock = 10,
        string $sku = 'SKU-DEMO'
    ): SellerOffer {
        $owner = User::factory()->create();
        $seller = Seller::query()->create([
            'owner_user_id' => $owner->id,
            'legal_name' => 'Empresa '.$sku.' Ltda',
            'trade_name' => 'Loja '.$sku,
            'cnpj' => str_pad((string) (Seller::query()->count() + 1), 14, '0', STR_PAD_LEFT),
            'contact_email' => 'comercial@example.test',
            'status' => $sellerStatus,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'created_by_seller_id' => $seller->id,
            'name' => $name, 'slug' => 'produto-'.strtolower($sku),
            'description' => 'Descrição pública do anúncio.',
            'review_status' => $review,
        ]);
        $offer = SellerOffer::query()->create([
            'seller_id' => $seller->id, 'product_id' => $product->id,
            'sku' => $sku, 'price_cents' => $price,
            'currency' => 'BRL', 'review_status' => $review,
        ]);
        StockLevel::query()->create([
            'offer_id' => $offer->id, 'seller_id' => $seller->id,
            'quantity_on_hand' => $stock, 'quantity_reserved' => 0,
        ]);

        return $offer;
    }
}

