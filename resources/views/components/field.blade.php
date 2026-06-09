@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'autocomplete' => null,
    'help' => null,
    'placeholder' => null,
    'autofocus' => false,
])

@php
    $id = $attributes->get('id', $name);
    $errId = $id.'-error';
    $helpId = $id.'-help';
    $hasError = $errors->has($name);
    $isPassword = $type === 'password';
    $fieldValue = $isPassword ? '' : old($name, $value);
    $describedBy = $hasError ? $errId : ($help ? $helpId : null);
    $inputClass = 'input'.($hasError ? ' input-error' : '').($isPassword ? ' pr-12' : '');
@endphp

<div>
    <label for="{{ $id }}" class="label">
        {{ $label }}@if ($required)<span class="required-mark" aria-hidden="true"> *</span><span class="sr-only"> (required)</span>@endif
    </label>

    @if ($isPassword)
        {{-- Accessible show/hide password toggle (progressive enhancement via Alpine). --}}
        <div x-data="{ show: false }" class="relative">
            <input
                type="password"
                x-bind:type="show ? 'text' : 'password'"
                id="{{ $id }}"
                name="{{ $name }}"
                value=""
                @if ($required) required @endif
                @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                @if ($autofocus) autofocus @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                @if ($hasError) aria-invalid="true" @endif
                {{ $attributes->merge(['class' => $inputClass]) }}
            >
            <button
                type="button"
                @click="show = ! show"
                aria-controls="{{ $id }}"
                aria-pressed="false"
                x-bind:aria-pressed="show ? 'true' : 'false'"
                aria-label="Show password"
                x-bind:aria-label="show ? 'Hide password' : 'Show password'"
                class="absolute inset-y-0 right-0 flex items-center rounded-r-[10px] px-3 text-slate-500 transition hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-cyan"
            >
                <svg x-show="!show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg x-show="show" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><path d="M6.61 6.61A18.45 18.45 0 0 0 2 12s3.5 7 10 7a9.12 9.12 0 0 0 5.39-1.61M22 22 2 2"/></svg>
            </button>
        </div>
    @else
        <input
            type="{{ $type }}"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $fieldValue }}"
            @if ($required) required @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($autofocus) autofocus @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($hasError) aria-invalid="true" @endif
            {{ $attributes->merge(['class' => $inputClass]) }}
        >
    @endif

    @if ($help && ! $hasError)
        <p id="{{ $helpId }}" class="help-text">{{ $help }}</p>
    @endif

    @error($name)
        <p id="{{ $errId }}" class="field-error" role="alert">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <span>{{ $message }}</span>
        </p>
    @enderror
</div>
