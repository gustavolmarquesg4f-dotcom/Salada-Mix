<?php

namespace App\Http\Controllers\Bff;

use App\Domain\Seller\Actions\SubmitSellerApplication;
use App\Http\Controllers\Controller;
use App\Rules\ValidCnpj;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerApplicationController extends Controller
{
    public function store(Request $request, SubmitSellerApplication $action): JsonResponse
    {
        $data = $request->validate([
            'legal_name' => ['required', 'string', 'max:200'],
            'trade_name' => ['required', 'string', 'max:160'],
            'cnpj' => ['required', 'string', new ValidCnpj()],
            'contact_email' => ['required', 'email', 'max:255'],
        ]);

        $seller = $action->execute($request->user(), $data);

        return response()->json(['data' => [
            'id' => $seller->id,
            'name' => $seller->trade_name,
            'status' => $seller->status,
            'dashboard_url' => route('seller.dashboard', $seller),
        ]], 201);
    }
}

