<?php

namespace App\Http\Controllers\Bff;

use App\Domain\Catalog\Actions\CreateSellerOffer;
use App\Http\Controllers\Controller;
use App\Models\Seller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OfferSubmissionController extends Controller
{
    public function store(Request $request, Seller $seller, CreateSellerOffer $action): JsonResponse
    {
        // The Action enforces seller owner/manager; middleware enforces active membership.
        $data = $request->validate([
            'category_id' => ['required', 'string', Rule::exists('categories', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sku' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/'],
            'price_cents' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);

        $offer = $action->execute($seller, $request->user(), $data);

        return response()->json(['data' => [
            'id' => $offer->id,
            'seller_id' => $offer->seller_id,
            'review_status' => $offer->review_status,
            'checkout_enabled' => false,
        ]], 201);
    }
}

