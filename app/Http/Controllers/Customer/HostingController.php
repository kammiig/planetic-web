<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\HostingAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HostingController extends Controller
{
    public function index(Request $request): View
    {
        return view('customer.hosting.index', [
            'accounts' => $request->user()->hostingAccounts()->with(['hostingPackage', 'order'])->latest()->paginate(15),
        ]);
    }

    public function show(Request $request, HostingAccount $hostingAccount): View
    {
        abort_unless($hostingAccount->isOwnedBy($request->user()), 404);

        $hostingAccount->load('hostingPackage', 'domain');

        return view('customer.hosting.show', ['account' => $hostingAccount]);
    }
}
