<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Catalog\Actions\ReviewSellerOffer;
use App\Http\Controllers\Controller;
use App\Models\SellerOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogModerationController extends Controller
{
    public function index(): View
    {
        $offers = SellerOffer::query()
            ->with(['seller', 'product.category', 'stock'])
            ->where('review_status', 'pending')
            ->latest()->paginate(20);

        return view('admin.catalog', compact('offers'));
    }

    public function approve(SellerOffer $offer, Request $request, ReviewSellerOffer $action): RedirectResponse
    {
        $action->execute($offer, $request->user(), 'approved');

        return back()->with('status', 'Oferta aprovada; ainda depende da ativação comercial do vendedor.');
    }

    public function reject(SellerOffer $offer, Request $request, ReviewSellerOffer $action): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $action->execute($offer, $request->user(), 'rejected', $data['reason']);

        return back()->with('status', 'Oferta rejeitada e decisão auditada.');
    }
}

