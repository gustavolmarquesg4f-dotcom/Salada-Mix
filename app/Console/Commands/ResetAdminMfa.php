<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResetAdminMfa extends Command
{
    protected $signature = 'platform:reset-admin-mfa {email} {--confirm : Confirmar recuperação de conta privilegiada}';

    protected $description = 'Redefine MFA de administrador por intervenção CLI autorizada e revoga sessões.';

    public function handle(): int
    {
        if (! $this->option('confirm')) {
            $this->error('Use --confirm. Esta operação exige acesso operacional ao servidor.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', Str::lower(trim($this->argument('email'))))
            ->where('platform_role', 'admin')->first();

        if (! $user) {
            $this->error('Conta administrativa não encontrada.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'mfa_pending_secret' => null,
                'mfa_secret' => null,
                'mfa_confirmed_at' => null,
                'mfa_last_used_step' => null,
                'mfa_recovery_codes' => null,
                'remember_token' => Str::random(60),
            ])->save();

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)->delete();
            }

            AuditLog::query()->create([
                'actor_user_id' => null,
                'action' => 'identity.mfa.reset_by_cli',
                'metadata' => [
                    'target_user_id' => $user->id,
                    'source' => 'restricted_cli',
                    'sessions_revoked' => config('session.driver') === 'database',
                ],
            ]);
        });

        $this->info('MFA redefinido. O administrador deve configurar outro autenticador no próximo acesso.');

        return self::SUCCESS;
    }
}
