<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Seller;
use App\Models\SellerOffer;
use App\Models\Product;
use App\Models\StockLevel;
use App\Support\HmlDemo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class CatalogManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.manage-catalog', [
            'categories' => Category::query()->orderBy('position')->get(),
            'sellers' => Seller::query()->whereIn('status', ['approved', 'active'])->orderBy('trade_name')->get(),
            'offers' => SellerOffer::query()->with(['seller', 'product.category', 'product.media', 'stock'])
                ->latest()->paginate(20),
        ]);
    }

    public function category(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'position' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);
        $slug = Str::slug($data['name']);
        if (! $slug || Category::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => 'Departamento inválido ou já cadastrado.']);
        }
        $category = Category::query()->create([
            'name' => $data['name'], 'slug' => $slug, 'position' => $data['position'], 'is_active' => true,
        ]);
        AuditLog::query()->create([
            'actor_user_id' => $request->user()->id, 'action' => 'catalog.category.created',
            'metadata' => ['category_id' => $category->id],
        ]);
        return back()->with('status', 'Departamento criado.');
    }

    public function toggle(Request $request, Category $category): RedirectResponse
    {
        $active = ! $category->is_active;
        $category->update(['is_active' => $active]);
        AuditLog::query()->create([
            'actor_user_id' => $request->user()->id, 'action' => 'catalog.category.status',
            'metadata' => ['category_id' => $category->id, 'is_active' => $active],
        ]);
        return back()->with('status', 'Visibilidade do departamento atualizada.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'seller_id' => ['required', Rule::exists('sellers', 'id')->whereIn('status', ['approved', 'active'])],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sku' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/'],
            'price_cents' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);
        if (HmlDemo::enabled()) {
            abort_unless(Seller::query()->whereKey($data['seller_id'])
                ->where('trade_name', 'like', '%(DEMO)')->exists(), 403);
            $data['name'] = Str::endsWith($data['name'], '(DEMO)') ? $data['name'] : $data['name'].' (DEMO)';
        }
        $sku = Str::upper($data['sku']);
        if (SellerOffer::query()->where('seller_id', $data['seller_id'])->where('sku', $sku)->exists()) {
            throw ValidationException::withMessages(['sku' => 'SKU já cadastrado para esta loja.']);
        }
        DB::transaction(function () use ($request, $data, $sku): void {
            $product = Product::query()->create([
                'category_id' => $data['category_id'], 'created_by_seller_id' => $data['seller_id'],
                'name' => $data['name'],
                'slug' => (HmlDemo::enabled() ? 'demo-admin-' : '').(Str::slug($data['name']) ?: 'produto').'-'.Str::lower((string) Str::ulid()),
                'description' => $data['description'] ?? null, 'review_status' => 'pending',
            ]);
            $offer = SellerOffer::query()->create([
                'seller_id' => $data['seller_id'], 'product_id' => $product->id,
                'sku' => $sku, 'price_cents' => $data['price_cents'],
                'currency' => 'BRL', 'review_status' => 'pending',
            ]);
            StockLevel::query()->create([
                'offer_id' => $offer->id, 'seller_id' => $offer->seller_id,
                'quantity_on_hand' => $data['stock_quantity'], 'quantity_reserved' => 0,
            ]);
            AuditLog::query()->create([
                'actor_user_id' => $request->user()->id, 'seller_id' => $offer->seller_id,
                'action' => 'catalog.offer.admin.created', 'metadata' => ['offer_id' => $offer->id],
            ]);
        }, 3);
        return redirect()->route('admin.manage')->with('status', 'Oferta cadastrada como rascunho para revisão.');
    }

    public function unpublish(Request $request, SellerOffer $offer): RedirectResponse
    {
        DB::transaction(function () use ($request, $offer): void {
            $locked = SellerOffer::query()->lockForUpdate()->findOrFail($offer->id);
            if ($locked->review_status !== 'approved') {
                throw ValidationException::withMessages(['offer' => 'A oferta não está publicada.']);
            }
            $locked->update(['review_status' => 'pending']);
            Product::query()->whereKey($locked->product_id)->update(['review_status' => 'pending']);
            AuditLog::query()->create([
                'actor_user_id' => $request->user()->id, 'seller_id' => $locked->seller_id,
                'action' => 'catalog.offer.unpublished', 'metadata' => ['offer_id' => $locked->id],
            ]);
        }, 3);
        return back()->with('status', 'Oferta retirada da vitrine e enviada para revisão.');
    }
}
