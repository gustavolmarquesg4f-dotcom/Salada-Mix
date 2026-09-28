<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BffIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_bootstrap_and_guest_isolation(): void
    {
        $this->seed(\Database\Seeders\CatalogCategorySeeder::class);
        $this->getJson(route('bff.storefront'))->assertOk()
            ->assertJsonCount(10, 'data.categories')
            ->assertJsonPath('data.features.checkout_enabled', false)
            ->assertJsonPath('data.features.shipping_quote_enabled', false)
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->getJson(route('bff.auth.me'))->assertOk()
            ->assertJsonPath('data.authenticated', false);
        $this->getJson(route('bff.buyer'))->assertUnauthorized();
        $this->getJson(route('bff.cart.index'))->assertUnauthorized();
        $this->getJson(route('bff.admin.index'))->assertUnauthorized();
    }

    public function test_registration_and_verification_boundary(): void
    {
        Notification::fake();
        $this->postJson(route('bff.auth.register'), [
            'name' => 'Comprador de teste',
            'email' => 'Cliente@Example.Test',
            'password' => 'SenhaForte2026!',
            'password_confirmation' => 'SenhaForte2026!',
            'platform_role' => 'admin',
        ])->assertCreated()
            ->assertJsonPath('data.user.email', 'cliente@example.test')
            ->assertJsonPath('data.user.platform_role', 'customer')
            ->assertJsonPath('data.email_verification_required', true);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'cliente@example.test', 'platform_role' => 'customer',
            'email_verified_at' => null,
        ]);
        $this->getJson(route('bff.buyer'))->assertForbidden();
    }

    public function test_session_login_logout_and_password_management(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'account@example.test']);
        $this->postJson(route('bff.auth.login'), [
            'email' => 'account@example.test', 'password' => 'invalid',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();

        $this->postJson(route('bff.auth.login'), [
            'email' => 'ACCOUNT@example.test', 'password' => 'password',
        ])->assertOk()->assertJsonPath('data.user.id', $user->id);
        $this->getJson(route('bff.buyer'))->assertOk()
            ->assertJsonPath('data.cart.checkout_enabled', false);

        $this->patchJson(route('bff.account.update'), [
            'name' => 'Novo nome', 'platform_role' => 'admin',
        ])->assertOk()->assertJsonPath('data.name', 'Novo nome');
        $this->assertSame('customer', $user->fresh()->platform_role);

        $this->putJson(route('bff.account.password'), [
            'current_password' => 'invalid',
            'password' => 'SenhaNova2026!',
            'password_confirmation' => 'SenhaNova2026!',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->putJson(route('bff.account.password'), [
            'current_password' => 'password',
            'password' => 'SenhaNova2026!',
            'password_confirmation' => 'SenhaNova2026!',
        ])->assertOk();
        $this->assertTrue(Hash::check('SenhaNova2026!', $user->fresh()->password));

        $this->patchJson(route('bff.account.update'), [
            'email' => 'NEW@Example.Test', 'current_password' => 'SenhaNova2026!',
        ])->assertOk()->assertJsonPath('data.email', 'new@example.test')
            ->assertJsonPath('data.email_verified', false);

        $this->postJson(route('bff.auth.logout'))->assertOk()
            ->assertJsonPath('data.authenticated', false);
        $this->assertGuest();
    }

    public function test_reset_request_does_not_enumerate_accounts(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'known@example.test']);
        $a = $this->postJson(route('bff.auth.forgot'), ['email' => 'known@example.test'])
            ->assertOk()->json('message');
        $b = $this->postJson(route('bff.auth.forgot'), ['email' => 'missing@example.test'])
            ->assertOk()->json('message');
        $this->assertSame($a, $b);
    }

    public function test_session_listing_requires_database_driver(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('bff.account.sessions'))->assertStatus(409);
    }
}
