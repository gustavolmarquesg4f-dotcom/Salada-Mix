<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Catalog\Queries\PublicCatalog;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SellerOffer;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function home(PublicCatalog $catalog): View
    {
        $categories = Category::query()->where('is_active', true)->orderBy('position')->get();
        $offers = $catalog->latest();

        return view('storefront.home', compact('categories', 'offers'));
    }

    public function category(Category $category, PublicCatalog $catalog): View
    {
        $offers = $catalog->forCategory($category);

        return view('storefront.category', compact('category', 'offers'));
    }

    public function show(SellerOffer $offer, PublicCatalog $catalog): View
    {
        $offer = $catalog->findVisible($offer->id);

        return view('storefront.offer', compact('offer'));
    }
}

