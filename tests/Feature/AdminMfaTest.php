<?php

namespace Tests\Feature;

use App\Domain\Identity\Totp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminMfaTest extends TestCase
{
    use RefreshDatabase;

    public function test_totp_follows_rfc6238_sha1_vector_and_has_time_window(): void
    {
        $totp = app(Totp::class);
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertSame('287082', $totp->code($secret, 1));
        $this->assertSame(1, $totp->verify($secret, '287082', 59)['step']);
        $this->assertNull($totp->verify($secret, '287082', 300));
    }

    public function test_admin_needs_enrollment_and_confirmed_second_factor_for_both_portals(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.sellers.index'))
            ->assertRedirect(route('security.mfa.show'));
        $this->actingAs($admin)->getJson(route('bff.admin.index'))->assertForbidden()
            ->assertJsonPath('code', 'mfa_enrollment_required');

        $this->actingAs($admin)->postJson(route('bff.auth.mfa.enroll'), [
            'current_password' => 'password',
        ])->assertOk()->assertJsonStructure(['data' => ['secret', 'uri']]);

        $secret = $admin->fresh()->mfa_pending_secret;
        $this->assertNotSame($secret, DB::table('users')->where('id', $admin->id)->value('mfa_pending_secret'));
        $code = app(Totp::class)->code($secret, intdiv(now()->timestamp, 30));

        $response = $this->actingAs($admin)->postJson(route('bff.auth.mfa.confirm'), [
            'code' => $code,
        ])->assertOk()->assertJsonPath('data.verified', true)
            ->assertJsonCount(8, 'data.recovery_codes');

        $recovery = $response->json('data.recovery_codes.0');
        $this->assertFalse(in_array($recovery, $admin->fresh()->mfa_recovery_codes, true));
        $this->assertTrue(Hash::check($recovery, $admin->fresh()->mfa_recovery_codes[0]));

        $this->actingAs($admin)->getJson(route('bff.admin.index'))->assertOk()
            ->assertJsonPath('data.features.checkout_enabled', false);
        $this->actingAs($admin)->get(route('admin.sellers.index'))->assertOk();

        $this->withSession(['admin_mfa_user_id' => null])
            ->getJson(route('bff.admin.index'))->assertForbidden()
            ->assertJsonPath('code', 'mfa_challenge_required');

        $this->travel(31)->seconds();
        $newCode = app(Totp::class)->code($secret, intdiv(now()->timestamp, 30));
        $this->actingAs($admin)->postJson(route('bff.auth.mfa.challenge'), [
            'code' => $newCode,
        ])->assertOk()->assertJsonPath('data.verified', true);

        $this->withSession(['admin_mfa_user_id' => null])
            ->postJson(route('bff.auth.mfa.challenge'), ['code' => $newCode])
            ->assertUnprocessable()->assertJsonValidationErrors('code');

        $this->postJson(route('bff.auth.mfa.challenge'), [
            'recovery_code' => $recovery,
        ])->assertOk()->assertJsonPath('data.verified', true);
        $this->withSession(['admin_mfa_user_id' => null])
            ->postJson(route('bff.auth.mfa.challenge'), ['recovery_code' => $recovery])
            ->assertUnprocessable()->assertJsonValidationErrors('recovery_code');
    }

    public function test_customer_cannot_enroll_as_admin_or_use_forged_session_flag(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['admin_mfa_user_id' => $user->id])
            ->getJson(route('bff.admin.index'))->assertForbidden();

        $this->actingAs($user)->postJson(route('bff.auth.mfa.enroll'), [
            'current_password' => 'password',
        ])->assertForbidden();
        $this->get(route('security.mfa.show'))->assertForbidden();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['platform_role' => 'admin'])->save();

        return $user->fresh();
    }
}
