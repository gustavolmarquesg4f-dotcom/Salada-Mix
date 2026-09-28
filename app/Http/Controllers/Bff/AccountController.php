<?php

namespace App\Http\Controllers\Bff;

use App\Domain\Identity\SessionManager;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(['data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified' => $user->hasVerifiedEmail(),
            'platform_role' => $user->platform_role,
            'checkout_enabled' => false,
            'password_login_enabled' => (bool) $user->password_login_enabled,
            'sso' => $user->socialIdentities()->orderBy('provider')->get(['provider', 'provider_email', 'last_login_at'])
                ->map(fn ($identity) => [
                    'provider' => $identity->provider,
                    'email' => $identity->provider_email,
                    'last_login_at' => $identity->last_login_at?->toIso8601String(),
                ])->values(),
        ]]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($request->exists('email')) {
            $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        }
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'email' => ['sometimes', 'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['nullable', 'string'],
        ]);

        if (isset($data['email']) && Str::lower(trim($data['email'])) !== $user->email) {
            if (! $this->recentAuthenticationSatisfied($request, $data['current_password'] ?? null)) {
                throw ValidationException::withMessages(['current_password' => 'Confirme sua senha ou autentique-se novamente pelo SSO.']);
            }

            $user->forceFill([
                'email' => Str::lower(trim($data['email'])),
                'email_verified_at' => null,
                'remember_token' => Str::random(60),
            ]);
            $user->save();
            $user->sendEmailVerificationNotification();
        }

        if (isset($data['name'])) {
            $user->forceFill(['name' => $data['name']])->save();
        }

        AuditLog::query()->create([
            'actor_user_id' => $user->id,
            'action' => 'identity.account.updated',
            'metadata' => ['email_reverification_required' => ! $user->hasVerifiedEmail()],
        ]);

        return $this->show($request);
    }

    public function password(Request $request, SessionManager $sessions): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Senha atual incorreta.']);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
            'password_login_enabled' => true,
        ])->save();

        $sessions->revokeOtherSessions($user, $request->session()->getId());
        $request->session()->regenerate();

        AuditLog::query()->create([
            'actor_user_id' => $user->id,
            'action' => 'identity.password.changed',
            'metadata' => ['other_sessions_revoked' => config('session.driver') === 'database'],
        ]);

        return response()->json(['message' => 'Senha alterada. Outras sessões foram encerradas quando gerenciadas pelo banco.']);
    }

    public function establishPassword(Request $request, SessionManager $sessions): JsonResponse
    {
        $user = $request->user();

        if ($user->password_login_enabled) {
            throw ValidationException::withMessages(['password' => 'Esta conta já possui login por senha habilitado.']);
        }

        if (! $this->recentAuthenticationSatisfied($request, null)) {
            throw ValidationException::withMessages(['sso' => 'Autentique-se novamente pelo SSO antes de definir uma senha.']);
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'password_login_enabled' => true,
            'remember_token' => Str::random(60),
        ])->save();
        $sessions->revokeOtherSessions($user, $request->session()->getId());
        $request->session()->regenerate();

        AuditLog::query()->create([
            'actor_user_id' => $user->id,
            'action' => 'identity.password.established',
            'metadata' => ['source' => 'sso_session'],
        ]);

        return response()->json(['message' => 'Senha criada. Agora você pode entrar com e-mail e senha.']);
    }

    public function sessions(Request $request, SessionManager $sessions): JsonResponse
    {
        return response()->json(['data' => $sessions->read($request)]);
    }

    public function revokeSession(Request $request, string $fingerprint, SessionManager $sessions): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['nullable', 'string'],
        ]);

        $removed = $sessions->revoke(
            $request,
            $fingerprint,
            $data['current_password'] ?? null,
            $this->recentSso($request)
        );

        if ($removed) {
            AuditLog::query()->create([
                'actor_user_id' => $request->user()->id,
                'action' => 'identity.session.revoked',
                'metadata' => ['fingerprint_prefix' => substr($fingerprint, 0, 12)],
            ]);
        }

        return response()->json(['data' => ['revoked' => $removed]]);
    }
    private function recentSso(Request $request): bool
    {
        $authenticatedAt = (int) $request->session()->get('sso_authenticated_at', 0);

        return $authenticatedAt > 0
            && (now()->timestamp - $authenticatedAt) <= (int) config('sso.recent_auth_seconds', 600);
    }

    private function recentAuthenticationSatisfied(Request $request, ?string $password): bool
    {
        $user = $request->user();

        return ($user->password_login_enabled && $password && Hash::check($password, $user->password))
            || $this->recentSso($request);
    }
}
