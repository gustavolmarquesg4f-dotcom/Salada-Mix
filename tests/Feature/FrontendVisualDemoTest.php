<?php
namespace Tests\Feature;
use App\Models\SellerOffer;
use App\Support\DemoMedia;
use Database\Seeders\MarketplaceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class FrontendVisualDemoTest extends TestCase {
    use RefreshDatabase;
    public function test_local_demo_products_have_images_in_real_laravel_views(): void {
        $this->seed(MarketplaceDemoSeeder::class);
        $offer = SellerOffer::query()->whereHas('product', fn ($q) => $q->where('slug', 'demo-tech-fone'))->firstOrFail();
        $this->assertTrue(DemoMedia::enabled());
        $this->assertStringContainsString('images.unsplash.com', DemoMedia::product($offer));
        $this->get(route('home'))->assertOk()
            ->assertSee('HOMOLOGAÇÃO VISUAL + FUNCIONAL')
            ->assertSee('images.unsplash.com')
            ->assertSee('Sérum facial vitamina C (DEMO)')
            ->assertSee('Conteúdo sintético de homologação')
            ->assertSee('sm-home-v2', false)
            ->assertDontSee('Comprar agora');
        $this->get(route('storefront.search'))->assertOk()->assertSee('images.unsplash.com')->assertSee('sm-demo-image');
        $this->get(route('storefront.offer', $offer))->assertOk()->assertSee('sm-detail-image')->assertSee('checkout está desativado');
    }
    public function test_non_demo_offers_do_not_receive_unrelated_stock_photography(): void {
        $this->seed(MarketplaceDemoSeeder::class);
        $offer = SellerOffer::query()->firstOrFail();
        $offer->product->slug = 'real-seller-product';
        $this->assertNull(DemoMedia::product($offer));
        config(['marketplace.checkout_enabled' => true]);
        $offer->product->slug = 'demo-tech-fone';
        $this->assertFalse(DemoMedia::enabled());
        $this->assertNull(DemoMedia::product($offer));
        $this->assertNull(DemoMedia::banner('hero'));
    }
}
