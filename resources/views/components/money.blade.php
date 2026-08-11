@props(['amount' => 0, 'currency' => null, 'code' => false, 'compact' => false])
{{-- Wrapper form of the money() helper, for when the amount needs its own
     element (attributes, Alpine bindings, a nowrap class). Prefer money() in
     plain text — it produces no extra markup. --}}
<span {{ $attributes }}>{{ $compact ? \App\Support\Money::compact($amount, $currency) : \App\Support\Money::format($amount, $currency, $code) }}</span>
