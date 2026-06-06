<?php

namespace App\Http\Controllers\Public;

use App\Actions\Domains\CheckDomainAvailability;
use App\Exceptions\RegistrarException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DomainSearchRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DomainSearchController extends Controller
{
    public function index(): View
    {
        return view('public.domain-search');
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
            return response()->json($action->handle($domain));
        } catch (RegistrarException $e) {
            Log::channel('stack')->warning('Domain search failed', [
                'domain' => $domain,
                'registrar' => $e->registrar,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->safeMessage,
            ], 502);
        }
    }
}
