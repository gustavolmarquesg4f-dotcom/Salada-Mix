<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerMembership;
use App\Models\SellerOffer;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Be05OrderReservationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['marketplace.order_drafts_enabled' => true, 'marketplace.checkout_enabled' => false]);
    }

    public function test_switch_off_does_not_create_orders_or_holds(): void
    {
        $buyer = User::factory()->create();
        $offer = $this->offer('Produto', 9000, 3, '01');
        [$address, $payload] = $this->prepare($buyer, $offer, 2);
        config(['marketplace.order_drafts_enabled' => false]);
        $this->actingAs($buyer)->postJson(route('bff.orders.drafts.store'), $payload)->assertStatus(503);
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(0, $offer->stock->fresh()->quantity_reserved);
    }

    public function test_order_groups_by_seller_snapshots_values_and_does_not_charge(): void
    {
        $buyer = User::factory()->create();
        $one = $this->offer('Bolsa', 9000, 3, '11');
        $two = $this->offer('Fone', 12000, 2, '12');
        [$address, $payload] = $this->prepare($buyer, $one, 2);
        $this->actingAs($buyer)->putJson(route('bff.cart.put', $two), ['quantity' => 1])->assertOk();
        $created = $this->actingAs($buyer)->postJson(route('bff.orders.drafts.store'), $payload)
            ->assertCreated()->assertJsonPath('data.status', 'reserved')
            ->assertJsonPath('data.items_total_cents', 30000)
            ->assertJsonPath('data.grand_total_cents', null)
            ->assertJsonPath('data.payments_enabled', false)
            ->assertJsonCount(2, 'data.suborders');
        $id = $created->json('data.id');
        $this->assertDatabaseCount('suborders', 2);
        $this->assertDatabaseCount('stock_reservations', 2);
        $this->assertDatabaseHas('stock_levels', ['offer_id' => $one->id, 'quantity_reserved' => 2]);
        $this->assertDatabaseHas('stock_levels', ['offer_id' => $two->id, 'quantity_reserved' => 1]);
        $this->actingAs($buyer)->postJson(route('bff.orders.drafts.store'), $payload)
            ->assertCreated()->assertJsonPath('data.id', $id);
        $this->assertDatabaseCount('orders', 1);
        $one->forceFill(['price_cents' => 30000])->save();
        $this->assertDatabaseHas('suborder_items', [
            'offer_id' => $one->id, 'unit_price_cents' => 9000, 'name_snapshot' => 'Bolsa',
        ]);
        $this->assertDatabaseHas('orders', ['id' => $id, 'shipping_total_cents' => null, 'grand_total_cents' => null]);
        $this->assertDatabaseHas('order_events', ['order_id' => $id, 'event_type' => 'order.draft.reserved']);
    }

    public function test_cancel_and_expire_are_idempotent_and_restore_stock(): void
    {
        $buyer = User::factory()->create();
        $offer = $this->offer('Mouse', 10000, 4, '13');
        [, $payload] = $this->prepare($buyer, $offer, 3);
        $id = $this->actingAs($buyer)->postJson(route('bff.orders.drafts.store'), $payload)->assertCreated()->json('data.id');
        $this->actingAs($buyer)->deleteJson(route('bff.orders.drafts.cancel', $id))->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
        $this->actingAs($buyer)->deleteJson(route('bff.orders.drafts.cancel', $id))->assertOk();
        $this->assertSame(0, $offer->stock->fresh()->quantity_reserved);
        $this->assertDatabaseHas('stock_reservations', ['offer_id' => $offer->id, 'status' => 'released']);
        $this->assertSame(1, DB::table('order_events')->where('order_id', $id)
            ->where('event_type', 'order.draft.cancelled')->count());
        $next = array_merge($payload, ['idempotency_key' => 'second-attempt-12345']);
        $second = $this->actingAs($buyer)->postJson(route('bff.orders.drafts.store'), $next)->assertCreated()->json('data.id');
        DB::table('orders')->where('id', $second)->update(['expires_at' => now()->subMinute()]);
        $this->artisan('marketplace:expire-reservations')->assertExitCode(0);
        $this->artisan('marketplace:expire-reservations')->assertExitCode(0);
        $this->assertDatabaseHas('orders', ['id' => $second, 'status' => 'expired']);
        $this->assertSame(0, $offer->stock->fresh()->quantity_reserved);
        $this->assertSame(1, DB::table('order_events')->where('order_id', $second)
            ->where('event_type', 'order.draft.expired')->count());
    }

    public function test_two_buyers_cannot_hold_the_same_units(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $offer = $this->offer('Último', 10000, 2, '14');
        [, $aPayload] = $this->prepare($a, $offer, 2);
        [$addr, $bPayload] = $this->prepare($b, $offer, 2);
        $this->actingAs($a)->postJson(route('bff.orders.drafts.store'), $aPayload)->assertCreated();
        $this->actingAs($b)->postJson(route('bff.orders.drafts.store'), $bPayload)
            ->assertUnprocessable()->assertJsonValidationErrors('cart');
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(2, $offer->stock->fresh()->quantity_reserved);
    }

    public function test_second_idempotency_key_cannot_create_another_active_hold(): void
    {
        $buyer = User::factory()->create();
        $offer = $this->offer('Controle', 10000, 4, '15');
        [, $payload] = $this->prepare($buyer, $offer, 1);
        $this->actingAs($buyer)->postJson(route('bff.orders.drafts.store'), $payload)->assertCreated();
        $this->actingAs($buyer)->postJson(route('bff.orders.drafts.store'), array_merge($payload, [
            'idempotency_key' => 'another-key-123456789',
        ]))->assertUnprocessable()->assertJsonValidationErrors('draft');
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_owner_only_access_and_seller_cannot_see_buyer_address(): void
    {
        $buyer = User::factory()->create();
        $stranger = User::factory()->create();
        $offer = $this->offer('Notebook', 30000, 3, '16');
        [, $payload] = $this->prepare($buyer, $offer, 1);
        $id = $this->actingAs($buyer)->postJson(route('bff.orders.drafts.store'), $payload)->assertCreated()->json('data.id');
        $this->actingAs($stranger)->getJson(route('bff.orders.drafts.show', $id))->assertNotFound();
        $this->actingAs($stranger)->deleteJson(route('bff.orders.drafts.cancel', $id))->assertNotFound();
        SellerMembership::query()->create([
            'seller_id' => $offer->seller_id, 'user_id' => $stranger->id,
            'role' => 'operations', 'status' => 'active',
        ]);
        $res = $this->actingAs($stranger)->getJson(route('bff.sellers.orders.reservations', $offer->seller))
            ->assertOk()->assertJsonCount(1, 'data');
        $this->assertStringNotContainsString('recipient_name', $res->getContent());
        $this->assertStringNotContainsString('postal_code', $res->getContent());
        $this->actingAs($buyer)->getJson(route('bff.orders.drafts.show', $id))->assertOk()
            ->assertJsonPath('data.suborders.0.delivery_address.recipient_name', 'Cliente Exemplo');
    }

    public function test_unverified_or_foreign_address_is_rejected_without_reserving(): void
    {
        $buyer = User::factory()->create();
        $foreign = User::factory()->create();
        $offer = $this->offer('Teclado', 6000, 3, '17');
        [, $payload] = $this->prepare($buyer, $offer, 1);
        $foreignAddress = $this->address($foreign);
        $payload['address_id'] = $foreignAddress->id;
        $this->actingAs($buyer)->postJson(route('bff.orders.drafts.store'), $payload)->assertNotFound();
        $this->actingAs(User::factory()->unverified()->create())
            ->postJson(route('bff.orders.drafts.store'), $payload)->assertForbidden();
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(0, $offer->stock->fresh()->quantity_reserved);
    }

    private function prepare(User $buyer, SellerOffer $offer, int $qty): array
    {
        $address = $this->address($buyer);
        $this->actingAs($buyer)->putJson(route('bff.cart.put', $offer), ['quantity' => $qty])->assertOk();
        return [$address, ['address_id' => $address->id, 'idempotency_key' => 'request-'.$buyer->id.'-12345678']];
    }

    private function address(User $buyer): CustomerAddress
    {
        return CustomerAddress::query()->create([
            'user_id' => $buyer->id, 'label' => 'Casa',
            'recipient_name' => 'Cliente Exemplo', 'postal_code' => '70040900',
            'street' => 'Rua Exemplo', 'number' => '10', 'neighborhood' => 'Centro',
            'city' => 'Brasília', 'state' => 'DF', 'is_default' => true,
        ]);
    }

    private function offer(string $name, int $price, int $stock, string $suffix): SellerOffer
    {
        $seller = Seller::query()->create([
            'owner_user_id' => User::factory()->create()->id,
            'legal_name' => 'Empresa '.$suffix, 'trade_name' => 'Loja '.$suffix,
            'cnpj' => str_pad($suffix, 14, '0', STR_PAD_LEFT),
            'contact_email' => 'loja'.$suffix.'@example.test', 'status' => 'active',
        ]);
        $category = Category::query()->create([
            'name' => 'Categoria '.$suffix, 'slug' => 'categoria-'.$suffix, 'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id, 'created_by_seller_id' => $seller->id,
            'name' => $name, 'slug' => 'produto-'.$suffix,
            'description' => 'Teste', 'review_status' => 'approved',
        ]);
        $offer = SellerOffer::query()->create([
            'seller_id' => $seller->id, 'product_id' => $product->id,
            'sku' => 'SKU-'.$suffix, 'price_cents' => $price,
            'currency' => 'BRL', 'review_status' => 'approved',
        ]);
        StockLevel::query()->create([
            'offer_id' => $offer->id, 'seller_id' => $seller->id,
            'quantity_on_hand' => $stock, 'quantity_reserved' => 0,
        ]);
        return $offer;
    }
}
