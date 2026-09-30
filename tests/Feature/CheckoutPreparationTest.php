<?php

namespace Tests\Feature;

use App\Models\SellerOffer;
use App\Models\User;
use Database\Seeders\MarketplaceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutPreparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_verified_buyer_can_review_checkout_preparation(): void
    {
        $this->get(route('buyer.checkout.prepare'))->assertRedirect(route('login'));
        $this->getJson(route('bff.checkout.preparation'))->assertUnauthorized();

        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('buyer.checkout.prepare'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs(User::factory()->create())
            ->get(route('buyer.checkout.prepare'))
            ->assertOk()
            ->assertSee('Frete e entrega do seu mix')
            ->assertSee('Cadastre um endereço')
            ->assertSee('Pagamento real desativado');

        $this->getJson(route('bff.checkout.preparation'))->assertOk()
            ->assertJsonPath('readiness.can_place_order', false)
            ->assertJsonPath('readiness.shipping_quotes_ready', false)
            ->assertJsonPath('selected_address', null);
    }

    public function test_address_selection_is_owner_scoped_and_read_only(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);

        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $a = $this->address($alice, 'Casa', true);
        $b = $this->address($alice, 'Trabalho', false);
        $other = $this->address($bob, 'Privado de outra pessoa', true);

        $this->actingAs($alice)->getJson(route('bff.checkout.preparation'))->assertOk()
            ->assertJsonPath('selected_address.id', $a)
            ->assertJsonCount(2, 'addresses')
            ->assertDontSee('Privado de outra pessoa');

        $this->getJson(route('bff.checkout.preparation', ['address' => $b]))->assertOk()
            ->assertJsonPath('selected_address.id', $b)
            ->assertJsonPath('readiness.address_selected', true);

        $this->get(route('buyer.checkout.prepare', ['address' => $b]))->assertOk()
            ->assertSee('Trabalho')
            ->assertSee('Selecionado');

        $this->get(route('buyer.checkout.prepare', ['address' => $other]))->assertNotFound();
        $this->getJson(route('bff.checkout.preparation', ['address' => $other]))->assertNotFound();
        $this->getJson(route('bff.checkout.preparation', ['address' => 'invalid']))->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('stock_reservations', 0);
    }

    public function test_checkout_groups_sellers_and_indicates_origin_readiness_without_quotes(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        $buyer = User::factory()->create();
        $address = $this->address($buyer, 'Casa', true);

        $fone = SellerOffer::query()->whereHas('product',
            fn ($q) => $q->where('slug', 'demo-tech-fone'))->firstOrFail();
        $serum = SellerOffer::query()->whereHas('product',
            fn ($q) => $q->where('slug', 'demo-belle-serum'))->firstOrFail();

        $this->actingAs($buyer)
            ->putJson(route('bff.cart.put', ['offer' => $fone->id]), ['quantity' => 2])
            ->assertOk();
        $this->putJson(route('bff.cart.put', ['offer' => $serum->id]), ['quantity' => 1])
            ->assertOk();

        $this->getJson(route('bff.checkout.preparation'))->assertOk()
            ->assertJsonPath('selected_address.id', $address)
            ->assertJsonPath('preview.items_subtotal_cents', 30970)
            ->assertJsonCount(2, 'preview.groups')
            ->assertJsonPath('readiness.shipping_origins_ready', false)
            ->assertJsonPath('readiness.payment_ready', false)
            ->assertJsonPath('readiness.can_place_order', false)
            ->assertJsonPath('preview.grand_total_cents', null);

        $this->shippingOrigin($fone->seller_id);
        $this->shippingOrigin($serum->seller_id);
        $this->getJson(route('bff.checkout.preparation'))->assertOk()
            ->assertJsonPath('readiness.shipping_origins_ready', true)
            ->assertJsonPath('readiness.shipping_quotes_ready', false)
            ->assertJsonPath('readiness.can_place_order', false);

        $this->get(route('buyer.checkout.prepare'))->assertOk()
            ->assertSee('MixTech (DEMO)')
            ->assertSee('BelleStore (DEMO)')
            ->assertSee('309,70')
            ->assertSee('A transportadora e a cotação real ainda não estão integradas.')
            ->assertDontSee('Comprar agora');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('suborders', 0);
        $this->assertDatabaseCount('stock_reservations', 0);
        $this->assertSame(0, $fone->stock->fresh()->quantity_reserved);
    }

    public function test_hidden_cart_offer_never_leaks_private_data_through_preparation(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        $user = User::factory()->create();
        $offer = SellerOffer::query()->whereHas('product',
            fn ($q) => $q->where('slug', 'demo-tech-fone'))->firstOrFail();

        $this->actingAs($user)->putJson(route('bff.cart.put', ['offer' => $offer->id]), ['quantity' => 1])->assertOk();
        $offer->seller->update(['status' => 'suspended']);

        $this->getJson(route('bff.checkout.preparation'))->assertOk()
            ->assertJsonPath('preview.unavailable_items_count', 1)
            ->assertJsonPath('preview.items_subtotal_cents', 0)
            ->assertJsonPath('readiness.can_place_order', false)
            ->assertDontSee('MixTech (DEMO)')
            ->assertDontSee('Fone Bluetooth sem fio (DEMO)');

        $this->get(route('buyer.checkout.prepare'))->assertOk()
            ->assertSee('item(ns) indisponível(is)')
            ->assertDontSee('MixTech (DEMO)')
            ->assertDontSee('Fone Bluetooth sem fio (DEMO)');
    }

    private function address(User $user, string $label, bool $isDefault): string
    {
        $id = (string) Str::ulid();

        DB::table('customer_addresses')->insert([
            'id' => $id, 'user_id' => $user->id, 'label' => $label,
            'recipient_name' => 'Pessoa de Teste', 'phone' => null,
            'postal_code' => '70000000', 'street' => 'Rua Exemplo',
            'number' => '100', 'complement' => null, 'neighborhood' => 'Centro',
            'city' => 'Brasília', 'state' => 'DF', 'is_default' => $isDefault,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    private function shippingOrigin(string $sellerId): void
    {
        DB::table('shipping_origins')->insert([
            'id' => (string) Str::ulid(), 'seller_id' => $sellerId,
            'label' => 'Depósito DEMO', 'postal_code' => '70000000',
            'street' => 'Rua Técnica', 'number' => '12',
            'complement' => null, 'neighborhood' => 'Centro',
            'city' => 'Brasília', 'state' => 'DF',
            'is_default' => true, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
