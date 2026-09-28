<?php

namespace Tests\Feature;

use App\Models\SocialIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SsoPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'sso.providers.google.enabled' => true,
            'sso.providers.github.enabled' => true,
            'services.google.client_id' => 'google-client',
            'services.google.client_secret' => 'google-secret',
            'services.github.client_id' => 'github-client',
            'services.github.client_secret' => 'github-secret',
        ]);
    }

    public function test_google_redirect_uses_stateful_socialite_flow(): void
    {
        Socialite::fake('google');

        $this->get(route('sso.redirect', 'google'))
            ->assertRedirect()
            ->assertSessionHas('sso.provider', 'google')
            ->assertSessionHas('sso.intent', 'login');
    }

    public function test_verified_google_identity_creates_verified_sso_only_account_without_storing_tokens(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Cliente Google',
            'email' => 'cliente@example.test',
            'email_verified' => true,
            'token' => 'must-not-be-persisted',
            'refreshToken' => 'must-not-be-persisted-either',
        ]));

        $response = $this->withSession([
            'sso.provider' => 'google',
            'sso.intent' => 'login',
        ])->get(route('sso.callback', 'google'));

        $response->assertRedirect(route('home', absolute: false));
        $user = User::query()->where('email', 'cliente@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertFalse($user->password_login_enabled);
        $this->assertDatabaseHas('social_identities', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-123',
        ]);

        $columns = array_keys(SocialIdentity::query()->firstOrFail()->getAttributes());
        $this->assertNotContains('token', $columns);
        $this->assertNotContains('refresh_token', $columns);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'action' => 'identity.sso.registered',
        ]);
    }

    public function test_verified_google_email_can_safely_link_existing_local_account(): void
    {
        $user = User::factory()->create(['email' => 'same@example.test']);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-existing',
            'email' => 'same@example.test',
            'name' => 'Same User',
            'email_verified' => true,
        ]));

        $this->withSession(['sso.provider' => 'google', 'sso.intent' => 'login'])
            ->get(route('sso.callback', 'google'))
            ->assertRedirect(route('home', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('social_identities', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-existing',
        ]);
    }

    public function test_github_never_auto_links_existing_account_only_by_email(): void
    {
        User::factory()->create(['email' => 'existing@example.test']);
        Socialite::fake('github', SocialiteUser::fake([
            'id' => 'github-123',
            'email' => 'existing@example.test',
            'name' => 'Existing',
            'verified_email' => true,
        ]));

        $this->withSession(['sso.provider' => 'github', 'sso.intent' => 'login'])
            ->get(route('sso.callback', 'github'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('sso');

        $this->assertGuest();
        $this->assertDatabaseCount('social_identities', 0);
    }

    public function test_authenticated_user_can_link_provider_but_provider_cannot_be_stolen(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        SocialIdentity::query()->create([
            'user_id' => $other->id,
            'provider' => 'google',
            'provider_user_id' => 'occupied',
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-new',
            'email' => 'different@example.test',
            'email_verified' => true,
        ]));

        $this->actingAs($owner)
            ->withSession(['sso.provider' => 'google', 'sso.intent' => 'link'])
            ->get(route('sso.callback', 'google'))
            ->assertRedirect(route('buyer.account'));

        $this->assertDatabaseHas('social_identities', [
            'user_id' => $owner->id,
            'provider_user_id' => 'google-new',
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'occupied',
            'email' => 'different@example.test',
            'email_verified' => true,
        ]));

        $this->actingAs($owner)
            ->withSession(['sso.provider' => 'google', 'sso.intent' => 'link'])
            ->get(route('sso.callback', 'google'))
            ->assertSessionHasErrors('sso');

        $this->assertDatabaseHas('social_identities', [
            'user_id' => $other->id,
            'provider_user_id' => 'occupied',
        ]);
    }

    public function test_sso_only_user_can_establish_password_after_recent_sso_and_then_unlink(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('unknown-random'),
            'password_login_enabled' => false,
        ]);
        SocialIdentity::query()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'g-1',
        ]);

        $this->actingAs($user)
            ->withSession(['sso_authenticated_at' => now()->timestamp])
            ->putJson(route('bff.auth.password.establish'), [
                'password' => 'NovaSenha-Forte123!',
                'password_confirmation' => 'NovaSenha-Forte123!',
            ])->assertOk();

        $user->refresh();
        $this->assertTrue($user->password_login_enabled);
        $this->assertTrue(Hash::check('NovaSenha-Forte123!', $user->password));

        $this->actingAs($user)->deleteJson(route('bff.auth.sso.unlink', 'google'), [
            'current_password' => 'NovaSenha-Forte123!',
        ])->assertOk()->assertJsonPath('data.connected', false);

        $this->assertDatabaseCount('social_identities', 0);
    }

    public function test_sso_only_user_cannot_remove_last_login_method(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password_login_enabled' => false,
        ]);
        SocialIdentity::query()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'only-method',
        ]);

        $this->actingAs($user)
            ->withSession(['sso_authenticated_at' => now()->timestamp])
            ->deleteJson(route('bff.auth.sso.unlink', 'google'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sso');
    }

    public function test_bff_meta_and_public_api_include_server_generated_request_id(): void
    {
        $meta = $this->getJson(route('bff.meta'))->assertOk()
            ->assertJsonPath('data.api_version', 1)
            ->assertJsonPath('data.auth.session_cookie', true)
            ->assertJsonCount(2, 'data.auth.sso_providers');

        $this->assertNotEmpty($meta->headers->get('X-Request-Id'));
        $this->assertSame('1', $meta->headers->get('X-API-Version'));

        $api = $this->getJson(route('api.catalog.categories'))->assertOk();
        $this->assertNotEmpty($api->headers->get('X-Request-Id'));
    }
}

