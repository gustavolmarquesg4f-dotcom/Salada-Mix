<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\SsoManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SsoController extends Controller
{
    public function providers(SsoManager $manager): JsonResponse
    {
        return response()->json(['data' => $manager->enabledProviders()]);
    }

    public function redirect(Request $request, string $provider, SsoManager $manager): RedirectResponse
    {
        $config = $manager->providerConfig($provider);
        $service = config("services.{$provider}", []);

        abort_if(
            empty($service['client_id']) || empty($service['client_secret']),
            503,
            'SSO configurado na aplicação, mas as credenciais do provedor ainda não foram instaladas.'
        );

        $request->session()->put('sso.intent', $request->user() ? 'link' : 'login');
        $request->session()->put('sso.provider', $provider);

        return Socialite::driver($provider)
            ->scopes((array) ($config['scopes'] ?? []))
            ->redirect();
    }

    public function callback(Request $request, string $provider, SsoManager $manager): RedirectResponse
    {
        $manager->providerConfig($provider);

        if ($request->session()->pull('sso.provider') !== $provider) {
            return redirect()->route('login')->withErrors(['sso' => 'Fluxo de autenticação expirado ou inválido.']);
        }

        $intent = $request->session()->pull('sso.intent', 'login');

        try {
            $profile = Socialite::driver($provider)->user();
            $current = $intent === 'link' ? $request->user() : null;
            $result = $manager->resolve($provider, $profile, $current);
        } catch (ValidationException $exception) {
            return redirect()->route($request->user() ? 'buyer.account' : 'login')
                ->withErrors($exception->errors());
        } catch (Throwable) {
            return redirect()->route($request->user() ? 'buyer.account' : 'login')
                ->withErrors(['sso' => 'Não foi possível concluir a autenticação externa. Tente novamente.']);
        }

        if ($intent === 'link' && $request->user()) {
            $request->session()->put('sso_authenticated_at', now()->timestamp);

            return redirect()->route('buyer.account')
                ->with('status', 'Conta externa conectada com segurança.');
        }

        Auth::login($result['user']);
        $request->session()->regenerate();
        $request->session()->put('sso_authenticated_at', now()->timestamp);
        $request->session()->put('sso_authenticated_provider', $provider);

        if (! $result['user']->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($result['user']->platform_role === 'admin') {
            return redirect()->route('security.mfa.show');
        }

        return redirect()->intended(route('home', absolute: false));
    }

    public function unlink(Request $request, string $provider, SsoManager $manager): JsonResponse
    {
        $data = $request->validate(['current_password' => ['nullable', 'string']]);
        $recent = (int) $request->session()->get('sso_authenticated_at', 0);
        $recentSso = $recent > 0 && (now()->timestamp - $recent) <= (int) config('sso.recent_auth_seconds', 600);

        $manager->unlink($request->user(), $provider, $data['current_password'] ?? null, $recentSso);

        return response()->json(['data' => ['provider' => $provider, 'connected' => false]]);
    }
}

