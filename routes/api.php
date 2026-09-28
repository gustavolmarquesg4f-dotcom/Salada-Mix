<?php

use App\Http\Controllers\Api\PublicCatalogController;
use Illuminate\Support\Facades\Route;

// Read-only public contract. No credentials, financial actions or private vendor data.
Route::prefix('v1/catalog')->name('api.catalog.')->group(function (): void {
    Route::get('/categories', [PublicCatalogController::class, 'categories'])->name('categories');
    Route::get('/offers', [PublicCatalogController::class, 'index'])->name('offers.index');
    Route::get('/offers/{offer}', [PublicCatalogController::class, 'show'])->name('offers.show');
});

