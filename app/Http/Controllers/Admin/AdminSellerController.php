<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Seller\Actions\ReviewSellerApplication;
use App\Http\Controllers\Controller;
use App\Models\Seller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSellerController extends Controller
{
    public function index(): View
    {
        $sellers = Seller::query()->with('owner')->latest()->paginate(20);

        return view('admin.sellers', compact('sellers'));
    }

    public function approve(Seller $seller, Request $request, ReviewSellerApplication $action): RedirectResponse
    {
        $action->execute($seller, $request->user(), 'approved');

        return back()->with('status', 'Empresa aprovada; habilitação comercial ainda pendente.');
    }

    public function reject(Seller $seller, Request $request, ReviewSellerApplication $action): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $action->execute($seller, $request->user(), 'rejected', $data['reason']);

        return back()->with('status', 'Decisão registrada.');
    }
}

