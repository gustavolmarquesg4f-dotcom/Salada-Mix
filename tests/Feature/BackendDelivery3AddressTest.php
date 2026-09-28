<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerMembership;
use App\Models\SellerOffer;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BackendDelivery3AddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_addresses_require_verified_buyer_and_validate_cep_and_uf(): void
    {
        $user = User::factory()->create();
        $this->get(route('buyer.addresses.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('buyer.addresses.index'))->assertRedirect(route('verification.notice'));

        $this->actingAs($user)->post(route('buyer.addresses.store'), [
            ...$this->address(), 'postal_code' => 'ABC',
        ])->assertSessionHasErrors('postal_code');

        $this->actingAs($user)->post(route('buyer.addresses.store'), [
            ...$this->address(), 'state' => 'XX',
        ])->assertSessionHasErrors('state');

        $this->actingAs($user)->post(route('buyer.addresses.store'), $this->address())
            ->assertRedirect(route('buyer.addresses.index'));

        $this->assertDatabaseHas('customer_addresses', [
            'user_id' => $user->id, 'postal_code' => '70040900', 'state' => 'DF', 'is_default' => true,
        ]);
    }

    public function test_buyer_can_only_access_own_addresses_and_default_is_promoted_on_delete(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->actingAs($alice)->post(route('buyer.addresses.store'), $this->address())
            ->assertSessionHasNoErrors();
        $first = DB::table('customer_addresses')->where('user_id', $alice->id)->value('id');

        $this->actingAs($alice)->post(route('buyer.addresses.store'), [
            ...$this->address(), 'label' => 'Trabalho', 'is_default' => '1',
        ])->assertSessionHasNoErrors();
        $second = DB::table('customer_addresses')->where('user_id', $alice->id)
            ->where('id', '!=', $first)->value('id');

        $this->assertDatabaseHas('customer_addresses', ['id' => $first, 'is_default' => false]);
        $this->assertDatabaseHas('customer_addresses', ['id' => $second, 'is_default' => true]);

        $this->actingAs($bob)->get(route('buyer.addresses.index'))
            ->assertOk()->assertDontSee('Trabalho');
        $this->actingAs($bob)->delete(route('buyer.addresses.destroy', $second))->assertNotFound();
        $this->assertDatabaseHas('customer_addresses', ['id' => $second, 'user_id' => $alice->id]);

        $this->actingAs($alice)->delete(route('buyer.addresses.destroy', $second))
            ->assertRedirect(route('buyer.addresses.index'));
        $this->assertDatabaseHas('customer_addresses', ['id' => $first, 'is_default' => true]);
    }

    public function test_only_the_correct_seller_manager_can_write_shipping_origins(): void
    {
        $owner = User::factory()->create();
        $operator = User::factory()->create();
        $stranger = User::factory()->create();
        $sellerA = $this->seller('001');
        $sellerB = $this->seller('002');
        $this->member($sellerA, $owner, 'owner');
        $this->member($sellerA, $operator, 'operations');
        $this->member($sellerB, $owner, 'owner');

        $this->actingAs($stranger)->get(route('seller.origins.index', $sellerA))->assertForbidden();
        $this->actingAs($operator)->get(route('seller.origins.index', $sellerA))->assertOk();
        $this->actingAs($operator)->post(route('seller.origins.store', $sellerA), $this->origin())
            ->assertForbidden();

        $this->actingAs($owner)->post(route('seller.origins.store', $sellerA), $this->origin())
            ->assertRedirect(route('seller.origins.index', $sellerA));
        $originId = DB::table('shipping_origins')->where('seller_id', $sellerA->id)->value('id');
        $this->assertDatabaseHas('shipping_origins', [
            'id' => $originId, 'seller_id' => $sellerA->id, 'postal_code' => '70040900',
        ]);

        $this->actingAs($owner)->delete(route('seller.origins.destroy', [$sellerB, $originId]))
            ->assertNotFound();
        $this->assertDatabaseHas('shipping_origins', ['id' => $originId, 'seller_id' => $sellerA->id]);
    }

    public function test_checkout_preview_groups_real_items_but_never_quotes_or_charges(): void
    {
        $buyer = User::factory()->create();
        $a = $this->seller('003');
        $b = $this->seller('004');
        $offerA = $this->offer($a, 'Bolsa', 'bolsa-a', 9000);
        $offerB = $this->offer($b, 'Fone', 'fone-b', 12000);

        $this->actingAs($buyer)->putJson(route('buyer.cart.put', $offerA), ['quantity' => 2])->assertOk();
        $this->actingAs($buyer)->putJson(route('buyer.cart.put', $offerB), ['quantity' => 1])->assertOk();

        $preview = $this->actingAs($buyer)->getJson(route('buyer.checkout.preview'))
            ->assertOk()->assertJsonCount(2, 'groups')
            ->assertJsonPath('items_subtotal_cents', 30000)
            ->assertJsonPath('groups.0.shipping.status', 'origin_not_configured')
            ->assertJsonPath('groups.0.shipping.amount_cents', null)
            ->assertJsonPath('grand_total_cents', null)
            ->assertJsonPath('checkout_enabled', false);

        $this->assertSame(0, $preview->json('unavailable_items_count'));
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('suborders', 0);
        $this->assertDatabaseCount('stock_reservations', 0);

        DB::table('shipping_origins')->insert([
            ...$this->originRow($a->id),
        ]);

        $this->actingAs($buyer)->getJson(route('buyer.checkout.preview'))->assertOk()
            ->assertJsonPath('groups.0.shipping.status', 'provider_not_configured')
            ->assertJsonPath('checkout_enabled', false);
    }

    private function address(): array
    {
        return [
            'label' => 'Casa', 'recipient_name' => 'Cliente Teste',
            'phone' => '(61) 99999-9999', 'postal_code' => '70040-900',
            'street' => 'Rua de exemplo', 'number' => '10', 'complement' => 'Apto 1',
            'neighborhood' => 'Centro', 'city' => 'Brasília', 'state' => 'DF',
        ];
    }

    private function origin(): array
    {
        $data = $this->address();
        unset($data['recipient_name'], $data['phone']);

        return $data;
    }

    private function originRow(string $sellerId): array
    {
        return [
            'id' => (string) Str::ulid(), 'seller_id' => $sellerId,
            'label' => 'Origem', 'postal_code' => '70040900', 'street' => 'Rua',
            'number' => '10', 'neighborhood' => 'Centro', 'city' => 'Brasília',
            'state' => 'DF', 'is_default' => true, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ];
    }

    private function seller(string $suffix): Seller
    {
        $owner = User::factory()->create();

        return Seller::query()->create([
            'owner_user_id' => $owner->id,
            'legal_name' => 'Empresa '.$suffix,
            'trade_name' => 'Loja '.$suffix,
            'cnpj' => str_pad($suffix, 14, '0', STR_PAD_LEFT),
            'contact_email' => 'comercial@example.test',
            'status' => 'active',
        ]);
    }

    private function member(Seller $seller, User $user, string $role): void
    {
        SellerMembership::query()->create([
            'seller_id' => $seller->id, 'user_id' => $user->id,
            'role' => $role, 'status' => 'active',
        ]);
    }

    private function offer(Seller $seller, string $name, string $slug, int $price): SellerOffer
    {
        $category = Category::query()->firstOrCreate(['slug' => 'mix'], [
            'name' => 'Mix', 'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id, 'created_by_seller_id' => $seller->id,
            'name' => $name, 'slug' => $slug, 'review_status' => 'approved',
        ]);
        $offer = SellerOffer::query()->create([
            'seller_id' => $seller->id, 'product_id' => $product->id,
            'sku' => strtoupper($slug), 'price_cents' => $price,
            'currency' => 'BRL', 'review_status' => 'approved',
        ]);
        StockLevel::query()->create([
            'offer_id' => $offer->id, 'seller_id' => $seller->id,
            'quantity_on_hand' => 10, 'quantity_reserved' => 0,
        ]);

        return $offer;
    }
}
