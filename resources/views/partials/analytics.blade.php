@php $gaId = trim((string) setting('analytics.ga_id')); @endphp
@if ($gaId !== '')
    {{-- Google tag (gtag.js). The measurement ID is admin-editable in
         Admin → Website Content → Analytics; clear it there to disable. --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($gaId) }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($gaId));
    </script>
@endif
