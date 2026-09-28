<?php

namespace App\Http\Controllers\Seller;

use App\Domain\Seller\Actions\SubmitSellerApplication;
use App\Http\Controllers\Controller;
use App\Rules\ValidCnpj;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerOnboardingController extends Controller
{
    public function create(): View
    {
        return view('seller.apply');
    }

    public function store(Request $request, SubmitSellerApplication $action): RedirectResponse
    {
        $data = $request->validate([
            'legal_name' => ['required', 'string', 'max:200'],
            'trade_name' => ['required', 'string', 'max:160'],
            'cnpj' => ['required', 'string', new ValidCnpj()],
            'contact_email' => ['required', 'email', 'max:255'],
        ]);

        $seller = $action->execute($request->user(), $data);

        return redirect()->route('seller.dashboard', $seller)
            ->with('status', 'Cadastro enviado para análise.');
    }
}

