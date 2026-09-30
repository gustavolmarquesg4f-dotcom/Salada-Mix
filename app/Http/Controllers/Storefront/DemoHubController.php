<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DemoHubController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(app()->environment('staging')
            && parse_url((string) config('app.url'), PHP_URL_HOST) === 'ivory-rook-276202.hostingersite.com'
            && ! config('marketplace.checkout_enabled')
            && ! config('marketplace.order_drafts_enabled')
            && config('marketplace.payments_provider') === 'none', 404);

        return view('storefront.demo-hub', [
            'shops' => DB::table('sellers')->where('trade_name', 'like', '%(DEMO)')->count(),
            'products' => DB::table('products')->where('slug', 'like', 'demo-%')->count(),
            'offers' => DB::table('seller_offers')->join('products', 'products.id', '=', 'seller_offers.product_id')
                ->where('products.slug', 'like', 'demo-%')->count(),
            'categories' => DB::table('categories')->where('is_active', true)->count(),
        ]);
    }
}
