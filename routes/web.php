<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\DomainSearchController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LegalController;
use App\Http\Controllers\Public\PricingController;
use App\Http\Controllers\Public\WebsitePackageController;
use App\Http\Controllers\Webhook\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Marketing Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/website-package', [WebsitePackageController::class, 'index'])->name('website-package');
Route::get('/hosting', [PricingController::class, 'index'])->name('hosting.index');
Route::get('/domains', [DomainSearchController::class, 'index'])->name('domains.index');

Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('contact.store');

// SEO
Route::get('/sitemap.xml', [\App\Http\Controllers\Public\SeoController::class, 'sitemap'])->name('sitemap');

// Legal pages
Route::get('/privacy-policy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/renewal-policy', [LegalController::class, 'renewal'])->name('legal.renewal');
Route::get('/refund-policy', [LegalController::class, 'refund'])->name('legal.refund');

/*
|--------------------------------------------------------------------------
| Domain Search + Cart (internal JSON API consumed by the frontend)
|--------------------------------------------------------------------------
| The frontend never calls third-party APIs directly — it only calls these
| Laravel endpoints, which proxy to the registrar behind a service class.
*/
Route::post('/domains/search', [DomainSearchController::class, 'search'])
    ->middleware('throttle:20,1')
    ->name('domains.search');

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/items', [CartController::class, 'store'])->name('items.store');
    Route::delete('/items/{cartItem}', [CartController::class, 'destroy'])->name('items.destroy');
});

/*
|--------------------------------------------------------------------------
| Checkout (auth required before paying — email verification is NOT a
| precondition: it happens in the background and never blocks a purchase)
|--------------------------------------------------------------------------
*/
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');

    // Domain choice for hosting / website-package orders: register new, use
    // an existing domain, or (website package only) decide later.
    Route::post('/domain', [CheckoutController::class, 'setDomain'])
        ->middleware(['auth', 'throttle:20,1'])
        ->name('domain');

    // On-site payment: creates/reuses the order + Stripe PaymentIntent and
    // returns its client_secret. Requires a signed-in customer only.
    Route::post('/payment-intent', [CheckoutController::class, 'paymentIntent'])
        ->middleware(['auth', 'throttle:10,1'])
        ->name('payment-intent');

    Route::get('/success', [CheckoutController::class, 'success'])->name('success');
    Route::get('/cancel', [CheckoutController::class, 'cancel'])->name('cancel');
});

/*
|--------------------------------------------------------------------------
| Stripe Webhook — public, signature-verified, never CSRF-protected
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/stripe', StripeWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.stripe');

require __DIR__.'/auth.php';
require __DIR__.'/customer.php';
