<?php

use App\Http\Controllers\Admin\AdminSellerController;
use App\Http\Controllers\Admin\CatalogModerationController;
use App\Http\Controllers\Seller\SellerOfferController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Seller\SellerDashboardController;
use App\Http\Controllers\Seller\SellerOnboardingController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/buscar', [CatalogController::class, 'search'])->name('storefront.search');
Route::get('/categorias/{category:slug}', [CatalogController::class, 'category'])->name('storefront.category');
Route::get('/ofertas/{offer}', [CatalogController::class, 'show'])->name('storefront.offer');

Route::middleware('guest')->group(function (): void {
    Route::get('/cadastro', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/cadastro', [RegisteredUserController::class, 'store']);
    Route::get('/entrar', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/entrar', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
    Route::get('/esqueci-a-senha', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/esqueci-a-senha', [PasswordResetLinkController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/nova-senha/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/nova-senha', [NewPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/sair', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::view('/email/verify', 'auth.verify-email')->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('home');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Uma nova mensagem de verificação foi enviada.');
    })->middleware('throttle:6,1')->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::view('/minha-conta', 'buyer.account')->name('buyer.account');
    Route::get('/vender/cadastro', [SellerOnboardingController::class, 'create'])->name('seller.apply');
    Route::post('/vender/cadastro', [SellerOnboardingController::class, 'store'])->name('seller.submit');
    Route::get('/vendedor/{seller}/painel', [SellerDashboardController::class, 'show'])
        ->middleware('seller.member')->name('seller.dashboard');
    Route::prefix('vendedor/{seller}')->name('seller.')->middleware('seller.member')->group(function (): void {
        Route::get('/ofertas', [SellerOfferController::class, 'index'])->name('offers.index');
        Route::get('/ofertas/nova', [SellerOfferController::class, 'create'])->name('offers.create');
        Route::post('/ofertas', [SellerOfferController::class, 'store'])->name('offers.store');
    });
    Route::prefix('admin')->name('admin.')->middleware('can:review-sellers')->group(function (): void {
        Route::get('/catalogo', [CatalogModerationController::class, 'index'])->name('catalog.index');
        Route::post('/catalogo/{offer}/aprovar', [CatalogModerationController::class, 'approve'])->name('catalog.approve');
        Route::post('/catalogo/{offer}/rejeitar', [CatalogModerationController::class, 'reject'])->name('catalog.reject');
        Route::get('/empresas', [AdminSellerController::class, 'index'])->name('sellers.index');
        Route::post('/empresas/{seller}/aprovar', [AdminSellerController::class, 'approve'])->name('sellers.approve');
        Route::post('/empresas/{seller}/rejeitar', [AdminSellerController::class, 'reject'])->name('sellers.reject');
    });
});
