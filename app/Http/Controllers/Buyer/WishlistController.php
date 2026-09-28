<?php

namespace App\Http\Controllers\Buyer;

use App\Domain\Wishlist\WishlistManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request, WishlistManager $wishlist): JsonResponse
    {
        return response()->json($wishlist->read($request->user()));
    }

    public function put(Request $request, string $offer, WishlistManager $wishlist): JsonResponse
    {
        return response()->json($wishlist->add($request->user(), $offer));
    }

    public function destroy(Request $request, string $offer, WishlistManager $wishlist): JsonResponse
    {
        return response()->json($wishlist->remove($request->user(), $offer));
    }
}

