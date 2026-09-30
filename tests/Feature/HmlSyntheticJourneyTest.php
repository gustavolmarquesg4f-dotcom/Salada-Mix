<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MarketplaceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HmlSyntheticJourneyTest extends TestCase
{
    use RefreshDatabase;

    private string $previousEnv;
    private string $previousUrl;

    protected function setUp(): void
    {
        parent::setUp();
        // Staging enables CSRF in Laravel; controller tests exercise authorization after bypassing this middleware.
        $this->withoutMiddleware();
        $this->previousEnv = app()->environment();
        $this->previousUrl = (string) config('app.url');
        app()->detectEnvironment(fn (): string => 'staging');
        config(['app.url' => 'https://ivory-rook-276202.hostingersite.com']);
    }

    protected function tearDown(): void
    {
        config(['app.url' => $this->previousUrl, 'marketplace.checkout_enabled' => false]);
        app()->detectEnvironment(fn (): string => $this->previousEnv);
        parent::tearDown();
    }

    public function test_guest_can_explore_real_catalog_then_create_isolated_demo_buyer(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        $this->get('/demo/ofertas/tech-fone')->assertRedirect();
        $this->get('/demo')->assertOk()->assertSee('Entrar como comprador fictício');
        $this->post('/demo/comprador')->assertRedirect('/demo/checkout');
        $this->assertAuthenticated();
        $user = auth()->user();
        $this->assertSame('customer', $user->platform_role);
        $this->assertStringEndsWith('@saladamix-demo.test', $user->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('customer_addresses', ['user_id' => $user->id, 'postal_code' => '70000000']);
        $this->get('/demo/checkout')->assertOk()->assertSee('pedido de demonstração');
    }

    public function test_demo_order_is_idempotent_and_runs_payment_shipping_and_post_sale_without_real_effects(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        $this->post('/demo/comprador')->assertRedirect();
        $user = auth()->user();
        $offer = DB::table('seller_offers')->join('products', 'products.id', '=', 'seller_offers.product_id')
            ->where('products.slug', 'demo-tech-fone')->first(['seller_offers.id']);
        DB::table('cart_items')->insert([
            'user_id' => $user->id, 'offer_id' => $offer->id, 'quantity' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $key = 'TEST-'.str_repeat('a', 30);
        $this->post('/demo/pedidos', ['idempotency_key' => $key])->assertRedirect();
        $this->post('/demo/pedidos', ['idempotency_key' => $key])->assertRedirect();
        $this->assertDatabaseCount('demo_orders', 1);
        $this->assertDatabaseCount('demo_order_items', 1);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('stock_levels', ['offer_id' => $offer->id, 'quantity_on_hand' => 16, 'quantity_reserved' => 0]);
        $record = DB::table('demo_orders')->first();
        $this->assertSame(25980, (int) $record->items_total_cents);
        $this->assertSame(990, (int) $record->shipping_total_cents);
        $this->get('/demo/pedidos/'.$record->id)->assertOk()->assertSee('Pedido fictício');

        foreach (['pay' => 'paid_demo', 'prepare' => 'preparing_demo', 'ship' => 'shipped_demo', 'deliver' => 'delivered_demo'] as $action => $status) {
            $this->post('/demo/pedidos/'.$record->id.'/etapa', ['action' => $action])->assertRedirect();
            $this->assertDatabaseHas('demo_orders', ['id' => $record->id, 'status' => $status]);
        }
        $this->assertDatabaseCount('demo_order_events', 5);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('stock_levels', ['offer_id' => $offer->id, 'quantity_on_hand' => 16, 'quantity_reserved' => 0]);
        $this->postJson('/demo/pedidos/'.$record->id.'/etapa', ['action' => 'pay'])->assertUnprocessable();
        $this->get('/demo/pedidos')->assertOk()->assertSee($record->id);
    }

    public function test_real_authenticated_account_cannot_be_replaced_or_use_synthetic_purchase(): void
    {
        $real = User::factory()->create();
        $this->actingAs($real);
        $this->post('/demo/comprador')->assertForbidden();
        $this->get('/demo/checkout')->assertForbidden();
        $this->assertSame($real->id, auth()->id());
    }

    public function test_demo_endpoints_are_not_available_outside_isolated_staging_or_with_commercial_flags(): void
    {
        app()->detectEnvironment(fn (): string => 'production');
        $this->get('/demo/ofertas/tech-fone')->assertNotFound();
        $this->post('/demo/comprador')->assertNotFound();
        app()->detectEnvironment(fn (): string => 'staging');
        config(['marketplace.checkout_enabled' => true]);
        $this->post('/demo/comprador')->assertNotFound();
    }
}
