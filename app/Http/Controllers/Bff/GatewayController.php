<?php

namespace App\Http\Controllers\Bff;

use App\Domain\Cart\CartManager;
use App\Domain\Checkout\CheckoutPreview;
use App\Domain\Catalog\Presenters\PublicOfferData;
use App\Domain\Catalog\Queries\PublicCatalog;
use App\Domain\Wishlist\WishlistManager;
use App\Domain\Identity\SsoManager;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Seller;
use App\Models\SellerOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GatewayController extends Controller
{
    public function meta(SsoManager $sso): JsonResponse
    {
        return response()->json(['data' => [
            'api_version' => 1,
            'application' => 'salada-mix',
            'request_id' => request()->attributes->get('request_id'),
            'auth' => [
                'session_cookie' => true,
                'csrf_required_for_writes' => true,
                'sso_providers' => $sso->enabledProviders(),
                'admin_mfa_required' => true,
            ],
            'features' => [
                'catalog' => true,
                'cart' => true,
                'wishlist' => true,
                'seller_onboarding' => true,
                'seller_catalog' => true,
                'addresses' => true,
                'checkout_preview' => true,
                'checkout_enabled' => false,
                'payments_enabled' => false,
                'shipping_quotes_enabled' => false,
            ],
        ]]);
    }

    /**
     * Same-origin BFF: one public snapshot with no direct table access from the browser.
     * No billing operations are possible through this gateway.
     */
    public function storefront(PublicCatalog $catalog): JsonResponse
    {
        $categories = Category::query()->where('is_active', true)->orderBy('position')
            ->get(['id', 'name', 'slug', 'parent_id']);

        return response()->json([
            'data' => [
                'categories' => $categories,
                'offers' => $catalog->latest(8)->map(fn (SellerOffer $offer) =>
                    PublicOfferData::from($offer))->values(),
                'features' => [
                    'checkout_enabled' => false,
                    'shipping_quote_enabled' => false,
                    'seller_registration_enabled' => true,
                ],
            ],
        ]);
    }

    public function buyer(Request $request, CartManager $cart, WishlistManager $wishlist): JsonResponse
    {
        $user = $request->user();

        return response()->json(['data' => [
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => $user->hasVerifiedEmail(),
            ],
            'cart' => $cart->read($user),
            'wishlist' => $wishlist->read($user),
            'sellers' => $user->sellerMemberships()
                ->where('status', 'active')->with('seller:id,trade_name,status')
                ->get()->map(fn ($membership) => [
                    'id' => $membership->seller->id,
                    'name' => $membership->seller->trade_name,
                    'status' => $membership->seller->status,
                    'role' => $membership->role,
                ])->values(),
        ]]);
    }

    public function checkoutPreview(Request $request, CheckoutPreview $preview): JsonResponse
    {
        // Read-only grouping from BE-03A: no order, payment, quote or stock reservation.
        return response()->json(['data' => $preview->forBuyer($request->user())]);
    }

    public function sellerOrigins(Seller $seller): JsonResponse
    {
        // The route requires seller.member. Never load origins from other companies.
        $origins = DB::table('shipping_origins')->where('seller_id', $seller->id)
            ->orderByDesc('is_default')->orderBy('created_at')
            ->get(['id', 'label', 'postal_code', 'street', 'number', 'complement',
                'neighborhood', 'city', 'state', 'is_default', 'is_active']);

        return response()->json(['data' => $origins]);
    }

    public function seller(Request $request, Seller $seller): JsonResponse
    {
        // Authorization comes from seller.member middleware, never from a seller_id payload.
        $membership = $seller->memberships()->where('user_id', $request->user()->id)
            ->where('status', 'active')->firstOrFail();
        $request->validate(['page' => ['nullable', 'integer', 'min:1', 'max:10000']]);
        $offers = $seller->offers()->with(['product.category', 'stock'])->latest()->paginate(20);

        return response()->json(['data' => [
            'seller' => [
                'id' => $seller->id,
                'name' => $seller->trade_name,
                'status' => $seller->status,
                'role' => $membership->role,
            ],
            'offers' => $offers->getCollection()->map(fn ($offer) => [
                'id' => $offer->id,
                'name' => $offer->product->name,
                'sku' => $offer->sku,
                'category' => $offer->product->category->name,
                'price_cents' => $offer->price_cents,
                'stock_on_hand' => $offer->stock?->quantity_on_hand ?? 0,
                'stock_reserved' => $offer->stock?->quantity_reserved ?? 0,
                'review_status' => $offer->review_status,
            ])->values(),
            'meta' => [
                'total' => $offers->total(),
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
            ],
            'capabilities' => [
                'edit_catalog' => in_array($membership->role, ['owner', 'manager'], true)
                    && in_array($seller->status, ['approved', 'active'], true),
                'receive_orders' => false,
            ],
        ]]);
    }

    public function admin(): JsonResponse
    {
        return response()->json(['data' => [
            'pending_sellers' => Seller::query()->where('status', 'submitted')->count(),
            'pending_offers' => SellerOffer::query()->where('review_status', 'pending')->count(),
            'features' => ['payments_enabled' => false, 'checkout_enabled' => false],
        ]]);
    }
}
