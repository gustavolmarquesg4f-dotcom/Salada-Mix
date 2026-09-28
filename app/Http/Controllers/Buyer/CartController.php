<?php

namespace App\Http\Controllers\Buyer;

use App\Domain\Cart\CartManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request, CartManager $cart): JsonResponse
    {
        return response()->json($cart->read($request->user()));
    }

    public function put(Request $request, string $offer, CartManager $cart): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        return response()->json($cart->setQuantity($request->user(), $offer, (int) $validated['quantity']));
    }

    public function destroy(Request $request, string $offer, CartManager $cart): JsonResponse
    {
        return response()->json($cart->remove($request->user(), $offer));
    }
}

