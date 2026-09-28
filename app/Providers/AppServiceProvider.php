<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('review-sellers', fn (User $user): bool => $user->platform_role === 'admin');

        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                Str::lower((string) $request->input('email')).'|'.$request->ip()
            );
        });

        ViewFacade::composer('components.salada.header', function (View $view): void {
            $view->with('navCategories', Category::query()
                ->where('is_active', true)
                ->orderBy('position')
                ->orderBy('name')
                ->limit(6)
                ->get());
        });
    }
}
