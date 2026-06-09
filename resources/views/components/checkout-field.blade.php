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
@endphp

<div>
    <label for="{{ $id }}" class="label">
        {{ $label }}@if ($required)<span class="required-mark" aria-hidden="true"> *</span><span class="sr-only"> (required)</span>@endif
    </label>

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

    @if ($help)
        <p id="{{ $id }}_help" class="help-text">{{ $help }}</p>
    @endif

    <p id="{{ $id }}_err" class="field-error" role="alert" x-show="fieldError('{{ $name }}')" x-text="fieldError('{{ $name }}')" x-cloak></p>
</div>
