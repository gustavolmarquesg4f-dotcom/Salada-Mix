<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Seller;
use App\Models\SellerMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BffSellerBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_application_is_owned_by_authenticated_verified_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('bff.sellers.store'), [
            'legal_name' => 'Loja Teste LTDA', 'trade_name' => 'Loja Teste',
            'cnpj' => '11222333000181', 'contact_email' => 'contato@example.test',
            'status' => 'active', 'owner_user_id' => 999999,
        ])->assertCreated()->assertJsonPath('data.status', 'submitted');

        $seller = Seller::query()->firstOrFail();
        $this->assertSame($user->id, $seller->owner_user_id);
        $this->assertSame('submitted', $seller->status);
        $this->actingAs($user)->getJson(route('bff.sellers.show', $seller))
            ->assertOk()->assertJsonPath('data.seller.id', $seller->id)
            ->assertJsonPath('data.capabilities.edit_catalog', false);
        $this->actingAs(User::factory()->create())->getJson(route('bff.sellers.show', $seller))
            ->assertForbidden();
    }

    public function test_offer_creation_does_not_trust_payload_seller_and_restricts_role(): void
    {
        $owner = User::factory()->create();
        $operator = User::factory()->create();
        $other = User::factory()->create();
        $seller = Seller::query()->create([
            'owner_user_id' => $owner->id, 'legal_name' => 'Exemplo Ltda',
            'trade_name' => 'Exemplo', 'cnpj' => '00000000000001',
            'contact_email' => 'seller@example.test', 'status' => 'approved',
        ]);
        foreach ([[$owner, 'owner'], [$operator, 'operations']] as [$user, $role]) {
            SellerMembership::query()->create([
                'seller_id' => $seller->id, 'user_id' => $user->id,
                'role' => $role, 'status' => 'active',
            ]);
        }

        $category = Category::query()->create([
            'name' => 'Beleza', 'slug' => 'beleza', 'is_active' => true,
        ]);
        $payload = [
            'category_id' => $category->id, 'name' => 'Produto fictício',
            'sku' => 'SKU-1', 'price_cents' => 4990, 'stock_quantity' => 3,
            'seller_id' => $other->id,
        ];

        $this->actingAs($other)->postJson(route('bff.sellers.offers.store', $seller), $payload)
            ->assertForbidden();
        $this->actingAs($operator)->postJson(route('bff.sellers.offers.store', $seller), $payload)
            ->assertForbidden();
        $this->actingAs($owner)->postJson(route('bff.sellers.offers.store', $seller), $payload)
            ->assertCreated()->assertJsonPath('data.review_status', 'pending')
            ->assertJsonPath('data.checkout_enabled', false);

        $this->assertDatabaseHas('seller_offers', ['seller_id' => $seller->id, 'price_cents' => 4990]);
        $this->assertDatabaseCount('seller_offers', 1);
    }
}
