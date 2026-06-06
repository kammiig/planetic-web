@if (session('status') || session('success') || session('error') || session('warning') || session('info'))
    <div class="container-px pt-4" role="status" aria-live="polite">
        @foreach (['success' => 'success', 'status' => 'success', 'info' => 'info', 'warning' => 'warning', 'error' => 'danger'] as $key => $variant)
            @if (session($key))
                <div class="alert alert-{{ $variant }} mb-3">
                    {{ session($key) }}
                </div>
            @endif
        @endforeach
    </div>
@endif
