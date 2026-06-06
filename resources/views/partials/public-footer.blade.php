<footer class="mt-20 bg-primary-950 text-slate-300">
    <div class="container-px grid grid-cols-2 gap-8 py-14 md:grid-cols-4">
        <div class="col-span-2 md:col-span-1">
            <div class="flex items-center gap-2 text-white">
                <span class="grid h-9 w-9 place-items-center rounded-[10px] bg-primary-500" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 2.5 15.3 0 18M12 3c-2.5 2.7-2.5 15.3 0 18"/></svg>
                </span>
                <span class="text-lg font-extrabold">Planetic Web</span>
            </div>
            <p class="mt-4 max-w-xs text-sm text-slate-400">
                Domains, hosting, DNS and bespoke websites — built, secured and managed for you.
            </p>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-white">Services</h2>
            <ul class="mt-4 space-y-2 text-sm">
                <li><a class="hover:text-white" href="{{ route('website-package') }}">£200 Website Package</a></li>
                <li><a class="hover:text-white" href="{{ route('hosting.index') }}">Hosting Packages</a></li>
                <li><a class="hover:text-white" href="{{ route('domains.index') }}">Domain Search</a></li>
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-white">Legal</h2>
            <ul class="mt-4 space-y-2 text-sm">
                <li><a class="hover:text-white" href="{{ route('legal.privacy') }}">Privacy Policy</a></li>
                <li><a class="hover:text-white" href="{{ route('legal.terms') }}">Terms of Use</a></li>
                <li><a class="hover:text-white" href="{{ route('legal.renewal') }}">Renewal Policy</a></li>
                <li><a class="hover:text-white" href="{{ route('legal.refund') }}">Refund Policy</a></li>
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-white">Contact</h2>
            <ul class="mt-4 space-y-2 text-sm">
                <li><a class="hover:text-white" href="{{ route('contact') }}">Contact Us</a></li>
                <li><a class="hover:text-white" href="mailto:{{ config('billing.support_email') }}">{{ config('billing.support_email') }}</a></li>
            </ul>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-px flex flex-col items-center justify-between gap-2 py-6 text-sm text-slate-400 sm:flex-row">
            <p>&copy; {{ date('Y') }} Planetic Web. All rights reserved.</p>
            <p class="flex items-center gap-3">
                <span class="badge badge-info"><span class="badge-dot"></span> SSL Secured</span>
                <span class="badge badge-primary"><span class="badge-dot"></span> Cloudflare</span>
            </p>
        </div>
    </div>
</footer>
