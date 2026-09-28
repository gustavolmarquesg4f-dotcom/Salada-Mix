<?php

namespace App\Http\Controllers\Bff;

use App\Domain\Catalog\Actions\ReviewSellerOffer;
use App\Domain\Seller\Actions\ReviewSellerApplication;
use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminModerationController extends Controller
{
    public function sellers(): JsonResponse
    {
        $rows = Seller::query()->with('owner:id,name,email')
            ->whereIn('status', ['submitted', 'under_review'])
            ->latest()->paginate(20);

        return response()->json([
            'data' => $rows->getCollection()->map(fn (Seller $seller) => [
                'id' => $seller->id,
                'name' => $seller->trade_name,
                'legal_name' => $seller->legal_name,
                'cnpj' => $seller->cnpj,
                'contact_email' => $seller->contact_email,
                'status' => $seller->status,
                'owner' => [
                    'id' => $seller->owner->id,
                    'name' => $seller->owner->name,
                    'email' => $seller->owner->email,
                ],
            ])->values(),
            'meta' => ['total' => $rows->total(), 'current_page' => $rows->currentPage()],
        ]);
    }

    public function offers(): JsonResponse
    {
        $rows = SellerOffer::query()->with(['seller:id,trade_name,status', 'product.category', 'stock'])
            ->where('review_status', 'pending')->latest()->paginate(20);

        return response()->json([
            'data' => $rows->getCollection()->map(fn (SellerOffer $offer) => [
                'id' => $offer->id,
                'name' => $offer->product->name,
                'description' => $offer->product->description,
                'seller' => ['id' => $offer->seller_id, 'name' => $offer->seller->trade_name,
                    'status' => $offer->seller->status],
                'category' => $offer->product->category->name,
                'sku' => $offer->sku,
                'price_cents' => $offer->price_cents,
                'quantity_on_hand' => $offer->stock?->quantity_on_hand ?? 0,
                'review_status' => $offer->review_status,
            ])->values(),
            'meta' => ['total' => $rows->total(), 'current_page' => $rows->currentPage()],
        ]);
    }

    public function decideSeller(
        Request $request, Seller $seller, ReviewSellerApplication $action
    ): JsonResponse {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reason' => ['required_if:decision,rejected', 'nullable', 'string', 'min:10', 'max:2000'],
        ]);

        $result = $action->execute($seller, $request->user(), $data['decision'], $data['reason'] ?? null);

        return response()->json(['data' => [
            'id' => $result->id,
            'status' => $result->status,
            'checkout_enabled' => false,
        ]]);
    }

    public function decideOffer(
        Request $request, SellerOffer $offer, ReviewSellerOffer $action
    ): JsonResponse {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reason' => ['required_if:decision,rejected', 'nullable', 'string', 'min:10', 'max:2000'],
        ]);

        $result = $action->execute($offer, $request->user(), $data['decision'], $data['reason'] ?? null);

        return response()->json(['data' => [
            'id' => $result->id,
            'review_status' => $result->review_status,
            'checkout_enabled' => false,
        ]]);
    }
}
