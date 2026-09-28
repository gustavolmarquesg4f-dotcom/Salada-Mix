<?php

namespace Tests\Feature;

use App\Domain\Seller\Actions\SubmitSellerApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSellerApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_platform_approvals(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.sellers.index'))->assertForbidden();
    }

    public function test_admin_approves_company_with_audit_but_does_not_enable_sales(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['platform_role' => 'admin'])->save();

        $seller = app(SubmitSellerApplication::class)->execute($owner, [
            'legal_name' => 'Vendedor Exemplo LTDA',
            'trade_name' => 'Exemplo',
            'cnpj' => '11222333000181',
            'contact_email' => 'venda@example.test',
        ]);

        $this->actingAs($admin)->post(route('admin.sellers.approve', $seller))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sellers', [
            'id' => $seller->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('seller_reviews', [
            'seller_id' => $seller->id,
            'actor_user_id' => $admin->id,
            'decision' => 'approved',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'seller.application.approved']);
        $this->assertFalse(config('marketplace.checkout_enabled'));
    }

    public function test_repeated_approval_is_not_permitted(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['platform_role' => 'admin'])->save();
        $seller = app(SubmitSellerApplication::class)->execute($owner, [
            'legal_name' => 'Vendedor Exemplo LTDA',
            'trade_name' => 'Exemplo',
            'cnpj' => '11222333000181',
            'contact_email' => 'venda@example.test',
        ]);

        $this->actingAs($admin)->post(route('admin.sellers.approve', $seller))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.sellers.approve', $seller))->assertSessionHasErrors('seller');
        $this->assertDatabaseCount('seller_reviews', 1);
    }
}

