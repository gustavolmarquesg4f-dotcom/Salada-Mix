<?php

namespace App\Http\Controllers\Buyer;

use App\Domain\Cart\CartManager;
use App\Domain\Wishlist\WishlistManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShoppingPageController extends Controller
{
    public function cart(Request $request, CartManager $cart): View
    {
        $snapshot = $cart->read($request->user());
        $available = collect($snapshot['items'])->filter(fn (array $line) => $line['available']);
        $groups = $available->groupBy(fn (array $line) => $line['offer']['seller']['id']);
        $unavailable = collect($snapshot['items'])->reject(fn (array $line) => $line['available']);

        return view('buyer.cart', compact('snapshot', 'groups', 'unavailable'));
    }

    public function wishlist(Request $request, WishlistManager $wishlist): View
    {
        $items = $wishlist->read($request->user())['items'];

        return view('buyer.wishlist', compact('items'));
    }
}
