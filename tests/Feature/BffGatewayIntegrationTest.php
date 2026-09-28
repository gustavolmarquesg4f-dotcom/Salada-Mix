<?php

namespace Tests\Feature;

use App\Models\Seller;
use App\Models\SellerMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BffGatewayIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_checkout_is_read_only_and_cannot_initiate_payment(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('bff.checkout.preview'))->assertOk()
            ->assertJsonPath('data.checkout_enabled', false)
            ->assertJsonPath('data.grand_total_cents', null)
            ->assertJsonCount(0, 'data.groups');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('suborders', 0);
        $this->assertDatabaseCount('stock_reservations', 0);
    }

    public function test_shipping_origins_are_readable_only_by_active_member_of_that_seller(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $seller = Seller::query()->create([
            'owner_user_id' => $owner->id,
            'legal_name' => 'Loja Exemplo',
            'trade_name' => 'Loja Exemplo',
            'cnpj' => '00000000000001',
            'contact_email' => 'loja@example.test',
            'status' => 'approved',
        ]);

        SellerMembership::query()->create([
            'seller_id' => $seller->id, 'user_id' => $owner->id,
            'role' => 'owner', 'status' => 'active',
        ]);

        $originId = (string) Str::ulid();
        DB::table('shipping_origins')->insert([
            'id' => $originId,
            'seller_id' => $seller->id,
            'label' => 'Depósito',
            'postal_code' => '70000000',
            'street' => 'Rua Exemplo',
            'number' => '10',
            'neighborhood' => 'Centro',
            'city' => 'Brasília',
            'state' => 'DF',
            'is_default' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)->getJson(route('bff.sellers.origins.index', $seller))
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $originId);

        $this->actingAs($other)->getJson(route('bff.sellers.origins.index', $seller))
            ->assertForbidden();
    }
}
