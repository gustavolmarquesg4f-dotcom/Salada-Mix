<?php

namespace Tests\Feature;

use App\Models\SellerOffer;
use App\Models\User;
use Database\Seeders\MarketplaceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendShoppingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_unverified_user_cannot_open_shopping_pages(): void
    {
        $this->get(route('buyer.cart.page'))->assertRedirect(route('login'));
        $this->get(route('buyer.wishlist.page'))->assertRedirect(route('login'));

        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get(route('buyer.cart.page'))
            ->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('buyer.wishlist.page'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_empty_cart_and_wishlist_have_safe_navigation(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('buyer.cart.page'))->assertOk()
            ->assertSee('Sua sacola está esperando seus achados')
            ->assertSee(route('buyer.wishlist.page'), false)
            ->assertDontSee('Comprar agora');
        $this->get(route('buyer.wishlist.page'))->assertOk()
            ->assertSee('Seus favoritos vão aparecer aqui')
            ->assertSee(route('buyer.cart.page'), false);
    }

    public function test_cart_groups_real_items_by_seller_without_reserving_stock(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        $user = User::factory()->create();
        $fone = SellerOffer::query()->whereHas('product', fn ($q) => $q->where('slug', 'demo-tech-fone'))->firstOrFail();
        $serum = SellerOffer::query()->whereHas('product', fn ($q) => $q->where('slug', 'demo-belle-serum'))->firstOrFail();

        $this->actingAs($user)
            ->putJson(route('bff.cart.put', ['offer' => $fone->id]), ['quantity' => 2])
            ->assertOk()->assertJsonPath('subtotal_cents', 25980);
        $this->actingAs($user)
            ->putJson(route('bff.cart.put', ['offer' => $serum->id]), ['quantity' => 1])
            ->assertOk()->assertJsonPath('subtotal_cents', 30970);

        $this->actingAs($user)->get(route('buyer.cart.page'))->assertOk()
            ->assertSee('MixTech (DEMO)')
            ->assertSee('BelleStore (DEMO)')
            ->assertSee('Fone Bluetooth sem fio (DEMO)')
            ->assertSee('Sérum facial vitamina C (DEMO)')
            ->assertSee('309,70')
            ->assertSee('sm-shopping-qty')
            ->assertSee('sm-shopping-thumb')
            ->assertSee(route('bff.cart.put', ['offer' => $fone->id]), false)
            ->assertSee('Finalizar compra indisponível')
            ->assertDontSee('Pagar agora');

        $this->assertSame(0, $fone->stock->fresh()->quantity_reserved);
        $this->assertSame(0, $serum->stock->fresh()->quantity_reserved);
    }

    public function test_ineligible_offer_is_not_exposed_on_cart_page(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        $user = User::factory()->create();
        $fone = SellerOffer::query()->whereHas('product', fn ($q) => $q->where('slug', 'demo-tech-fone'))->firstOrFail();
        $this->actingAs($user)->putJson(route('bff.cart.put', ['offer' => $fone->id]), ['quantity' => 1])->assertOk();
        $fone->seller->update(['status' => 'suspended']);

        $this->actingAs($user)->get(route('buyer.cart.page'))->assertOk()
            ->assertSee('Itens indisponíveis')
            ->assertSee('Oferta indisponível')
            ->assertDontSee('Fone Bluetooth sem fio (DEMO)')
            ->assertDontSee('MixTech (DEMO)')
            ->assertSee(route('bff.cart.destroy', ['offer' => $fone->id]), false);
    }

    public function test_favorites_use_live_price_and_isolate_another_customer(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $serum = SellerOffer::query()->whereHas('product', fn ($q) => $q->where('slug', 'demo-belle-serum'))->firstOrFail();

        $this->actingAs($alice)->putJson(route('bff.wishlist.put', ['offer' => $serum->id]))
            ->assertOk()->assertJsonCount(1, 'items');

        $this->actingAs($alice)->get(route('buyer.wishlist.page'))->assertOk()
            ->assertSee('Sérum facial vitamina C (DEMO)')
            ->assertSee('49,90')
            ->assertSee('sm-favorite-thumb')
            ->assertSee(route('bff.wishlist.destroy', ['offer' => $serum->id]), false);

        $this->actingAs($bob)->get(route('buyer.wishlist.page'))->assertOk()
            ->assertDontSee('Sérum facial vitamina C (DEMO)')
            ->assertSee('Seus favoritos vão aparecer aqui');
    }

    public function test_product_cards_and_detail_show_actions_without_turning_on_checkout(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        $user = User::factory()->create();
        $offer = SellerOffer::query()->whereHas('product', fn ($q) => $q->where('slug', 'demo-tech-fone'))->firstOrFail();

        $this->actingAs($user)->get(route('storefront.offer', $offer))->assertOk()
            ->assertSee('Adicionar à sacola')
            ->assertSee(route('bff.cart.put', ['offer' => $offer->id]), false)
            ->assertSee(route('bff.wishlist.put', ['offer' => $offer->id]), false)
            ->assertSee('O checkout está desativado');

        $this->get(route('storefront.search'))->assertOk()
            ->assertSee('data-sm-commerce="cart-add"', false);
        $this->assertFalse(config('marketplace.checkout_enabled'));
    }
}
