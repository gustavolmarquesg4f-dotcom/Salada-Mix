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

class BffAdminModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_moderation_uses_second_factor_and_never_activates_commerce(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['platform_role' => 'admin', 'mfa_confirmed_at' => now()])->save();

        $seller = Seller::query()->create([
            'owner_user_id' => $owner->id, 'legal_name' => 'Empresa Exemplo',
            'trade_name' => 'Loja Exemplo', 'cnpj' => '00000000000001',
            'contact_email' => 'seller@example.test', 'status' => 'submitted',
        ]);

        $this->actingAs($owner)->getJson(route('bff.admin.sellers.index'))->assertForbidden();
        $this->actingAs($admin)->getJson(route('bff.admin.sellers.index'))
            ->assertForbidden()->assertJsonPath('code', 'mfa_challenge_required');

        $this->actingAs($admin)->withSession(['admin_mfa_user_id' => $admin->id])
            ->getJson(route('bff.admin.sellers.index'))->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->postJson(route('bff.admin.sellers.decide', $seller), [
            'decision' => 'approved',
        ])->assertOk()->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.checkout_enabled', false);

        $this->assertDatabaseHas('seller_reviews', [
            'seller_id' => $seller->id, 'decision' => 'approved',
        ]);
        $this->assertSame('approved', $seller->fresh()->status);

        $category = Category::query()->create([
            'name' => 'Tecnologia', 'slug' => 'tecnologia', 'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id, 'created_by_seller_id' => $seller->id,
            'name' => 'Produto fictício', 'slug' => 'produto-ficticio',
            'review_status' => 'pending',
        ]);
        $offer = SellerOffer::query()->create([
            'seller_id' => $seller->id, 'product_id' => $product->id,
            'sku' => 'TEST', 'price_cents' => 1000, 'review_status' => 'pending',
        ]);
        StockLevel::query()->create([
            'seller_id' => $seller->id, 'offer_id' => $offer->id,
            'quantity_on_hand' => 2, 'quantity_reserved' => 0,
        ]);

        $this->getJson(route('bff.admin.offers.index'))->assertOk()
            ->assertJsonPath('meta.total', 1);
        $this->postJson(route('bff.admin.offers.decide', $offer), [
            'decision' => 'approved',
        ])->assertOk()->assertJsonPath('data.review_status', 'approved')
            ->assertJsonPath('data.checkout_enabled', false);

        $this->assertSame('approved', $offer->fresh()->review_status);
        $this->assertSame('approved', $product->fresh()->review_status);
        $this->assertSame('approved', $seller->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'catalog.offer.approved']);
    }
}
