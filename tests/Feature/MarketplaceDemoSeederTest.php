<?php

namespace Tests\Feature;

use App\Models\SellerOffer;
use Database\Seeders\MarketplaceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_is_idempotent_and_visible_through_real_catalog_query(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        $this->seed(MarketplaceDemoSeeder::class);

        $this->assertDatabaseCount('categories', 10);
        $this->assertDatabaseCount('sellers', 3);
        $this->assertDatabaseCount('products', 9);
        $this->assertDatabaseCount('seller_offers', 9);
        $this->assertDatabaseCount('stock_levels', 9);
        $this->assertDatabaseCount('users', 3);

        $this->get(route('home'))->assertOk()
            ->assertSee('Sérum facial vitamina C (DEMO)')
            ->assertSee('BelleStore (DEMO)');
        $this->getJson(route('api.catalog.offers.index', ['q' => 'vitamina C']))
            ->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.seller.name', 'BelleStore (DEMO)');

        $this->assertSame(0, SellerOffer::query()->where('review_status', 'pending')->count());
        $this->assertFalse(config('marketplace.checkout_enabled'));
    }

    public function test_demo_seeder_refuses_to_run_with_checkout_enabled(): void
    {
        config(['marketplace.checkout_enabled' => true]);

        $this->expectException(\RuntimeException::class);
        $this->seed(MarketplaceDemoSeeder::class);
    }

    public function test_default_seeder_does_not_create_demo_sellers(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->assertDatabaseCount('sellers', 0);
        $this->assertDatabaseCount('seller_offers', 0);
    }
    public function test_guarded_hostinger_staging_has_real_demo_catalog_and_images(): void
    {
        $previous = app()->environment();
        $url = config('app.url');

        try {
            app()->detectEnvironment(fn (): string => 'staging');
            config(['app.url' => 'https://ivory-rook-276202.hostingersite.com']);

            $this->seed(MarketplaceDemoSeeder::class);
            $this->seed(MarketplaceDemoSeeder::class);
            $this->assertDatabaseCount('sellers', 3);
            $this->assertDatabaseCount('seller_offers', 9);

            $this->get('/loja')->assertOk()
                ->assertSee('Fone Bluetooth sem fio (DEMO)')
                ->assertSee('sm-demo-image');
            $this->get('/buscar?q=vitamina')->assertOk()
                ->assertSee('Sérum facial vitamina C (DEMO)');
        } finally {
            app()->detectEnvironment(fn (): string => $previous);
            config(['app.url' => $url]);
        }
    }

    public function test_staging_demo_refuses_unrelated_host_or_existing_commercial_data(): void
    {
        $previous = app()->environment();
        $url = config('app.url');

        try {
            app()->detectEnvironment(fn (): string => 'staging');
            config(['app.url' => 'https://another-host.test']);
            try {
                $this->seed(MarketplaceDemoSeeder::class);
                $this->fail('Seeder must reject an unrelated host.');
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('Seeder DEMO exige', $e->getMessage());
            }

            config(['app.url' => 'https://ivory-rook-276202.hostingersite.com']);
            $this->seed(\Database\Seeders\CatalogCategorySeeder::class);
            \Illuminate\Support\Facades\DB::table('orders')->insert([
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'user_id' => \App\Models\User::factory()->create()->id,
                'status' => 'draft',
                'items_total_cents' => 0,
                'currency' => 'BRL',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->expectException(\RuntimeException::class);
            $this->seed(MarketplaceDemoSeeder::class);
        } finally {
            app()->detectEnvironment(fn (): string => $previous);
            config(['app.url' => $url]);
        }
    }

}
