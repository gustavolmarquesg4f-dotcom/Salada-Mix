<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_home_contains_brand_navigation_and_marketplace_actions(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Salada Mix', false)
            ->assertSee('Pular para o conteúdo')
            ->assertSee('Departamentos')
            ->assertSee('Quero vender')
            ->assertSee('salada-mix-logo.svg')
            ->assertSee('sm-home-v3', false)
            ->assertSee('sm-category-rail-v3', false)
            ->assertDontSee('Frete grátis')
            ->assertDontSee('name="price_cents"', false);
    }

    public function test_login_uses_shared_brand_layout_without_inventing_commercial_promises(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Salada Mix')
            ->assertSee('Entrar na minha conta')
            ->assertSee('Quero vender')
            ->assertDontSee('Frete grátis');
    }

    public function test_signed_in_customer_has_account_link_and_cannot_see_admin_links(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('Minha conta')
            ->assertDontSee('Administração');
    }
}
