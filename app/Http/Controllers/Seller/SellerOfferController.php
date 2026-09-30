<?php

namespace App\Http\Controllers\Seller;

use App\Domain\Catalog\Actions\CreateSellerOffer;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Seller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SellerOfferController extends Controller
{
    public function index(Seller $seller): View
    {
        $offers = $seller->offers()->with(['product.category', 'product.media', 'stock'])->latest()->paginate(20);

        return view('seller.offers.index', compact('seller', 'offers'));
    }

    public function create(Seller $seller, Request $request): View
    {
        $this->authorizeEditor($seller, $request);
        $categories = Category::query()->where('is_active', true)->orderBy('position')->get();

        return view('seller.offers.create', compact('seller', 'categories'));
    }

    public function store(Seller $seller, Request $request, CreateSellerOffer $action): RedirectResponse
    {
        $this->authorizeEditor($seller, $request);

        $data = $request->validate([
            'category_id' => ['required', 'string', Rule::exists('categories', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sku' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/'],
            'price' => ['required', 'string', 'regex:/^\d{1,9}([,.]\d{1,2})?$/'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'weight_grams' => ['required', 'integer', 'min:1', 'max:100000'],
            'length_cm' => ['required', 'integer', 'min:1', 'max:300'],
            'width_cm' => ['required', 'integer', 'min:1', 'max:300'],
            'height_cm' => ['required', 'integer', 'min:1', 'max:300'],
        ]);

        $data['price_cents'] = $this->priceToCents($data['price']);
        unset($data['price']);

        $action->execute($seller, $request->user(), $data);

        return redirect()->route('seller.offers.index', $seller)
            ->with('status', 'Produto e oferta cadastrados para análise.');
    }

    private function authorizeEditor(Seller $seller, Request $request): void
    {
        abort_unless($seller->memberships()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->whereIn('role', ['owner', 'manager'])
            ->exists(), 403);
    }

    private function priceToCents(string $value): int
    {
        $normalized = str_replace(',', '.', trim($value));
        [$whole, $decimal] = array_pad(explode('.', $normalized, 2), 2, '');
        $cents = ((int) $whole * 100) + (int) str_pad(substr($decimal, 0, 2), 2, '0');

        if ($cents < 1 || $cents > 999999999999) {
            throw ValidationException::withMessages(['price' => 'Preço fora do intervalo permitido.']);
        }

        return $cents;
    }
}
