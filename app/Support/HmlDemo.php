<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

final class HmlDemo
{
    public static function enabled(): bool
    {
        return app()->environment('staging')
            && parse_url((string) config('app.url'), PHP_URL_HOST) === 'ivory-rook-276202.hostingersite.com'
            && ! config('marketplace.checkout_enabled')
            && ! config('marketplace.order_drafts_enabled')
            && config('marketplace.payments_provider') === 'none';
    }

    public static function requireEnabled(): void
    {
        abort_unless(self::enabled(), 404);
    }

    public static function buyer(Request $request): User
    {
        self::requireEnabled();
        $user = $request->user();
        abort_unless($user
            && $user->platform_role === 'customer'
            && str_ends_with($user->email, '@saladamix-demo.test')
            && (string) $request->session()->get('salada_demo_user_id') === (string) $user->id, 403);
        return $user;
    }
}
