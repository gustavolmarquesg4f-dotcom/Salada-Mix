<?php

namespace App\Domain\Catalog\Queries;

use App\Models\Category;
use App\Models\Seller;
use App\Models\SellerOffer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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

        return $this->browse(['category' => $category->slug]);
    }

    /**
     * Every public result is derived from visibleOffers(): filters can narrow visibility,
     * never bypass the seller, approval, category or available-stock requirements.
     */
    public function browse(array $filters = []): LengthAwarePaginator
    {
        $query = $this->visibleOffers();

        if (filled($filters['q'] ?? null)) {
            $term = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['q']);
            $pattern = '%'.$term.'%';

            $query->where(function (Builder $visible) use ($pattern): void {
                $visible
                    ->whereHas('product', fn (Builder $product) => $product
                        ->where(fn (Builder $text) => $text
                            ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])
                            ->orWhereRaw("description LIKE ? ESCAPE '!'", [$pattern])))
                    ->orWhereHas('seller', fn (Builder $seller) => $seller
                        ->whereRaw("trade_name LIKE ? ESCAPE '!'", [$pattern]));
            });
        }

        if (filled($filters['category'] ?? null)) {
            $query->whereHas('product', fn (Builder $product) => $product
                ->whereHas('category', fn (Builder $category) => $category
                    ->where('slug', $filters['category'])));
        }

        if (filled($filters['seller'] ?? null)) {
            $query->where('seller_id', $filters['seller']);
        }

        if (($filters['min_cents'] ?? null) !== null) {
            $query->where('price_cents', '>=', $filters['min_cents']);
        }

        if (($filters['max_cents'] ?? null) !== null) {
            $query->where('price_cents', '<=', $filters['max_cents']);
        }

        match ($filters['sort'] ?? 'recent') {
            'price_asc' => $query->orderBy('price_cents')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price_cents')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        return $query->paginate(12)->withQueryString();
    }

    /** Only vendors with at least one publicly visible offer appear in filters. */
    public function publicSellers(): Collection
    {
        return Seller::query()
            ->where('status', 'active')
            ->whereIn('id', $this->visibleOffers()->select('seller_id')->distinct())
            ->orderBy('trade_name')
            ->get(['id', 'trade_name']);
    }

    public function findVisible(string $offerId): SellerOffer
    {
        return $this->visibleOffers()->findOrFail($offerId);
    }
}
