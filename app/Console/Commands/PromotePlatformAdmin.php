<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PromotePlatformAdmin extends Command
{
    protected $signature = 'platform:promote-admin {email} {--confirm : Confirmar concessão do papel de administração}';

    protected $description = 'Promove uma conta verificada a administradora (somente via acesso CLI ao servidor).';

    public function handle(): int
    {
        if (! $this->option('confirm')) {
            $this->error('Use --confirm para registrar explicitamente esta mudança privilegiada.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user || ! $user->hasVerifiedEmail()) {
            $this->error('Usuário inexistente ou e-mail não verificado.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($user): void {
            $user->forceFill(['platform_role' => 'admin'])->save();
            AuditLog::query()->create([
                'actor_user_id' => null,
                'action' => 'platform.admin.promoted_by_cli',
                'metadata' => ['target_user_id' => $user->id, 'source' => 'restricted_cli'],
            ]);
        });

        $this->info('Permissão aplicada. MFA administrativo continua bloqueador de produção.');

        return self::SUCCESS;
    }
}

