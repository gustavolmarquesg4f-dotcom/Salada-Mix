<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        $addresses = DB::table('customer_addresses')->where('user_id', $request->user()->id)
            ->orderByDesc('is_default')->orderBy('created_at')->get();

        return view('buyer.addresses', compact('addresses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = AddressFields::normalize($request->validate(AddressFields::rules(true)));
        $userId = $request->user()->id;

        DB::transaction(function () use ($data, $userId): void {
            // Locking the owning user serializes simultaneous address changes.
            DB::table('users')->where('id', $userId)->lockForUpdate()->firstOrFail();
            $count = DB::table('customer_addresses')->where('user_id', $userId)->count();
            if ($count >= 10) {
                throw ValidationException::withMessages(['label' => 'Limite de 10 endereços por conta.']);
            }

            $makeDefault = $data['is_default'] || $count === 0;
            if ($makeDefault) {
                DB::table('customer_addresses')->where('user_id', $userId)->update(['is_default' => false]);
            }

            DB::table('customer_addresses')->insert([
                ...$data,
                'id' => (string) Str::ulid(),
                'user_id' => $userId,
                'is_default' => $makeDefault,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);

        return redirect()->route('buyer.addresses.index')->with('status', 'Endereço salvo.');
    }

    public function destroy(Request $request, string $address): RedirectResponse
    {
        $userId = $request->user()->id;

        DB::transaction(function () use ($userId, $address): void {
            DB::table('users')->where('id', $userId)->lockForUpdate()->firstOrFail();
            $owned = DB::table('customer_addresses')->where('id', $address)
                ->where('user_id', $userId)->first();
            abort_unless($owned, 404);

            DB::table('customer_addresses')->where('id', $address)->where('user_id', $userId)->delete();

            if ($owned->is_default) {
                $next = DB::table('customer_addresses')->where('user_id', $userId)
                    ->orderBy('created_at')->orderBy('id')->first();

                if ($next) {
                    DB::table('customer_addresses')->where('id', $next->id)
                        ->where('user_id', $userId)->update(['is_default' => true]);
                }
            }
        }, 3);

        return redirect()->route('buyer.addresses.index')->with('status', 'Endereço removido.');
    }
}
