<?php

namespace App\Http\Controllers\Public;

use App\Actions\Domains\CheckDomainAvailability;
use App\Exceptions\RegistrarException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DomainSearchRequest;
use App\Models\TldPricing;
use App\Support\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DomainSearchController extends Controller
{
    public function index(): View
    {
        $currency = Region::current()->currency();

        // TLDs with no price in this storefront's currency are withheld rather
        // than listed at 0.00 — .co.uk is a UK-only name with no USD price.
        $sellable = fn ($query) => $query->orderBy('sort_order')->get()
            ->filter(fn (TldPricing $t) => $t->availableIn($currency))
            ->values();

        return view('public.domain-search', [
            // Supplied by RegionViewServiceProvider in this storefront's
            // currency; null when the package is not sold here.
            'freeYearNotice' => config('billing.website_package.free_year_notice'),
            'featuredTlds' => $sellable(TldPricing::active()->where('is_featured', true)),
            'tldPrices' => $sellable(TldPricing::active()),
        ]);
    }

    /**
     * Domain availability search (Ticket 17). The frontend calls this Laravel
     * endpoint only — never the registrar directly. Raw registrar errors are
     * logged privately and never returned to the customer.
     */
    public function search(DomainSearchRequest $request, CheckDomainAvailability $action): JsonResponse
    {
        $domain = $request->validated()['domain'];

        try {
            return response()->json($action->handle($domain, $request->boolean('full')));
        } catch (RegistrarException $e) {
            Log::channel('stack')->warning('Domain search failed', [
                'domain' => $domain,
                'registrar' => $e->registrar,
                'error' => $e->getMessage(),
            ]);

            // The registrar (Porkbun) rate-limits availability checks to about
            // one per 10 seconds. Tell the customer plainly that it is momentary
            // and to try again, rather than showing a scary generic error.
            if ($this->isRateLimited($e)) {
                return response()->json([
                    'success' => false,
                    'message' => 'A lot of domains are being checked right now. Please wait about 10 seconds and search again.',
                ], 429);
            }

            return response()->json([
                'success' => false,
                'message' => $e->safeMessage,
            ], 502);
        }
    }

    /** Whether a registrar failure was its (transient) availability rate limit. */
    private function isRateLimited(RegistrarException $e): bool
    {
        if (is_array($e->context) && strtoupper((string) ($e->context['code'] ?? '')) === 'RATE_LIMIT_EXCEEDED') {
            return true;
        }

        $message = strtolower($e->getMessage());

        return str_contains($message, 'rate limit')
            || str_contains($message, 'rate_limit')
            || str_contains($message, 'checks within');
    }
}
