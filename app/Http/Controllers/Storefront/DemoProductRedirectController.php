<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Catalog\Queries\PublicCatalog;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\HmlDemo;
use Illuminate\Http\RedirectResponse;

final class DemoProductRedirectController extends Controller
{
    public function __invoke(string $key, PublicCatalog $catalog): RedirectResponse
    {
        HmlDemo::requireEnabled();
        abort_unless(preg_match('/^[a-z0-9-]{1,70}$/', $key), 404);
        $product = Product::query()->where('slug', 'demo-'.$key)->firstOrFail();
        $offer = $catalog->visibleOffers()->where('product_id', $product->id)->firstOrFail();
        return redirect()->route('storefront.offer', $offer);
    }
}
