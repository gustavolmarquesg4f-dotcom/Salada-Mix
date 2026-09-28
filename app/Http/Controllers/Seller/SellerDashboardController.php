<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use Illuminate\View\View;

class SellerDashboardController extends Controller
{
    public function show(Seller $seller): View
    {
        return view('seller.dashboard', compact('seller'));
    }
}

