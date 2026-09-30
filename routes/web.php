<?php

use App\Http\Controllers\Admin\AdminSellerController;
use App\Http\Controllers\Buyer\CartController;
use App\Http\Controllers\Buyer\AddressController;
use App\Http\Controllers\Buyer\CheckoutPreviewController;
use App\Http\Controllers\Buyer\CheckoutPreparationController;
use App\Http\Controllers\Buyer\WishlistController;
use App\Http\Controllers\Buyer\ShoppingPageController;
use App\Http\Controllers\Admin\CatalogModerationController;
use App\Http\Controllers\Seller\SellerOfferController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\DemoHubController;
use App\Http\Controllers\Storefront\DemoJourneyController;
use App\Http\Controllers\Storefront\DemoProductRedirectController;
use App\Http\Controllers\Storefront\DemoRoleController;
use App\Http\Controllers\Storefront\ProductMediaController;
use App\Http\Controllers\Admin\CatalogManagementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\MfaController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\Seller\SellerDashboardController;
use App\Http\Controllers\Seller\SellerOnboardingController;
use App\Http\Controllers\Seller\ShippingOriginController;
use App\Http\Controllers\Seller\SellerTeamController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// HML presents the approved FE-06 visual reference byte-for-byte (except its base path).
// Live Laravel routes remain available; the commercial production home stays dynamic.
Route::get('/', function (\App\Domain\Catalog\Queries\PublicCatalog $catalog) {
    if (app()->environment('staging')) {
        return response()->file(public_path('fe06/index.html'), [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, max-age=0',
        ]);
    }

    return app(CatalogController::class)->home($catalog);
})->name('home');
// The functional Laravel storefront is available alongside the FE-06 presentation.
// No demo payment, synthetic order submission or unsafe impersonation routes are exposed.
Route::get('/loja', [CatalogController::class, 'home'])->name('storefront.live');
Route::get('/midia/{media}', [ProductMediaController::class, 'show'])->name('media.show');
Route::get('/demo', DemoHubController::class)->middleware('throttle:api-public')->name('storefront.demo');
Route::redirect('/demo/guia', '/demo')->name('demo.index');
Route::get('/demo/painel/{role}', DemoRoleController::class)->middleware('throttle:api-public')->name('demo.role');
Route::get('/demo/ofertas/{key}', DemoProductRedirectController::class)
    ->middleware('throttle:api-public')->name('demo.product');
Route::post('/demo/comprador', [DemoJourneyController::class, 'start'])
    ->middleware('throttle:5,60')->name('demo.start');
Route::get('/buscar', [CatalogController::class, 'search'])->name('storefront.search');
Route::get('/categorias/{category:slug}', [CatalogController::class, 'category'])->name('storefront.category');
Route::get('/ofertas/{offer}', [CatalogController::class, 'show'])->name('storefront.offer');

// Stateful OAuth/OIDC browser flow. Socialite state protection stays enabled.
Route::get('/auth/sso/{provider}/redirect', [SsoController::class, 'redirect'])
    ->middleware('throttle:sso')->name('sso.redirect');
Route::get('/auth/sso/{provider}/callback', [SsoController::class, 'callback'])
    ->middleware('throttle:sso')->name('sso.callback');

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

