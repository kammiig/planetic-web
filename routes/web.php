<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\DomainSearchController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LegalController;
use App\Http\Controllers\Public\PricingController;
use App\Http\Controllers\Public\RegionHintController;
use App\Http\Controllers\Public\WebsitePackageController;
use App\Http\Controllers\Webhook\StripeWebhookController;
use App\Support\Region;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Regional Storefronts
|--------------------------------------------------------------------------
| Every public page is registered once per region (config/regions.php):
|
|     /            → UK, GBP      — canonical root, bare route names
|     /int/…       → World, USD   — route names prefixed "int."
|
| The prefix is what determines a request's region; geolocation never changes
| what a URL returns. That keeps each URL independently cacheable by Cloudflare
| (which will not vary its cache by CF-IPCountry outside Enterprise) and keeps
| both versions crawlable by Googlebot, which crawls mainly from US IPs.
|
| The default region keeps the bare route names, so every existing route()
| call, bookmark, backlink and sitemap entry continues to resolve unchanged.
*/

$storefront = function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/website-package', [WebsitePackageController::class, 'index'])->name('website-package');
    Route::get('/hosting', [PricingController::class, 'index'])->name('hosting.index');
    Route::get('/domains', [DomainSearchController::class, 'index'])->name('domains.index');

    // Blog
    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

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
    | Domain Search + Cart (internal JSON API consumed by the frontend).
    | The frontend never calls third-party APIs directly — it only calls these
    | Laravel endpoints, which proxy to the registrar behind a service class.
    | These live inside the regional tree so that prices quoted to the visitor,
    | and the currency stamped on a newly created cart, match the storefront
    | they are actually browsing.
    */
    Route::post('/domains/search', [DomainSearchController::class, 'search'])
        ->middleware('throttle:20,1')
        ->name('domains.search');

    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::post('/items', [CartController::class, 'store'])->name('items.store');
        Route::delete('/items/{cartItem}', [CartController::class, 'destroy'])->name('items.destroy');
    });
};

foreach (Region::keys() as $regionKey) {
    $region = Region::make($regionKey);

    Route::middleware("region:{$regionKey}")
        ->prefix($region->prefix())
        ->name($region->isDefault() ? '' : $regionKey.'.')
        ->group($storefront);
}

/*
|--------------------------------------------------------------------------
| Region hint (geolocation) — deliberately uncacheable
|--------------------------------------------------------------------------
| The ONLY place the visitor's country is used. Returns the suggested region
| as JSON with no-store, so the page HTML can stay identical for everybody
| (and therefore cacheable) while still offering a client-side prompt to
| switch storefronts. Never redirects; never varies a rendered page.
*/
Route::get('/region-hint', RegionHintController::class)
    ->middleware('throttle:60,1')
    ->name('region.hint');

// SEO — one sitemap covering every regional tree.
Route::get('/sitemap.xml', [\App\Http\Controllers\Public\SeoController::class, 'sitemap'])->name('sitemap');

/*
|--------------------------------------------------------------------------
| Checkout (auth required before paying — email verification is NOT a
| precondition: it happens in the background and never blocks a purchase)
|--------------------------------------------------------------------------
| Single, region-neutral tree. Checkout displays the currency STORED on the
| cart/order, which was locked when the cart was created — never the currency
| of whatever URL the visitor happens to be on. A visitor who switches region
| mid-session must not see the price of a pending order move.
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

    // Free (£0) orders: completed without Stripe (no PaymentIntent), provisioning
    // starts immediately. Used for free first-year hosting + free domain bundles.
    Route::post('/complete-free', [CheckoutController::class, 'completeFree'])
        ->middleware(['auth', 'throttle:10,1'])
        ->name('complete-free');

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
