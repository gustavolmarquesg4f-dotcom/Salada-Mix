<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_home_contains_brand_navigation_and_explicit_commercial_status(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Salada Mix', false)
            ->assertSee('Pular para o conteúdo')
            ->assertSee('Departamentos')
            ->assertSee('PLATAFORMA EM PREPARAÇÃO')
            ->assertSee('salada-mix-logo.svg')
            ->assertDontSee('name="price_cents"', false);
    }

    public function test_login_uses_shared_layout_without_claiming_sales_are_open(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Salada Mix')
            ->assertSee('PLATAFORMA EM PREPARAÇÃO')
            ->assertSee('Entrar na minha conta');
    }

    public function test_signed_in_customer_has_account_link_and_cannot_see_admin_links(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('Minha conta')
            ->assertDontSee('Moderação');
    }
}
