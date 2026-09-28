<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user?->platform_role === 'admin', 403);

        if ($user->mfa_confirmed_at && (string) $request->session()->get('admin_mfa_user_id') === (string) $user->id) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('bff/*')) {
            return response()->json([
                'code' => $user->mfa_confirmed_at ? 'mfa_challenge_required' : 'mfa_enrollment_required',
                'message' => 'A verificação adicional é obrigatória para o acesso administrativo.',
                'url' => route('security.mfa.show'),
            ], 403);
        }

        return redirect()->guest(route('security.mfa.show'));
    }
}

