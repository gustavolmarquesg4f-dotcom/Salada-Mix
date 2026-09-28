<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class BffRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('bff/*')) {
            return $next($request);
        }

        // BFF clients always receive JSON errors. Do not accept arbitrary client IDs as trusted trace IDs.
        $request->headers->set('Accept', 'application/json');
        $requestId = (string) Str::uuid();
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);
        $response->headers->set('X-API-Version', '1');

        return $response;
    }
}

