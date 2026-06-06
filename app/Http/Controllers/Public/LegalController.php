<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return view('public.legal.privacy');
    }

    public function terms(): View
    {
        return view('public.legal.terms');
    }

    public function renewal(): View
    {
        return view('public.legal.renewal');
    }

    public function refund(): View
    {
        return view('public.legal.refund');
    }
}
