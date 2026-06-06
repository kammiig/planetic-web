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
    $fieldValue = $type === 'password' ? '' : old($name, $value);
    $describedBy = $hasError ? $errId : ($help ? $helpId : null);
@endphp

<div>
    <label for="{{ $id }}" class="label">
        {{ $label }}@if ($required)<span class="required-mark" aria-hidden="true"> *</span><span class="sr-only"> (required)</span>@endif
    </label>

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
        {{ $attributes->merge(['class' => 'input'.($hasError ? ' input-error' : '')]) }}
    >

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
