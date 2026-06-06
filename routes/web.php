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
| Checkout (auth + verified email required before paying)
|--------------------------------------------------------------------------
*/
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::post('/start', [CheckoutController::class, 'start'])
        ->middleware(['auth', 'verified', 'throttle:10,1'])
        ->name('start');
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
