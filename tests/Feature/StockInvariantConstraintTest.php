<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerOffer;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockInvariantConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_relational_database_rejects_reservations_larger_than_physical_stock(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Native CHECK constraint is installed on MySQL and MariaDB.');
        }

        $seller = Seller::query()->create([
            'owner_user_id' => User::factory()->create()->id,
            'legal_name' => 'Estoque Teste Ltda',
            'trade_name' => 'Loja Estoque',
            'cnpj' => '11111111111111',
            'contact_email' => 'estoque@example.test',
            'status' => 'active',
        ]);
        $category = Category::query()->create([
            'name' => 'Teste integridade',
            'slug' => 'teste-integridade',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'created_by_seller_id' => $seller->id,
            'name' => 'Produto de integridade',
            'slug' => 'produto-integridade',
            'description' => 'Teste da restricao SQL.',
            'review_status' => 'approved',
        ]);
        $offer = SellerOffer::query()->create([
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'sku' => 'STOCK-CHECK-001',
            'price_cents' => 10000,
            'currency' => 'BRL',
            'review_status' => 'approved',
        ]);
        StockLevel::query()->create([
            'seller_id' => $seller->id,
            'offer_id' => $offer->id,
            'quantity_on_hand' => 1,
            'quantity_reserved' => 0,
        ]);

        try {
            DB::table('stock_levels')->where('offer_id', $offer->id)
                ->update(['quantity_reserved' => 2]);
            $this->fail('Database accepted an invalid stock reservation.');
        } catch (QueryException $exception) {
            // Expected: CHECK constraint rejects the invalid UPDATE.
        }

        $this->assertDatabaseHas('stock_levels', [
            'offer_id' => $offer->id,
            'quantity_on_hand' => 1,
            'quantity_reserved' => 0,
        ]);
    }
}
