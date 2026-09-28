<?php

namespace App\Http\Controllers\Bff;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(['data' => [
            'authenticated' => (bool) $user,
            'email_verified' => $user?->hasVerifiedEmail() ?? false,
            'admin_mfa_required' => $user?->platform_role === 'admin',
            'admin_mfa_verified' => $user?->platform_role === 'admin'
                && (bool) $user->mfa_confirmed_at
                && (string) $request->session()->get('admin_mfa_user_id') === (string) $user->id,
            'user' => $user ? $this->publicUser($user) : null,
            'links' => [
                'login' => route('login'),
                'register' => route('register'),
                'verification' => $user && ! $user->hasVerifiedEmail() ? route('verification.notice') : null,
            ],
        ]]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => Str::lower(trim($data['email'])),
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));
        $user->refresh(); // load database defaults, including the unprivileged customer role
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['data' => [
            'user' => $this->publicUser($user),
            'email_verification_required' => true,
        ]], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => Str::lower(trim($data['email'])), 'password' => $data['password']])) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha inválidos.']);
        }

        $request->session()->regenerate();
        $user = $request->user();

        return response()->json(['data' => [
            'user' => $this->publicUser($user),
            'email_verification_required' => ! $user->hasVerifiedEmail(),
            'admin_mfa_required' => $user->platform_role === 'admin',
            'admin_mfa_enrollment_required' => $user->platform_role === 'admin' && ! $user->mfa_confirmed_at,
        ]]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['data' => ['authenticated' => false]]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return response()->json(['message' => 'Se necessário, uma nova mensagem de verificação será enviada.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        Password::sendResetLink(['email' => Str::lower(trim($data['email']))]);

        // Do not reveal whether the address belongs to an account.
        return response()->json(['message' => 'Se a conta existir, enviaremos instruções por e-mail.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $data['email'] = Str::lower(trim($data['email']));

        $status = Password::reset($data, function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return response()->json(['message' => 'Senha alterada. Entre novamente com suas credenciais.']);
    }

    private function publicUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'platform_role' => $user->platform_role,
        ];
    }
}
