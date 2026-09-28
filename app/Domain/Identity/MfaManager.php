<?php

namespace App\Domain\Identity;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MfaManager
{
    public function __construct(private readonly Totp $totp)
    {
    }

    public function enroll(User $user): array
    {
        if ($user->platform_role !== 'admin' || $user->mfa_confirmed_at) {
            abort(403);
        }

        $secret = $this->totp->newSecret();
        $user->forceFill(['mfa_pending_secret' => $secret])->save();

        $this->audit($user, 'identity.mfa.enrollment.started');

        return [
            'secret' => $secret,
            'uri' => $this->totp->provisioningUri($secret, $user->email),
        ];
    }

    /** @return array<int, string> Recovery codes shown only once. */
    public function confirm(User $user, string $code): array
    {
        if ($user->platform_role !== 'admin' || $user->mfa_confirmed_at || ! $user->mfa_pending_secret) {
            throw ValidationException::withMessages(['code' => 'Configure o autenticador antes de confirmar.']);
        }

        $result = $this->totp->verify($user->mfa_pending_secret, $code);

        if (! $result) {
            throw ValidationException::withMessages(['code' => 'Código inválido.']);
        }

        return DB::transaction(function () use ($user, $result): array {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($locked->mfa_confirmed_at || ! $locked->mfa_pending_secret
                || ! hash_equals($user->mfa_pending_secret, $locked->mfa_pending_secret)) {
                throw ValidationException::withMessages(['code' => 'Configuração já utilizada.']);
            }

            $codes = $this->recoveryCodes();
            $locked->forceFill([
                'mfa_secret' => $locked->mfa_pending_secret,
                'mfa_pending_secret' => null,
                'mfa_confirmed_at' => now(),
                'mfa_last_used_step' => $result['step'],
                'mfa_recovery_codes' => array_map(fn (string $value) => Hash::make($value), $codes),
            ])->save();

            $user->refresh();
            $this->audit($user, 'identity.mfa.enabled');

            return $codes;
        });
    }

    public function challenge(User $user, ?string $code, ?string $recoveryCode): void
    {
        if ($user->platform_role !== 'admin' || ! $user->mfa_confirmed_at || ! $user->mfa_secret) {
            abort(403);
        }

        DB::transaction(function () use ($user, $code, $recoveryCode): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($code) {
                $matched = $this->totp->verify($locked->mfa_secret, $code);

                if (! $matched || ($locked->mfa_last_used_step !== null
                    && $matched['step'] <= $locked->mfa_last_used_step)) {
                    throw ValidationException::withMessages(['code' => 'Código inválido ou já utilizado.']);
                }

                $locked->forceFill(['mfa_last_used_step' => $matched['step']])->save();
            } elseif ($recoveryCode) {
                $codes = $locked->mfa_recovery_codes ?? [];
                $index = null;
                $normalized = Str::upper(trim($recoveryCode));

                foreach ($codes as $key => $hash) {
                    if (Hash::check($normalized, $hash)) {
                        $index = $key;
                        break;
                    }
                }

                if ($index === null) {
                    throw ValidationException::withMessages(['recovery_code' => 'Código de recuperação inválido.']);
                }

                unset($codes[$index]);
                $locked->forceFill(['mfa_recovery_codes' => array_values($codes)])->save();
            } else {
                throw ValidationException::withMessages(['code' => 'Informe um código do autenticador ou de recuperação.']);
            }

            $this->audit($user, 'identity.mfa.challenge.passed');
        });
    }

    /** @return array<int, string> */
    public function regenerateRecoveryCodes(User $user, string $code): array
    {
        if ($user->platform_role !== 'admin' || ! $user->mfa_confirmed_at || ! $user->mfa_secret) {
            abort(403);
        }

        return DB::transaction(function () use ($user, $code): array {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $matched = $this->totp->verify($locked->mfa_secret, $code);

            if (! $matched || ($locked->mfa_last_used_step !== null
                && $matched['step'] <= $locked->mfa_last_used_step)) {
                throw ValidationException::withMessages(['code' => 'Código inválido ou já utilizado.']);
            }

            $codes = $this->recoveryCodes();
            $locked->forceFill([
                'mfa_last_used_step' => $matched['step'],
                'mfa_recovery_codes' => array_map(fn (string $value) => Hash::make($value), $codes),
            ])->save();

            $this->audit($user, 'identity.mfa.recovery.regenerated');

            return $codes;
        });
    }

    private function recoveryCodes(): array
    {
        return array_map(fn () => Str::upper(Str::random(16)), range(1, 8));
    }

    private function audit(User $user, string $action): void
    {
        AuditLog::query()->create([
            'actor_user_id' => $user->id,
            'action' => $action,
            'metadata' => ['method' => 'totp'],
        ]);
    }
}

