<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerOffer;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_search_uses_only_approved_active_and_available_offers(): void
    {
        $category = $this->category('Tecnologia', 'tecnologia');
        $seller = $this->seller('Loja Aurora');
        $this->offer($seller, $category, 'Fone Verde');
        $pending = $this->offer($seller, $category, 'Fone Oculto');
        $pending->update(['review_status' => 'pending']);
        $empty = $this->offer($seller, $category, 'Fone Sem Estoque');
        $empty->stock->update(['quantity_on_hand' => 0]);

        $other = $this->seller('Outra Loja', 'submitted');
        $this->offer($other, $category, 'Fone Não Habilitado');

        $this->get(route('storefront.search', ['q' => 'Fone']))
            ->assertOk()
            ->assertSee('Fone Verde')
            ->assertDontSee('Fone Oculto')
            ->assertDontSee('Fone Sem Estoque')
            ->assertDontSee('Fone Não Habilitado')
            ->assertViewHas('offers', fn ($offers) => $offers->total() === 1);
    }

    public function test_search_can_match_public_seller_and_treat_sql_wildcards_as_literals(): void
    {
        $category = $this->category('Beleza', 'beleza');
        $seller = $this->seller('Estação Mix');
        $this->offer($seller, $category, 'Kit 100% Original');
        $other = $this->seller('Loja Diversa');
        $this->offer($other, $category, 'Fone Bluetooth');

        $this->get(route('storefront.search', ['q' => 'Estação']))
            ->assertOk()->assertSee('Kit 100% Original')->assertDontSee('Fone Bluetooth');
        $this->get(route('storefront.search', ['q' => '%']))
            ->assertOk()->assertSee('Kit 100% Original')->assertDontSee('Fone Bluetooth');
    }

    public function test_category_seller_and_price_filters_use_real_catalog(): void
    {
        $beauty = $this->category('Beleza', 'beleza');
        $tech = $this->category('Tecnologia', 'tecnologia');
        $a = $this->seller('Loja A');
        $b = $this->seller('Loja B');
        $this->offer($a, $beauty, 'Perfume A', 18050);
        $this->offer($b, $beauty, 'Perfume B', 19050);
        $this->offer($a, $tech, 'Mouse A', 18050);

        $this->get(route('storefront.search', [
            'category' => 'beleza', 'seller' => $a->id,
            'min_price' => '180,50', 'max_price' => '180.50',
        ]))->assertOk()
            ->assertSee('Perfume A')->assertDontSee('Perfume B')->assertDontSee('Mouse A')
            ->assertViewHas('offers', fn ($offers) => $offers->total() === 1);

        $this->get(route('storefront.category', $beauty).'?seller='.$b->id)
            ->assertOk()->assertSee('Perfume B')->assertDontSee('Perfume A');
    }

    public function test_price_sorting_and_browse_pagination_preserve_filters(): void
    {
        $category = $this->category('Papelaria', 'papelaria');
        $seller = $this->seller('Papel e Cia');
        $this->offer($seller, $category, 'Caderno barato', 1000);
        $this->offer($seller, $category, 'Caderno caro', 5000);

        $this->get(route('storefront.search', ['q' => 'Caderno', 'sort' => 'price_desc']))
            ->assertOk()->assertSeeInOrder(['Caderno caro', 'Caderno barato']);

        for ($i = 0; $i < 11; $i++) {
            $this->offer($seller, $category, 'Caderno '.($i + 1), 1200 + $i);
        }

        $this->get(route('storefront.search', ['q' => 'Caderno']))
            ->assertOk()
            ->assertViewHas('offers', fn ($offers) => $offers->total() === 13 && $offers->count() === 12)
            ->assertSee('q=Caderno')
            ->assertSee('page=2');

        $this->get(route('storefront.search', ['q' => 'Caderno', 'page' => 2]))
            ->assertOk()
            ->assertViewHas('offers', fn ($offers) => $offers->count() === 1);
    }

    public function test_invalid_filter_data_is_rejected_and_category_must_be_active(): void
    {
        $inactive = $this->category('Oculto', 'oculto', false);

        $this->get(route('storefront.search', ['q' => str_repeat('x', 101)]))
            ->assertSessionHasErrors('q');
        $this->get(route('storefront.search', ['min_price' => '200,00', 'max_price' => '100,00']))
            ->assertSessionHasErrors('max_price');
        $this->get(route('storefront.search', ['sort' => 'arbitrary']))
            ->assertSessionHasErrors('sort');
        $this->get(route('storefront.category', $inactive))->assertNotFound();
        $this->get(route('storefront.search', ['category' => $inactive->slug]))->assertNotFound();
        $this->get(route('home'))->assertDontSee('href="'.route('storefront.category', $inactive).'"', false);
    }

    public function test_product_detail_preserves_checkout_block_and_public_seller_info(): void
    {
        $category = $this->category('Tecnologia', 'tecnologia');
        $seller = $this->seller('Loja Horizonte');
        $offer = $this->offer($seller, $category, 'Caixa de som', 14590);

        $this->get(route('storefront.offer', $offer))
            ->assertOk()
            ->assertSee('Loja Horizonte')
            ->assertSee('Caixa de som')
            ->assertSee('145,90')
            ->assertSee('checkout está desativado')
            ->assertDontSee('Finalizar compra');
    }

    private function category(string $name, string $slug, bool $active = true): Category
    {
        return Category::query()->create([
            'name' => $name, 'slug' => $slug, 'is_active' => $active, 'position' => 1,
        ]);
    }

    private function seller(string $name, string $status = 'active'): Seller
    {
        static $sellerSequence = 0;
        $sellerSequence++;
        $owner = User::factory()->create();

        return Seller::query()->create([
            'owner_user_id' => $owner->id, 'legal_name' => $name.' LTDA',
            'trade_name' => $name, 'cnpj' => sprintf('%014d', $sellerSequence),
            'contact_email' => $owner->email, 'status' => $status,
        ]);
    }

    private function offer(Seller $seller, Category $category, string $name, int $price = 12990): SellerOffer
    {
        $product = Product::query()->create([
            'category_id' => $category->id, 'created_by_seller_id' => $seller->id,
            'name' => $name, 'slug' => strtolower((string) Str::ulid()),
            'description' => 'Descrição de homologação.', 'review_status' => 'approved',
        ]);
        $offer = SellerOffer::query()->create([
            'seller_id' => $seller->id, 'product_id' => $product->id,
            'sku' => 'SKU-'.Str::ulid(), 'price_cents' => $price,
            'review_status' => 'approved',
        ]);
        StockLevel::query()->create([
            'offer_id' => $offer->id, 'seller_id' => $seller->id,
            'quantity_on_hand' => 8, 'quantity_reserved' => 0,
        ]);

        return $offer;
    }
}
