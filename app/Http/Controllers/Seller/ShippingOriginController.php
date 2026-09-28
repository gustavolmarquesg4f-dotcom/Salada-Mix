<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressFields;
use App\Models\Seller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShippingOriginController extends Controller
{
    public function index(Seller $seller): View
    {
        $origins = DB::table('shipping_origins')->where('seller_id', $seller->id)
            ->orderByDesc('is_default')->orderBy('created_at')->get();

        return view('seller.origins', compact('seller', 'origins'));
    }

    public function store(Seller $seller, Request $request): RedirectResponse
    {
        $this->authorizeManager($seller, $request);
        $data = AddressFields::normalize($request->validate(AddressFields::rules(false)));

        DB::transaction(function () use ($seller, $data): void {
            DB::table('sellers')->where('id', $seller->id)->lockForUpdate()->firstOrFail();
            $count = DB::table('shipping_origins')->where('seller_id', $seller->id)->count();
            if ($count >= 5) {
                throw ValidationException::withMessages(['label' => 'Limite de 5 origens por empresa.']);
            }

            $makeDefault = $data['is_default'] || $count === 0;
            if ($makeDefault) {
                DB::table('shipping_origins')->where('seller_id', $seller->id)->update(['is_default' => false]);
            }

            DB::table('shipping_origins')->insert([
                ...$data,
                'id' => (string) Str::ulid(),
                'seller_id' => $seller->id,
                'is_default' => $makeDefault,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);

        return redirect()->route('seller.origins.index', $seller)->with('status', 'Origem de envio salva.');
    }

    public function destroy(Seller $seller, Request $request, string $origin): RedirectResponse
    {
        $this->authorizeManager($seller, $request);

        DB::transaction(function () use ($seller, $origin): void {
            DB::table('sellers')->where('id', $seller->id)->lockForUpdate()->firstOrFail();
            $owned = DB::table('shipping_origins')->where('id', $origin)
                ->where('seller_id', $seller->id)->first();
            abort_unless($owned, 404);

            DB::table('shipping_origins')->where('id', $origin)->where('seller_id', $seller->id)->delete();

            if ($owned->is_default) {
                $next = DB::table('shipping_origins')->where('seller_id', $seller->id)
                    ->orderBy('created_at')->orderBy('id')->first();

                if ($next) {
                    DB::table('shipping_origins')->where('id', $next->id)
                        ->where('seller_id', $seller->id)->update(['is_default' => true]);
                }
            }
        }, 3);

        return redirect()->route('seller.origins.index', $seller)->with('status', 'Origem removida.');
    }

    private function authorizeManager(Seller $seller, Request $request): void
    {
        abort_unless($seller->memberships()->where('user_id', $request->user()->id)
            ->where('status', 'active')->whereIn('role', ['owner', 'manager'])->exists(), 403);
    }
}
