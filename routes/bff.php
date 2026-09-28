<?php

use App\Http\Controllers\Api\PublicCatalogController;
use App\Http\Controllers\Bff\AccountController;
use App\Http\Controllers\Bff\GatewayController;
use App\Http\Controllers\Bff\OfferSubmissionController;
use App\Http\Controllers\Bff\SellerApplicationController;
use App\Http\Controllers\Bff\SessionController;
use App\Http\Controllers\Buyer\CartController;
use App\Http\Controllers\Buyer\WishlistController;
use Illuminate\Support\Facades\Route;

/**
 * Same-origin BFF / lightweight API Gateway for our modular Laravel application.
 * Registered through the WEB middleware stack: encrypted HttpOnly session + CSRF
 * protection for all unsafe methods. This is NOT a public bearer-token API.
 */
Route::prefix('bff/v1')->name('bff.')->group(function (): void {
    Route::middleware('throttle:bff-read')->group(function (): void {
        Route::get('/storefront', [GatewayController::class, 'storefront'])->name('storefront');
        Route::get('/catalog/categories', [PublicCatalogController::class, 'categories'])->name('catalog.categories');
        Route::get('/catalog/offers', [PublicCatalogController::class, 'index'])->name('catalog.offers.index');
        Route::get('/catalog/offers/{offer}', [PublicCatalogController::class, 'show'])->name('catalog.offers.show');
        Route::get('/auth/me', [SessionController::class, 'me'])->name('auth.me');
    });

    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::middleware('guest')->group(function (): void {
            Route::post('/register', [SessionController::class, 'register'])->middleware('throttle:3,1')->name('register');
            Route::post('/login', [SessionController::class, 'login'])->middleware('throttle:login')->name('login');
            Route::post('/forgot-password', [SessionController::class, 'forgotPassword'])->middleware('throttle:6,1')->name('forgot');
            Route::post('/reset-password', [SessionController::class, 'resetPassword'])->middleware('throttle:6,1')->name('reset');
        });
        Route::middleware('auth')->group(function (): void {
            Route::post('/logout', [SessionController::class, 'logout'])->name('logout');
            Route::post('/verification-email', [SessionController::class, 'resendVerification'])
                ->middleware('throttle:6,1')->name('verify.resend');
        });
    });

    Route::middleware(['auth', 'verified'])->group(function (): void {
        Route::get('/buyer', [GatewayController::class, 'buyer'])->name('buyer');
        Route::get('/account', [AccountController::class, 'show'])->name('account.show');
        Route::patch('/account', [AccountController::class, 'update'])->middleware('throttle:15,1')->name('account.update');
        Route::put('/account/password', [AccountController::class, 'password'])
            ->middleware('throttle:6,1')->name('account.password');
        Route::get('/account/sessions', [AccountController::class, 'sessions'])->name('account.sessions');
        Route::delete('/account/sessions/{fingerprint}', [AccountController::class, 'revokeSession'])
            ->middleware('throttle:10,1')->name('account.sessions.revoke');

        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::put('/cart/{offer}', [CartController::class, 'put'])->name('cart.put');
        Route::delete('/cart/{offer}', [CartController::class, 'destroy'])->name('cart.destroy');
        Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
        Route::put('/wishlist/{offer}', [WishlistController::class, 'put'])->name('wishlist.put');
        Route::delete('/wishlist/{offer}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

        Route::post('/sellers', [SellerApplicationController::class, 'store'])
            ->middleware('throttle:3,1')->name('sellers.store');
        Route::prefix('sellers/{seller}')->middleware('seller.member')->name('sellers.')->group(function (): void {
            Route::get('/', [GatewayController::class, 'seller'])->name('show');
            Route::post('/offers', [OfferSubmissionController::class, 'store'])
                ->middleware('throttle:20,1')->name('offers.store');
        });
        Route::get('/admin', [GatewayController::class, 'admin'])
            ->middleware('can:review-sellers')->name('admin');
    });
});

