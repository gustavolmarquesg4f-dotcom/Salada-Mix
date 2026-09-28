<?php

namespace App\Http\Controllers\Api;

use App\Domain\Catalog\Presenters\PublicOfferData;
use App\Domain\Catalog\Queries\PublicCatalog;
use App\Domain\Catalog\Queries\SearchCatalog;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicCatalogController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = Category::query()->where('is_active', true)
            ->orderBy('position')->orderBy('name')
            ->get(['id', 'name', 'slug', 'parent_id']);

        return response()->json(['data' => $categories]);
    }

    public function index(Request $request, SearchCatalog $search): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:150', 'regex:/^[a-z0-9-]+$/'],
            'min_price_cents' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'max_price_cents' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'sort' => ['nullable', Rule::in(['newest', 'price_asc', 'price_desc', 'name_asc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:24'],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        SearchCatalog::validatePriceRange($filters);
        $offers = $search->search($filters);

        return response()->json([
            'data' => $offers->getCollection()->map(fn ($offer) => PublicOfferData::from($offer))->all(),
            'meta' => [
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'per_page' => $offers->perPage(),
                'total' => $offers->total(),
            ],
            'links' => ['next' => $offers->nextPageUrl(), 'prev' => $offers->previousPageUrl()],
        ]);
    }

    public function show(string $offer, PublicCatalog $catalog): JsonResponse
    {
        $record = $catalog->findVisible($offer);

        return response()->json(['data' => PublicOfferData::from($record, details: true)]);
    }
}

