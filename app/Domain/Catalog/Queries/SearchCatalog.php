<?php

namespace App\Domain\Catalog\Queries;

use App\Models\SellerOffer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class SearchCatalog
{
    public function __construct(private readonly PublicCatalog $publicCatalog)
    {
    }

    /** Only publicly eligible offers; all prices are stored and filtered in BRL cents. */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = $this->publicCatalog->visibleOffers();
        $term = trim((string) ($filters['q'] ?? ''));

        if ($term !== '') {
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
            $pattern = '%'.$escaped.'%';

            $query->where(function (Builder $builder) use ($pattern): void {
                $builder->whereHas('product', function (Builder $product) use ($pattern): void {
                    $product->where(function (Builder $inner) use ($pattern): void {
                        $inner->whereRaw("products.name LIKE ? ESCAPE '!'", [$pattern])
                            ->orWhereRaw("products.description LIKE ? ESCAPE '!'", [$pattern]);
                    });
                })->orWhereHas('seller', fn (Builder $seller) =>
                    $seller->whereRaw("sellers.trade_name LIKE ? ESCAPE '!'", [$pattern]));
            });
        }

        if (! empty($filters['seller'])) {
            $query->where('seller_id', $filters['seller']);
        }

        if (! empty($filters['category'])) {
            $query->whereHas('product.category', fn (Builder $category) =>
                $category->where('slug', $filters['category']));
        }

        if (isset($filters['min_price_cents'])) {
            $query->where('price_cents', '>=', $filters['min_price_cents']);
        }

        if (isset($filters['max_price_cents'])) {
            $query->where('price_cents', '<=', $filters['max_price_cents']);
        }

        $sort = $filters['sort'] ?? 'newest';

        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price_cents')->orderBy('id');
                break;
            case 'price_desc':
                $query->orderByDesc('price_cents')->orderByDesc('id');
                break;
            case 'name_asc':
                $query->orderBy(
                    \App\Models\Product::query()->select('name')
                        ->whereColumn('products.id', 'seller_offers.product_id')
                )->orderBy('seller_offers.id');
                break;
            default:
                $query->orderByDesc('seller_offers.created_at')->orderByDesc('seller_offers.id');
        }

        return $query->paginate((int) ($filters['per_page'] ?? 12))->withQueryString();
    }

    public static function validatePriceRange(array $filters): void
    {
        if (isset($filters['min_price_cents'], $filters['max_price_cents'])
            && $filters['min_price_cents'] > $filters['max_price_cents']) {
            throw ValidationException::withMessages([
                'max_price_cents' => 'O preço máximo deve ser maior ou igual ao mínimo.',
            ]);
        }
    }
}

