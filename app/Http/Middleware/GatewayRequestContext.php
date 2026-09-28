<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class GatewayRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('bff/*') && ! $request->is('api/*')) {
            return $next($request);
        }

        // Versioned gateway contracts return JSON even when the caller forgets Accept.
        // We generate the correlation ID server-side instead of trusting arbitrary input.
        $request->headers->set('Accept', 'application/json');
        $requestId = (string) Str::uuid();
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);
        $response->headers->set('X-API-Version', '1');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}