Route::middleware(['auth', 'verified', 'throttle:10,1'])->prefix('seguranca/mfa')->name('security.mfa.')->group(function (): void {
    Route::get('/', [MfaController::class, 'show'])->name('show');
    Route::post('/iniciar', [MfaController::class, 'begin'])->name('begin');
    Route::post('/confirmar', [MfaController::class, 'confirm'])->name('confirm');
    Route::post('/validar', [MfaController::class, 'challenge'])->name('challenge');
    Route::post('/recuperacao', [MfaController::class, 'regenerate'])->name('regenerate');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::view('/minha-conta', 'buyer.account')->name('buyer.account');
    Route::get('/sacola', [ShoppingPageController::class, 'cart'])->name('buyer.cart.page');
    Route::get('/favoritos', [ShoppingPageController::class, 'wishlist'])->name('buyer.wishlist.page');
    Route::get('/minha-conta/enderecos', [AddressController::class, 'index'])->name('buyer.addresses.index');
    Route::post('/minha-conta/enderecos', [AddressController::class, 'store'])->name('buyer.addresses.store');
    Route::delete('/minha-conta/enderecos/{address}', [AddressController::class, 'destroy'])->name('buyer.addresses.destroy');
    Route::get('/minha-conta/resumo-compra', CheckoutPreviewController::class)->name('buyer.checkout.preview');
    Route::get('/minha-conta/preparar-compra', [CheckoutPreparationController::class, 'page'])->name('buyer.checkout.prepare');

    // JSON endpoints share the browser's verified session and CSRF protection.
    // They store interests only; stock is not reserved and checkout is disabled.
    Route::get('/minha-conta/carrinho', [CartController::class, 'index'])->name('buyer.cart.index');
    Route::put('/minha-conta/carrinho/{offer}', [CartController::class, 'put'])->name('buyer.cart.put');
    Route::delete('/minha-conta/carrinho/{offer}', [CartController::class, 'destroy'])->name('buyer.cart.destroy');
    Route::get('/minha-conta/favoritos', [WishlistController::class, 'index'])->name('buyer.wishlist.index');
    Route::put('/minha-conta/favoritos/{offer}', [WishlistController::class, 'put'])->name('buyer.wishlist.put');
    Route::delete('/minha-conta/favoritos/{offer}', [WishlistController::class, 'destroy'])->name('buyer.wishlist.destroy');
    Route::get('/vender/cadastro', [SellerOnboardingController::class, 'create'])->name('seller.apply');
    Route::post('/vender/cadastro', [SellerOnboardingController::class, 'store'])->name('seller.submit');
    Route::get('/vendedor/{seller}/painel', [SellerDashboardController::class, 'show'])
        ->middleware('seller.member')->name('seller.dashboard');
    Route::prefix('vendedor/{seller}')->name('seller.')->middleware('seller.member')->group(function (): void {
        Route::get('/equipe', [SellerTeamController::class, 'index'])->name('team.index');
        Route::post('/equipe/convites', [SellerTeamController::class, 'invite'])->middleware('throttle:6,1')->name('team.invite');
        Route::delete('/equipe/convites/{invitation}', [SellerTeamController::class, 'cancel'])->name('team.cancel');
        Route::delete('/equipe/membros/{membership}', [SellerTeamController::class, 'remove'])->name('team.remove');
        Route::get('/origens', [ShippingOriginController::class, 'index'])->name('origins.index');
        Route::post('/origens', [ShippingOriginController::class, 'store'])->name('origins.store');
        Route::delete('/origens/{origin}', [ShippingOriginController::class, 'destroy'])->name('origins.destroy');
        Route::get('/ofertas', [SellerOfferController::class, 'index'])->name('offers.index');
        Route::get('/ofertas/nova', [SellerOfferController::class, 'create'])->name('offers.create');
        Route::post('/ofertas/{offer}/midias', [ProductMediaController::class, 'sellerStore'])->middleware('throttle:10,1')->name('offers.media.store');
        Route::post('/ofertas', [SellerOfferController::class, 'store'])->name('offers.store');
    });
    Route::get('/convites/{invitation}/{token}', [SellerTeamController::class, 'showInvitation'])->name('seller.team.accept.show');
    Route::post('/convites/{invitation}/{token}', [SellerTeamController::class, 'accept'])->name('seller.team.accept');
    Route::prefix('admin')->name('admin.')->middleware(['can:review-sellers', 'admin.mfa'])->group(function (): void {
        Route::get('/gerenciar', [CatalogManagementController::class, 'index'])->name('manage');
        Route::post('/departamentos', [CatalogManagementController::class, 'category'])->name('categories.store');
        Route::post('/departamentos/{category}/alternar', [CatalogManagementController::class, 'toggle'])->name('categories.toggle');
        Route::post('/produtos', [CatalogManagementController::class, 'store'])->name('offers.admin.store');
        Route::patch('/produtos/{offer}', [CatalogManagementController::class, 'update'])->name('offers.admin.update');
        Route::post('/produtos/{offer}/despublicar', [CatalogManagementController::class, 'unpublish'])->name('offers.unpublish');
        Route::post('/produtos/{offer}/midias', [ProductMediaController::class, 'adminStore'])->middleware('throttle:10,1')->name('offers.media.store');
        Route::post('/produtos/{offer}/midias/{media}/capa', [ProductMediaController::class, 'adminCover'])->name('offers.media.cover');
        Route::delete('/produtos/{offer}/midias/{media}', [ProductMediaController::class, 'adminDestroy'])->name('offers.media.destroy');
        Route::get('/catalogo', [CatalogModerationController::class, 'index'])->name('catalog.index');
        Route::post('/catalogo/{offer}/aprovar', [CatalogModerationController::class, 'approve'])->name('catalog.approve');
        Route::post('/catalogo/{offer}/rejeitar', [CatalogModerationController::class, 'reject'])->name('catalog.reject');
        Route::get('/empresas', [AdminSellerController::class, 'index'])->name('sellers.index');
        Route::post('/empresas/{seller}/aprovar', [AdminSellerController::class, 'approve'])->name('sellers.approve');
        Route::post('/empresas/{seller}/rejeitar', [AdminSellerController::class, 'reject'])->name('sellers.reject');
    });
});


// Synthetic orders are isolated from the commercial orders/payment/stock tables.
Route::middleware(['auth', 'verified', 'throttle:60,1'])->prefix('demo')->name('demo.')->group(function (): void {
    Route::get('/checkout', [DemoJourneyController::class, 'checkout'])->name('checkout');
    Route::post('/pedidos', [DemoJourneyController::class, 'create'])->middleware('throttle:10,1')->name('create');
    Route::get('/pedidos', [DemoJourneyController::class, 'index'])->name('orders');
    Route::get('/pedidos/{order}', [DemoJourneyController::class, 'show'])->name('order');
    Route::post('/pedidos/{order}/etapa', [DemoJourneyController::class, 'transition'])->middleware('throttle:10,1')->name('transition');
});

// Same-origin BFF shares Laravel web sessions and CSRF middleware.
require __DIR__.'/bff.php';
