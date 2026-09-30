<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\HmlDemo;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class DemoRoleController extends Controller
{
    public function __invoke(string $role): View
    {
        HmlDemo::requireEnabled();
        abort_unless(in_array($role, ['comprador', 'vendedor', 'admin'], true), 404);
        return view('storefront.demo-role', [
            'role' => $role,
            'shops' => DB::table('sellers')->where('trade_name', 'like', '%(DEMO)')->orderBy('trade_name')->get(['id', 'trade_name', 'status']),
            'offers' => DB::table('seller_offers')->join('products', 'products.id', '=', 'seller_offers.product_id')
                ->join('sellers', 'sellers.id', '=', 'seller_offers.seller_id')
                ->where('products.slug', 'like', 'demo-%')
                ->orderBy('sellers.trade_name')->orderBy('products.name')
                ->get(['seller_offers.id', 'seller_offers.sku', 'seller_offers.price_cents', 'products.name', 'sellers.trade_name']),
            'ordersCount' => DB::table('demo_orders')->count(),
        ]);
    }
}
