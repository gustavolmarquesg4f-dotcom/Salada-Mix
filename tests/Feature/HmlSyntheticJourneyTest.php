<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MarketplaceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class HmlSyntheticJourneyTest extends TestCase
{
    use RefreshDatabase;

    private string $previousEnv;
    private string $previousUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->previousEnv = app()->environment();
        $this->previousUrl = (string) config('app.url');
        app()->detectEnvironment(fn (): string => 'staging');
        config([
            'app.url' => 'https://ivory-rook-276202.hostingersite.com',
            'marketplace.checkout_enabled' => false,
            'marketplace.order_drafts_enabled' => false,
            'marketplace.payments_provider' => 'none',
        ]);
    }

    protected function tearDown(): void
    {
        config([
            'app.url' => $this->previousUrl,
            'marketplace.checkout_enabled' => false,
            'marketplace.order_drafts_enabled' => false,
            'marketplace.payments_provider' => 'none',
        ]);
        app()->detectEnvironment(fn (): string => $this->previousEnv);
        parent::tearDown();
    }

    private function postWithToken(string $uri, array $data = [], bool $json = false): TestResponse
    {
        $token = Str::random(40);
        $this->withSession(['_token' => $token])->withHeader('X-CSRF-TOKEN', $token);

        return $json ? $this->postJson($uri, $data) : $this->post($uri, $data);
    }

    private function startBuyerWithProduct(int $quantity = 2): array
    {
        $this->seed(MarketplaceDemoSeeder::class);
        $this->postWithToken('/demo/comprador')->assertRedirect();
        $user = auth()->user();
        $offer = DB::table('seller_offers')->join('products', 'products.id', '=', 'seller_offers.product_id')
            ->where('products.slug', 'demo-tech-fone')
            ->first(['seller_offers.id', 'seller_offers.seller_id']);

        DB::table('cart_items')->insert([
            'user_id' => $user->id, 'offer_id' => $offer->id, 'quantity' => $quantity,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get('/demo/checkout')->assertOk()
            ->assertSee('Sandbox Econômico')
            ->assertSee('Sandbox Expresso')
            ->assertSee('Reservar e criar pedido SANDBOX');

        $quote = DB::table('demo_shipping_quotes')->where('user_id', $user->id)
            ->where('seller_id', $offer->seller_id)->orderBy('amount_cents')->first();
        $this->assertNotNull($quote);

        return [$user, $offer, $quote];
    }

    public function test_guest_can_explore_real_catalog_then_create_isolated_demo_buyer(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        $this->get('/demo/ofertas/tech-fone')->assertRedirect();
        $this->get('/demo')->assertOk()->assertSee('Entrar como comprador fictício');
        $this->postWithToken('/demo/comprador')->assertRedirect('/demo/checkout');
        $this->assertAuthenticated();
        $user = auth()->user();

        $this->assertSame('customer', $user->platform_role);
        $this->assertStringEndsWith('@saladamix-demo.test', $user->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('customer_addresses', ['user_id' => $user->id, 'postal_code' => '70000000']);
    }

    public function test_shipping_quote_reservation_payment_and_post_sale_use_real_hml_ledger_without_external_effects(): void
    {
        [$user, $offer, $quote] = $this->startBuyerWithProduct(2);
        $key = 'ORDER-'.str_repeat('a', 28);

        $payload = [
            'idempotency_key' => $key,
            'quotes' => [$offer->seller_id => $quote->id],
        ];
        $this->postWithToken('/demo/pedidos', $payload)->assertRedirect();
        $this->postWithToken('/demo/pedidos', $payload)->assertRedirect();

        $this->assertDatabaseCount('demo_orders', 1);
        $this->assertDatabaseCount('demo_order_shipments', 1);
        $this->assertDatabaseCount('demo_stock_reservations', 1);
        $this->assertDatabaseCount('orders', 0);

        $order = DB::table('demo_orders')->where('user_id', $user->id)->first();
        $this->assertNotNull($order->expires_at);
        $this->assertGreaterThan(0, (int) $order->shipping_total_cents);
        $this->assertSame(25980 + (int) $quote->amount_cents, (int) $order->grand_total_cents);
        $this->assertDatabaseHas('stock_levels', [
            'offer_id' => $offer->id, 'quantity_on_hand' => 16, 'quantity_reserved' => 2,
        ]);

        $this->get('/demo/pedidos/'.$order->id)->assertOk()
            ->assertSee('Pagamento sandbox')
            ->assertSee('Frete escolhido por loja')
            ->assertSee('Sandbox Econômico');

        $this->postWithToken('/demo/pedidos/'.$order->id.'/pagamento', [
            'outcome' => 'decline', 'idempotency_key' => 'PAY-'.str_repeat('b', 28),
        ])->assertRedirect();
        $this->assertDatabaseHas('demo_orders', [
            'id' => $order->id, 'status' => 'created_demo', 'payment_status' => 'declined_demo',
        ]);
        $this->assertDatabaseHas('stock_levels', [
            'offer_id' => $offer->id, 'quantity_on_hand' => 16, 'quantity_reserved' => 2,
        ]);

        $this->postWithToken('/demo/pedidos/'.$order->id.'/pagamento', [
            'outcome' => 'approve', 'idempotency_key' => 'PAY-'.str_repeat('c', 28),
        ])->assertRedirect();
        $this->assertDatabaseHas('demo_orders', [
            'id' => $order->id, 'status' => 'paid_demo', 'payment_status' => 'approved_demo',
        ]);
        $this->assertDatabaseHas('demo_stock_reservations', [
            'demo_order_id' => $order->id, 'offer_id' => $offer->id, 'status' => 'consumed',
        ]);
        $this->assertDatabaseHas('stock_levels', [
            'offer_id' => $offer->id, 'quantity_on_hand' => 14, 'quantity_reserved' => 0,
        ]);
        $this->assertDatabaseCount('demo_payment_attempts', 2);

        foreach (['prepare' => 'preparing_demo', 'ship' => 'shipped_demo', 'deliver' => 'delivered_demo'] as $action => $status) {
            $this->postWithToken('/demo/pedidos/'.$order->id.'/etapa', ['action' => $action])->assertRedirect();
            $this->assertDatabaseHas('demo_orders', ['id' => $order->id, 'status' => $status]);
        }

        $this->assertGreaterThanOrEqual(6, DB::table('demo_order_events')->where('demo_order_id', $order->id)->count());
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_cancelling_sandbox_order_releases_reserved_stock_without_reducing_on_hand(): void
    {
        [, $offer, $quote] = $this->startBuyerWithProduct(3);
        $this->postWithToken('/demo/pedidos', [
            'idempotency_key' => 'ORDER-'.str_repeat('d', 28),
            'quotes' => [$offer->seller_id => $quote->id],
        ])->assertRedirect();

        $order = DB::table('demo_orders')->first();
        $this->assertDatabaseHas('stock_levels', [
            'offer_id' => $offer->id, 'quantity_on_hand' => 16, 'quantity_reserved' => 3,
        ]);

        $this->postWithToken('/demo/pedidos/'.$order->id.'/etapa', ['action' => 'cancel'])->assertRedirect();
        $this->assertDatabaseHas('demo_orders', ['id' => $order->id, 'status' => 'cancelled_demo']);
        $this->assertDatabaseHas('demo_stock_reservations', [
            'demo_order_id' => $order->id, 'status' => 'released',
        ]);
        $this->assertDatabaseHas('stock_levels', [
            'offer_id' => $offer->id, 'quantity_on_hand' => 16, 'quantity_reserved' => 0,
        ]);
    }

    public function test_real_authenticated_account_cannot_be_replaced_or_use_synthetic_purchase(): void
    {
        $real = User::factory()->create();
        $this->actingAs($real);
        $this->postWithToken('/demo/comprador')->assertForbidden();
        $this->get('/demo/checkout')->assertForbidden();
        $this->assertSame($real->id, auth()->id());
    }

    public function test_demo_endpoints_are_not_available_outside_safe_staging_or_with_commercial_flags(): void
    {
        app()->detectEnvironment(fn (): string => 'production');
        $this->get('/demo/ofertas/tech-fone')->assertNotFound();
        $this->postWithToken('/demo/comprador')->assertNotFound();

        app()->detectEnvironment(fn (): string => 'staging');
        config(['marketplace.checkout_enabled' => true]);
        $this->postWithToken('/demo/comprador')->assertNotFound();
    }
}
