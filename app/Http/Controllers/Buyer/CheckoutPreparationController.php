<?php

namespace App\Http\Controllers\Buyer;

use App\Domain\Checkout\CheckoutPreparation;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CheckoutPreparationController extends Controller
{
    public function page(Request $request, CheckoutPreparation $preparation): View
    {
        $data = $this->snapshot($request, $preparation);

        return view('buyer.checkout-prepare', $data);
    }

    public function json(Request $request, CheckoutPreparation $preparation): JsonResponse
    {
        return response()->json($this->snapshot($request, $preparation));
    }

    private function snapshot(Request $request, CheckoutPreparation $preparation): array
    {
        $validated = $request->validate([
            'address' => ['nullable', 'ulid'],
        ]);

        return $preparation->forBuyer($request->user(), $validated['address'] ?? null);
    }
}
