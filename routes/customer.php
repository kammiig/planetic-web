<?php

use App\Http\Controllers\Customer\AccountSettingsController;
use App\Http\Controllers\Customer\BillingController;
use App\Http\Controllers\Customer\DashboardController;
use App\Http\Controllers\Customer\DomainController;
use App\Http\Controllers\Customer\HostingController;
use App\Http\Controllers\Customer\SupportTicketController;
use App\Http\Controllers\Customer\WebsiteProjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Dashboard Routes
|--------------------------------------------------------------------------
| All routes require an authenticated customer. Email verification is
| encouraged via a persistent banner but never locks a paying customer out
| of their dashboard or services. Row-level ownership is enforced by
| policies + query scoping inside each controller — a customer can only
| ever see their own records.
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('dashboard')->name('customer.')->group(function () {
        // Domains
        Route::get('domains', [DomainController::class, 'index'])->name('domains.index');
        Route::get('domains/{domain}', [DomainController::class, 'show'])->name('domains.show');
        Route::get('domains/{domain}/dns', [DomainController::class, 'dns'])->name('domains.dns');

        // Hosting
        Route::get('hosting', [HostingController::class, 'index'])->name('hosting.index');
        Route::get('hosting/{hostingAccount}', [HostingController::class, 'show'])->name('hosting.show');
        Route::get('hosting/{hostingAccount}/cpanel', [HostingController::class, 'cpanelSso'])
            ->middleware('throttle:10,1')
            ->name('hosting.cpanel');

        // Billing & invoices
        Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
        Route::get('billing/payment-method', [BillingController::class, 'paymentMethod'])
            ->middleware('throttle:10,1')->name('billing.payment-method');
        Route::post('billing/domains/{domain}/auto-renew', [BillingController::class, 'toggleDomainAutoRenew'])
            ->middleware('throttle:30,1')->name('billing.domains.auto-renew');
        Route::post('billing/hosting/{hostingAccount}/auto-renew', [BillingController::class, 'toggleHostingAutoRenew'])
            ->middleware('throttle:30,1')->name('billing.hosting.auto-renew');
        Route::get('invoices/{invoice}', [BillingController::class, 'showInvoice'])->name('invoices.show');
        Route::get('invoices/{invoice}/download', [BillingController::class, 'downloadInvoice'])->name('invoices.download');

        // Late domain choice ("I'll decide later" orders)
        Route::post('orders/{order}/domain', [\App\Http\Controllers\Customer\OrderDomainController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('orders.domain');

        // Website projects (workspace: intake, messages, revisions, meetings)
        Route::get('website-projects', [WebsiteProjectController::class, 'index'])->name('projects.index');
        Route::get('website-projects/{project}', [WebsiteProjectController::class, 'show'])->name('projects.show');
        Route::post('website-projects/{project}/intake', [WebsiteProjectController::class, 'storeIntake'])->name('projects.intake');
        Route::post('website-projects/{project}/messages', [WebsiteProjectController::class, 'storeMessage'])->middleware('throttle:30,1')->name('projects.messages.store');
        Route::post('website-projects/{project}/revision', [WebsiteProjectController::class, 'requestRevision'])->middleware('throttle:10,1')->name('projects.revision');
        Route::post('website-projects/{project}/meeting', [WebsiteProjectController::class, 'requestMeeting'])->middleware('throttle:10,1')->name('projects.meeting');
        Route::get('website-projects/{project}/assets/{asset}', [WebsiteProjectController::class, 'downloadAsset'])->name('projects.assets.download');
        Route::get('website-projects/{project}/messages/{attachment}/download', [WebsiteProjectController::class, 'downloadMessageAttachment'])->name('projects.messages.download');

        // Support tickets
        Route::get('support', [SupportTicketController::class, 'index'])->name('support.index');
        Route::post('support', [SupportTicketController::class, 'store'])->middleware('throttle:10,1')->name('support.store');
        Route::get('support/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
        Route::post('support/{ticket}/reply', [SupportTicketController::class, 'reply'])->middleware('throttle:20,1')->name('support.reply');
        Route::get('support/{ticket}/attachments/{attachment}', [SupportTicketController::class, 'downloadAttachment'])->name('support.attachments.download');

        // Account settings
        Route::get('settings', [AccountSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [AccountSettingsController::class, 'update'])->name('settings.update');
    });
});
