@props([
    'status' => null,
    'label' => null,
    'variant' => null,
])

@php
    // Accepts a backed status enum (with label()/badgeClass()) or explicit
    // label + variant. Always renders text alongside colour (WCAG: never
    // colour alone).
    if (is_object($status) && method_exists($status, 'badgeClass')) {
        $badgeClass = $status->badgeClass();
        $text = $status->label();
    } else {
        $badgeClass = $variant ?? 'badge-neutral';
        $text = $label ?? (is_string($status) ? $status : '');
    }
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$badgeClass]) }}>
    <span class="badge-dot" aria-hidden="true"></span>{{ $text }}
</span>
