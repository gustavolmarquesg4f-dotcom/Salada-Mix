<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendBuyerAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_authentication_screens_use_salada_mix_brand_and_real_web_routes(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('Entrar na minha conta')
            ->assertSee('sm-auth-layout')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee(route('password.request'), false);

        $this->get(route('register'))->assertOk()
            ->assertSee('Crie sua conta')
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('name="_token"', false)
            ->assertDontSee('Frete grátis');

        $this->get(route('password.request'))->assertOk()
            ->assertSee('Esqueceu sua senha?');
        $this->get(route('password.reset', ['token' => 'test-token', 'email' => 'cliente@example.test']))
            ->assertOk()->assertSee('Defina uma nova senha');
    }

    public function test_sso_buttons_are_only_present_for_enabled_providers(): void
    {
        config(['sso.providers.google.enabled' => true, 'sso.providers.github.enabled' => false]);
        $this->get(route('login'))->assertOk()
            ->assertSee('Continuar com Google')
            ->assertDontSee('Continuar com GitHub');
    }

    public function test_account_uses_existing_bff_contracts_without_creating_commerce(): void
    {
        $user = User::factory()->create(['name' => 'Cliente Exemplo', 'password_login_enabled' => true]);
        $this->actingAs($user)->get(route('buyer.account'))->assertOk()
            ->assertSee('Cliente Exemplo')
            ->assertSee('sm-account-layout')
            ->assertSee(route('bff.account.update'), false)
            ->assertSee(route('bff.account.password'), false)
            ->assertSee(route('bff.account.sessions'), false)
            ->assertSee('pagamento real ainda não está habilitado', false)
            ->assertDontSee('Comprar agora');
    }

    public function test_sso_only_account_shows_password_creation_instead_of_password_change(): void
    {
        $user = User::factory()->create(['password_login_enabled' => false]);
        $this->actingAs($user)->get(route('buyer.account'))->assertOk()
            ->assertSee('Criar senha para esta conta')
            ->assertSee(route('bff.auth.password.establish'), false);
    }

    public function test_addresses_and_checkout_preview_preserve_authenticated_boundaries(): void
    {
        $this->get(route('buyer.addresses.index'))->assertRedirect(route('login'));
        $this->get(route('buyer.checkout.preview'))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('buyer.addresses.index'))->assertOk()
            ->assertSee('Meus endereços')
            ->assertSee('name="postal_code"', false)
            ->assertSee(route('buyer.addresses.store'), false)
            ->assertSee('checkout está desativado');

        $this->actingAs($user)->get(route('buyer.checkout.preview'))->assertOk()
            ->assertSee('Resumo da sacola')
            ->assertSee('Pagamento indisponível nesta etapa')
            ->assertDontSee('Comprar agora');
    }

    public function test_unverified_account_cannot_open_customer_account(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get(route('buyer.account'))
            ->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('verification.notice'))
            ->assertOk()->assertSee('Confirme seu e-mail');
    }
}
