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
            ->assertSee('Fone Bluetooth sem fio (DEMO)')
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
}

