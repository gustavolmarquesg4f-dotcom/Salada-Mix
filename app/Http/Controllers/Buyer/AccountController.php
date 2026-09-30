<?php

namespace App\Http\Controllers\Buyer;

use App\Domain\Cart\CartManager;
use App\Http\Controllers\Controller;
use App\Support\HmlDemo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class AccountController extends Controller
{
    public function show(Request $request, CartManager $cart): View
    {
        $user = $request->user();
        $snapshot = $cart->read($user);

        $accountSummary = [
            'addresses' => DB::table('customer_addresses')->where('user_id', $user->id)->count(),
            'cart_items' => collect($snapshot['items'])->sum(fn (array $line): int => (int) $line['quantity']),
            'cart_subtotal_cents' => (int) $snapshot['subtotal_cents'],
            'wishlist_items' => DB::table('wishlist_items')->where('user_id', $user->id)->count(),
        ];

        $hmlSandbox = HmlDemo::enabled();
        $isDemoBuyer = $hmlSandbox
            && $user->platform_role === 'customer'
            && str_ends_with((string) $user->email, '@saladamix-demo.test')
            && (string) $request->session()->get('salada_demo_user_id') === (string) $user->id;

        return view('buyer.account', compact('accountSummary', 'hmlSandbox', 'isDemoBuyer'));
    }
}
