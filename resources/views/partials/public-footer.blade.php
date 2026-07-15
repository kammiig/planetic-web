@php
    $socials = array_filter([
        'facebook' => setting('social.facebook'),
        'twitter' => setting('social.twitter'),
        'instagram' => setting('social.instagram'),
        'linkedin' => setting('social.linkedin'),
    ]);
    $socialPaths = [
        'facebook' => 'M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z',
        'twitter' => 'M18.9 2H22l-7.5 8.6L23 22h-6.8l-5.3-7-6.1 7H1.7l8-9.2L1 2h7l4.8 6.4L18.9 2z',
        'instagram' => 'M12 2.2c3.2 0 3.6 0 4.9.07 3.3.15 4.8 1.7 5 5 .06 1.3.07 1.6.07 4.8s0 3.5-.07 4.8c-.15 3.2-1.7 4.8-5 5-1.3.06-1.6.07-4.9.07s-3.6 0-4.9-.07c-3.3-.15-4.8-1.7-5-5C2.04 15.5 2 15.2 2 12s0-3.5.07-4.8c.15-3.3 1.7-4.8 5-5C8.4 2.2 8.8 2.2 12 2.2zm0 3.4a6.4 6.4 0 1 0 0 12.8 6.4 6.4 0 0 0 0-12.8zm0 10.5a4.1 4.1 0 1 1 0-8.2 4.1 4.1 0 0 1 0 8.2zm6.6-10.9a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z',
        'linkedin' => 'M4.98 3.5A2.5 2.5 0 1 1 0 3.5a2.5 2.5 0 0 1 4.98 0zM0 8h5v16H0zM7.5 8h4.8v2.2h.07c.67-1.2 2.3-2.5 4.7-2.5 5 0 5.9 3.3 5.9 7.6V24h-5v-7.1c0-1.7 0-3.9-2.4-3.9s-2.7 1.8-2.7 3.8V24h-5z',
    ];
@endphp
<footer class="bg-primary-950 text-slate-300">
    <div class="container-px grid grid-cols-2 gap-8 py-14 md:grid-cols-5">
        <div class="col-span-2">
            <div class="flex items-center gap-2 text-white">
                <span class="grid h-9 w-9 place-items-center rounded-[10px] bg-primary-500" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 2.5 15.3 0 18M12 3c-2.5 2.7-2.5 15.3 0 18"/></svg>
                </span>
                <span class="text-lg font-extrabold">{{ setting('company.name', 'Planetic Web') }}</span>
            </div>
            <p class="mt-4 max-w-xs text-sm text-slate-400">
                {{ setting('footer.tagline', 'Domains, hosting, DNS and bespoke websites — built, secured and managed for you.') }}
            </p>
            @if ($socials)
                <div class="mt-5 flex gap-3">
                    @foreach ($socials as $key => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ ucfirst($key) }}"
                           class="grid h-9 w-9 place-items-center rounded-full bg-white/10 text-white transition hover:bg-primary-500">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $socialPaths[$key] }}"/></svg>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <h2 class="text-sm font-semibold text-white">Services</h2>
            <ul class="mt-4 space-y-2 text-sm">
                <li><a class="hover:text-white" href="{{ route('website-package') }}">Website Package</a></li>
                <li><a class="hover:text-white" href="{{ route('hosting.index') }}">Hosting Plans</a></li>
                <li><a class="hover:text-white" href="{{ route('domains.index') }}">Domain Search</a></li>
                <li><a class="hover:text-white" href="{{ route('blog.index') }}">Blog</a></li>
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
                @if ($email = setting('contact.email', 'support@planeticweb.com'))
                    <li><a class="hover:text-white" href="mailto:{{ $email }}">{{ $email }}</a></li>
                @endif
                @if ($phone = setting('contact.phone'))
                    <li><a class="hover:text-white" href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a></li>
                @endif
                @if ($address = setting('contact.address'))
                    <li class="text-slate-400">{{ $address }}</li>
                @endif
                @if ($hours = setting('contact.hours'))
                    <li class="text-slate-400">{{ $hours }}</li>
                @endif
            </ul>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-px flex flex-col items-center justify-between gap-2 py-6 text-sm text-slate-400 sm:flex-row">
            <p>&copy; {{ date('Y') }} {{ setting('company.name', 'Planetic Web') }}. All rights reserved.</p>
            <p class="flex items-center gap-3">
                <span class="badge badge-info"><span class="badge-dot"></span> SSL Secured</span>
                <span class="badge badge-primary"><span class="badge-dot"></span> Cloudflare</span>
            </p>
        </div>
    </div>
</footer>
