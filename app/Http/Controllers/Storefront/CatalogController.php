<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Catalog\Queries\PublicCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\BrowseCatalogRequest;
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

    public function search(BrowseCatalogRequest $request, PublicCatalog $catalog): View
    {
        $filters = $request->filters();

        $category = filled($filters['category'] ?? null)
            ? Category::query()->where('slug', $filters['category'])->where('is_active', true)->firstOrFail()
            : null;

        return $this->browseView($catalog, $filters, $category);
    }

    public function category(Category $category, BrowseCatalogRequest $request, PublicCatalog $catalog): View
    {
        abort_unless($category->is_active, 404);

        $filters = $request->filters();
        unset($filters['category']);
        $filters['category'] = $category->slug;

        return $this->browseView($catalog, $filters, $category);
    }

    public function show(SellerOffer $offer, PublicCatalog $catalog): View
    {
        $offer = $catalog->findVisible($offer->id);

        return view('storefront.offer', compact('offer'));
    }

    private function browseView(PublicCatalog $catalog, array $filters, ?Category $category): View
    {
        $categories = Category::query()->where('is_active', true)->orderBy('position')->get();
        $sellers = $catalog->publicSellers();
        $offers = $catalog->browse($filters);

        return view('storefront.browse', compact('offers', 'categories', 'sellers', 'filters', 'category'));
    }
}
