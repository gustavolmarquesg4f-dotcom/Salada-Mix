<?php

namespace Tests\Feature;

use App\Domain\Seller\Actions\SubmitSellerApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_CNPJ = '11222333000181';

    public function test_unauthenticated_visitors_cannot_submit_a_company(): void
    {
        $this->post(route('seller.submit'), [])->assertRedirect(route('login'));
    }

    public function test_verified_customer_can_submit_a_company_but_cannot_sell_yet(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->post(route('seller.submit'), $this->form())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sellers', [
            'cnpj' => self::VALID_CNPJ,
            'status' => 'submitted',
            'owner_user_id' => $owner->id,
        ]);
        $this->assertDatabaseHas('seller_memberships', [
            'user_id' => $owner->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'seller.application.submitted']);
    }

    public function test_other_company_user_cannot_access_private_seller_dashboard(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $seller = app(SubmitSellerApplication::class)->execute($owner, $this->form());

        $this->actingAs($other)->get(route('seller.dashboard', $seller))->assertForbidden();
        $this->actingAs($owner)->get(route('seller.dashboard', $seller))->assertOk();
    }

    public function test_invalid_cnpj_is_rejected_before_persistence(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->post(route('seller.submit'), $this->form('11111111111111'))
            ->assertSessionHasErrors('cnpj');

        $this->assertDatabaseCount('sellers', 0);
    }

    public function test_duplicate_cnpj_cannot_create_another_company(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        app(SubmitSellerApplication::class)->execute($first, $this->form());

        $this->actingAs($second)->post(route('seller.submit'), $this->form())
            ->assertSessionHasErrors('cnpj');

        $this->assertDatabaseCount('sellers', 1);
    }


    public function test_alphanumeric_cnpj_is_accepted_and_normalized(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->post(route('seller.submit'), $this->form('00.000.000/E08G-12'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sellers', ['cnpj' => '00000000E08G12', 'status' => 'submitted']);
    }

    private function form(string $cnpj = self::VALID_CNPJ): array
    {
        return [
            'legal_name' => 'Loja de Exemplo Ltda',
            'trade_name' => 'Loja de Exemplo',
            'cnpj' => $cnpj,
            'contact_email' => 'comercial@example.test',
        ];
    }
}
