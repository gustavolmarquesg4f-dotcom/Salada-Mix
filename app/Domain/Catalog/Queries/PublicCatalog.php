<?php

namespace App\Domain\Catalog\Queries;

use App\Models\Category;
use App\Models\SellerOffer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PublicCatalog
{
    public function visibleOffers(): Builder
    {
        return SellerOffer::query()
            ->with(['seller:id,trade_name,status', 'product.category', 'stock'])
            ->where('review_status', 'approved')
            ->whereHas('seller', fn (Builder $query) => $query->where('status', 'active'))
            ->whereHas('product', fn (Builder $query) => $query
                ->where('review_status', 'approved')
                ->whereHas('category', fn (Builder $category) => $category->where('is_active', true)))
            ->whereHas('stock', fn (Builder $query) => $query
                ->whereColumn('quantity_on_hand', '>', 'quantity_reserved'));
    }

    public function latest(int $limit = 8): Collection
    {
        return $this->visibleOffers()->latest()->limit($limit)->get();
    }

    public function forCategory(Category $category): LengthAwarePaginator
    {
        abort_unless($category->is_active, 404);

        return $this->visibleOffers()
            ->whereHas('product', fn (Builder $query) => $query->where('category_id', $category->id))
            ->latest()->paginate(12);
    }

    public function findVisible(string $offerId): SellerOffer
    {
        return $this->visibleOffers()->findOrFail($offerId);
    }
}

