<?php

use App\Console\Commands\PromotePlatformAdmin;
use App\Http\Middleware\EnsureSellerMember;
use App\Http\Middleware\EnsureAdminMfa;
use App\Http\Middleware\BffResponseHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'seller.member' => EnsureSellerMember::class,
            'admin.mfa' => EnsureAdminMfa::class,
        ]);
        $middleware->web(append: [BffResponseHeaders::class]);
    })
    ->withCommands([PromotePlatformAdmin::class])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
