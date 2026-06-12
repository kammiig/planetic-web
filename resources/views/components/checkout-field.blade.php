@props([
    'name',
    'label',
    'type' => 'text',
    'autocomplete' => null,
    'required' => false,
    'value' => null,
    'help' => null,
])

@php
    $id = 'co_'.$name;
    $describedBy = trim(($help ? $id.'_help ' : '').$id.'_err');
    $isPassword = $type === 'password';
@endphp

<div>
    <label for="{{ $id }}" class="label">
        {{ $label }}@if ($required)<span class="required-mark" aria-hidden="true"> *</span><span class="sr-only"> (required)</span>@endif
    </label>

    @if ($isPassword)
        {{-- Accessible show/hide password toggle (keyboard + screen-reader friendly). --}}
        <div x-data="{ show: false }" class="relative">
            <input
                type="password"
                x-bind:type="show ? 'text' : 'password'"
                id="{{ $id }}"
                name="{{ $name }}"
                value=""
                @if ($required) required @endif
                @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                aria-describedby="{{ $describedBy }}"
                {{ $attributes->merge(['class' => 'input pr-12']) }}
                x-bind:class="fieldError('{{ $name }}') && 'input-error'"
                x-bind:aria-invalid="fieldError('{{ $name }}') ? 'true' : null"
            >
            <button
                type="button"
                @click="show = ! show"
                aria-controls="{{ $id }}"
                aria-pressed="false"
                x-bind:aria-pressed="show ? 'true' : 'false'"
                aria-label="Show password"
                x-bind:aria-label="show ? 'Hide password' : 'Show password'"
                class="absolute inset-y-0 right-0 flex items-center rounded-r-[10px] px-3 text-slate-500 transition hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
            >
                <svg x-show="!show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg x-show="show" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><path d="M6.61 6.61A18.45 18.45 0 0 0 2 12s3.5 7 10 7a9.12 9.12 0 0 0 5.39-1.61M22 22 2 2"/></svg>
            </button>
        </div>
    @else
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            @if ($required) required @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            aria-describedby="{{ $describedBy }}"
            {{ $attributes->merge(['class' => 'input']) }}
            x-bind:class="fieldError('{{ $name }}') && 'input-error'"
            x-bind:aria-invalid="fieldError('{{ $name }}') ? 'true' : null"
        >
    @endif

    @if ($help)
        <p id="{{ $id }}_help" class="help-text">{{ $help }}</p>
    @endif

    <p id="{{ $id }}_err" class="field-error" role="alert" x-show="fieldError('{{ $name }}')" x-text="fieldError('{{ $name }}')" x-cloak></p>
</div>
