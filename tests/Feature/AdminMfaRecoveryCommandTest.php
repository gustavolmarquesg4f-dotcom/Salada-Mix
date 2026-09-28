<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMfaRecoveryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_mfa_reset_is_restricted_to_confirmed_cli_and_audited(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill([
            'platform_role' => 'admin',
            'mfa_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'mfa_confirmed_at' => now(),
            'mfa_last_used_step' => 1,
            'mfa_recovery_codes' => ['hashed-once'],
        ])->save();

        $this->artisan('platform:reset-admin-mfa', ['email' => $admin->email])
            ->assertExitCode(1);
        $this->assertNotNull($admin->fresh()->mfa_secret);

        $this->artisan('platform:reset-admin-mfa', [
            'email' => $admin->email, '--confirm' => true,
        ])->assertExitCode(0);

        $fresh = $admin->fresh();
        $this->assertNull($fresh->mfa_secret);
        $this->assertNull($fresh->mfa_confirmed_at);
        $this->assertNull($fresh->mfa_recovery_codes);
        $this->assertDatabaseHas('audit_logs', ['action' => 'identity.mfa.reset_by_cli']);

        $customer = User::factory()->create();
        $this->artisan('platform:reset-admin-mfa', [
            'email' => $customer->email, '--confirm' => true,
        ])->assertExitCode(1);
    }
}
