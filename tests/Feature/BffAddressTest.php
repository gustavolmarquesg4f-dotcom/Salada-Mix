<?php

namespace Tests\Feature;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BffAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_addresses_are_scoped_to_buyer_and_default_is_transferred_on_deletion(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $first = $this->actingAs($alice)->postJson(route('bff.addresses.store'), $this->payload('Casa'))
            ->assertCreated()->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.postal_code', '70000000')->json('data.id');
        $second = $this->actingAs($alice)->postJson(route('bff.addresses.store'),
            $this->payload('Trabalho', ['is_default' => true, 'user_id' => $bob->id]))
            ->assertCreated()->assertJsonPath('data.is_default', true)->json('data.id');

        $this->assertDatabaseHas('customer_addresses', ['id' => $first, 'user_id' => $alice->id, 'is_default' => false]);
        $this->assertDatabaseHas('customer_addresses', ['id' => $second, 'user_id' => $alice->id, 'is_default' => true]);
        $this->assertSame(1, CustomerAddress::query()->where('user_id', $alice->id)->where('is_default', true)->count());

        $this->actingAs($bob)->getJson(route('bff.addresses.index'))->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($bob)->patchJson(route('bff.addresses.update', $first), ['street' => 'Rua Outra'])->assertNotFound();
        $this->actingAs($bob)->deleteJson(route('bff.addresses.destroy', $first))->assertNotFound();

        $this->actingAs($alice)->deleteJson(route('bff.addresses.destroy', $second))->assertOk();
        $this->assertDatabaseHas('customer_addresses', ['id' => $first, 'is_default' => true]);
    }

    public function test_invalid_postal_code_is_rejected_and_shipping_remains_disabled(): void
    {
        $this->actingAs(User::factory()->create())->postJson(route('bff.addresses.store'),
            $this->payload('Casa', ['postal_code' => '1234']))
            ->assertUnprocessable()->assertJsonValidationErrors('postal_code');
        $this->assertDatabaseCount('customer_addresses', 0);
        $this->getJson(route('bff.storefront'))->assertJsonPath('data.features.shipping_quote_enabled', false);
    }

    private function payload(string $label, array $overrides = []): array
    {
        return array_merge([
            'label' => $label, 'recipient' => 'Cliente Teste',
            'postal_code' => '70000-000', 'street' => 'Rua Exemplo',
            'number' => '10', 'complement' => 'Apto 2', 'district' => 'Centro',
            'city' => 'Brasília', 'state' => 'df',
        ], $overrides);
    }
}
