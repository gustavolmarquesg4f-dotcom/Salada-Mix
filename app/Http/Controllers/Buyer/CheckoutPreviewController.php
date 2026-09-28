<?php

namespace App\Http\Controllers\Buyer;

use App\Domain\Checkout\CheckoutPreview;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutPreviewController extends Controller
{
    public function __invoke(Request $request, CheckoutPreview $checkout): JsonResponse|View
    {
        $preview = $checkout->forBuyer($request->user());

        if ($request->expectsJson()) {
            return response()->json($preview);
        }

        return view('buyer.checkout-preview', compact('preview'));
    }
}
