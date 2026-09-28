<?php

namespace App\Domain\Identity;

use App\Models\AuditLog;
use App\Models\SocialIdentity;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as ProviderUser;

class SsoManager
{
    public function enabledProviders(): array
    {
        return collect(config('sso.providers', []))
            ->filter(fn (array $config) => (bool) ($config['enabled'] ?? false))
            ->map(fn (array $config, string $provider) => [
                'id' => $provider,
                'label' => (string) ($config['label'] ?? Str::headline($provider)),
                'login_url' => route('sso.redirect', $provider),
            ])->values()->all();
    }

    public function providerConfig(string $provider): array
    {
        $config = config("sso.providers.{$provider}");

        abort_unless(is_array($config) && ($config['enabled'] ?? false), 404);

        return $config;
    }

    public function resolve(
        string $provider,
        ProviderUser $profile,
        ?User $currentUser = null
    ): array {
        $config = $this->providerConfig($provider);
        $providerId = trim((string) $profile->getId());
        $email = Str::lower(trim((string) $profile->getEmail()));
        $name = trim((string) ($profile->getName() ?: $profile->getNickname() ?: $email));
        $avatar = trim((string) ($profile->getAvatar() ?? ''));

        if ($providerId === '') {
            throw ValidationException::withMessages(['sso' => 'O provedor não retornou um identificador de conta válido.']);
        }

        $raw = method_exists($profile, 'getRaw') ? (array) $profile->getRaw() : [];
        $verifiedClaim = filter_var(
            $raw['email_verified'] ?? $raw['verified_email'] ?? false,
            FILTER_VALIDATE_BOOL
        );
        $trustedVerifiedEmail = (bool) ($config['trust_verified_email_claim'] ?? false)
            && $verifiedClaim
            && $email !== '';

        $result = DB::transaction(function () use (
            $provider, $providerId, $email, $name, $avatar, $trustedVerifiedEmail, $currentUser
        ): array {
            $identity = SocialIdentity::query()
                ->where('provider', $provider)
                ->where('provider_user_id', $providerId)
                ->lockForUpdate()
                ->first();

            if ($currentUser) {
                if ($identity && $identity->user_id !== $currentUser->id) {
                    throw ValidationException::withMessages([
                        'sso' => 'Esta conta do provedor já está conectada a outro usuário.',
                    ]);
                }

                $identity ??= new SocialIdentity([
                    'user_id' => $currentUser->id,
                    'provider' => $provider,
                    'provider_user_id' => $providerId,
                ]);
                $wasNew = ! $identity->exists;
                $identity->forceFill([
                    'provider_email' => $email ?: null,
                    'avatar_url' => $avatar ?: null,
                    'last_login_at' => now(),
                ])->save();

                AuditLog::query()->create([
                    'actor_user_id' => $currentUser->id,
                    'action' => $wasNew ? 'identity.sso.linked' : 'identity.sso.refreshed',
                    'metadata' => ['provider' => $provider],
                ]);

                return ['user' => $currentUser, 'created' => false, 'linked' => $wasNew];
            }

            if ($identity) {
                $identity->forceFill([
                    'provider_email' => $email ?: $identity->provider_email,
                    'avatar_url' => $avatar ?: $identity->avatar_url,
                    'last_login_at' => now(),
                ])->save();

                AuditLog::query()->create([
                    'actor_user_id' => $identity->user_id,
                    'action' => 'identity.sso.login',
                    'metadata' => ['provider' => $provider],
                ]);

                return ['user' => $identity->user()->firstOrFail(), 'created' => false, 'linked' => false];
            }

            if ($email === '') {
                throw ValidationException::withMessages([
                    'sso' => 'O provedor não disponibilizou um e-mail. Autorize o acesso ao e-mail ou use outro método de entrada.',
                ]);
            }

            $user = User::query()->where('email', $email)->lockForUpdate()->first();
            $created = false;

            if ($user && ! $trustedVerifiedEmail) {
                throw ValidationException::withMessages([
                    'sso' => 'Já existe uma conta com este e-mail. Entre com a senha e conecte este provedor pela sua conta.',
                ]);
            }

            if (! $user) {
                $user = User::query()->create([
                    'name' => $name !== '' ? $name : Str::before($email, '@'),
                    'email' => $email,
                    'email_verified_at' => $trustedVerifiedEmail ? now() : null,
                    'password' => Hash::make(Str::random(64)),
                    'password_login_enabled' => false,
                ]);
                $created = true;
            }

            $identity = SocialIdentity::query()->create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_user_id' => $providerId,
                'provider_email' => $email,
                'avatar_url' => $avatar ?: null,
                'last_login_at' => now(),
            ]);

            AuditLog::query()->create([
                'actor_user_id' => $user->id,
                'action' => $created ? 'identity.sso.registered' : 'identity.sso.linked_by_verified_email',
                'metadata' => ['provider' => $provider],
            ]);

            return ['user' => $user, 'created' => $created, 'linked' => true];
        });

        if ($result['created']) {
            event(new Registered($result['user']));
        }

        return $result;
    }

    public function unlink(User $user, string $provider, ?string $currentPassword, bool $recentSso): void
    {
        $this->providerConfig($provider);
        $identity = $user->socialIdentities()->where('provider', $provider)->firstOrFail();

        if ($user->password_login_enabled) {
            if (! $currentPassword || ! Hash::check($currentPassword, $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'Senha atual incorreta.']);
            }
        } elseif ($user->socialIdentities()->count() < 2 || ! $recentSso) {
            throw ValidationException::withMessages([
                'sso' => 'Defina uma senha ou mantenha outro provedor conectado antes de remover este acesso.',
            ]);
        }

        $identity->delete();

        AuditLog::query()->create([
            'actor_user_id' => $user->id,
            'action' => 'identity.sso.unlinked',
            'metadata' => ['provider' => $provider],
        ]);
    }
}

