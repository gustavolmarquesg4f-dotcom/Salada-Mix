<?php

namespace App\Http\Middleware;

use App\Models\Seller;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSellerMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $seller = $request->route('seller');

        abort_unless($seller instanceof Seller, 404);
        abort_unless($request->user()?->hasVerifiedEmail(), 403);

        $isMember = $seller->memberships()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->exists();

        abort_unless($isMember, 403);

        return $next($request);
    }
}

