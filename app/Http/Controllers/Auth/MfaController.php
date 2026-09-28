<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\MfaManager;
use App\Domain\Identity\Totp;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MfaController extends Controller
{
    public function show(Request $request): View
    {
        abort_unless($request->user()->platform_role === 'admin', 403);

        return view('auth.mfa', [
            'configured' => (bool) $request->user()->mfa_confirmed_at,
            'verified' => $this->isVerified($request),
            'setupSecret' => session('mfa_setup_secret'),
            'setupUri' => session('mfa_setup_uri'),
            'recoveryCodes' => session('mfa_recovery_codes'),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        abort_unless($request->user()->platform_role === 'admin', 403);

        return response()->json(['data' => [
            'configured' => (bool) $request->user()->mfa_confirmed_at,
            'verified' => $this->isVerified($request),
            'setup_url' => route('security.mfa.show'),
        ]]);
    }

    public function begin(Request $request, MfaManager $manager): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'string']]);
        $this->requirePassword($request, $data['current_password']);
        $setup = $manager->enroll($request->user());

        if ($request->expectsJson()) {
            return response()->json(['data' => $setup]);
        }

        return redirect()->route('security.mfa.show')->with([
            'mfa_setup_secret' => $setup['secret'],
            'mfa_setup_uri' => $setup['uri'],
            'status' => 'Registre o código no seu aplicativo autenticador.',
        ]);
    }

    public function confirm(Request $request, MfaManager $manager): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $codes = $manager->confirm($request->user(), $data['code']);
        $request->session()->regenerate();
        $request->session()->put('admin_mfa_user_id', $request->user()->id);

        if ($request->expectsJson()) {
            return response()->json(['data' => [
                'verified' => true,
                'recovery_codes' => $codes,
                'message' => 'Guarde os códigos de recuperação em local seguro; eles não serão exibidos novamente.',
            ]]);
        }

        return redirect()->route('security.mfa.show')->with([
            'mfa_recovery_codes' => $codes,
            'status' => 'Autenticador habilitado. Guarde seus códigos de recuperação.',
        ]);
    }

    public function challenge(Request $request, MfaManager $manager): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'required_without:recovery_code', 'digits:6'],
            'recovery_code' => ['nullable', 'required_without:code', 'string', 'max:60'],
        ]);

        $manager->challenge($request->user(), $data['code'] ?? null, $data['recovery_code'] ?? null);
        $request->session()->regenerate();
        $request->session()->put('admin_mfa_user_id', $request->user()->id);

        if ($request->expectsJson()) {
            return response()->json(['data' => ['verified' => true]]);
        }

        return redirect()->intended(route('admin.sellers.index'))->with('status', 'Acesso administrativo verificado.');
    }

    public function regenerate(Request $request, MfaManager $manager): JsonResponse|RedirectResponse
    {
        abort_unless($this->isVerified($request), 403);
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);
        $this->requirePassword($request, $data['current_password']);
        $codes = $manager->regenerateRecoveryCodes($request->user(), $data['code']);

        if ($request->expectsJson()) {
            return response()->json(['data' => ['recovery_codes' => $codes]]);
        }

        return redirect()->route('security.mfa.show')->with([
            'mfa_recovery_codes' => $codes,
            'status' => 'Códigos antigos invalidados. Guarde os novos códigos.',
        ]);
    }

    private function requirePassword(Request $request, string $password): void
    {
        if (! Hash::check($password, $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'Senha atual incorreta.']);
        }
    }

    private function isVerified(Request $request): bool
    {
        return (bool) $request->user()->mfa_confirmed_at
            && (string) $request->session()->get('admin_mfa_user_id') === (string) $request->user()->id;
    }
}

