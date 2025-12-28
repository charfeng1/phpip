{{--
    Form field wrapper component with consistent row-based layout.

    Provides a standardized form field layout with label and input in a Bootstrap row.
    Supports various input types and integrates with validation error display.

    @props string $name - Input field name (required)
    @props string $label - Label text (required)
    @props string $type - Input type: 'text', 'email', 'textarea', 'select', 'hidden' (default: 'text')
    @props mixed $value - Current field value (default: null)
    @props string|null $title - Tooltip text for label (default: null)
    @props bool $required - Whether field is required (default: false)
    @props string|null $placeholder - Placeholder text (default: null)
    @props string|null $helper - Helper text below input (default: null)
    @props array $options - Options for select type: array of ['value' => 'label'] (default: [])
    @props string $labelCol - Bootstrap column class for label (default: 'col-4')
    @props string $inputCol - Bootstrap column class for input (default: 'col-8')
    @props string $inputClass - Additional CSS classes for input (default: '')

    @example Basic text field
    <x-form-field name="name" label="Name" required />

    @example With tooltip and placeholder
    <x-form-field
        name="email"
        type="email"
        label="Email Address"
        title="Enter your primary email"
        placeholder="user@example.com" />

    @example Textarea
    <x-form-field
        name="notes"
        type="textarea"
        label="Notes"
        :value="$model->notes" />

    @example Select dropdown
    <x-form-field
        name="status"
        type="select"
        label="Status"
        :options="['active' => 'Active', 'inactive' => 'Inactive']"
        :value="$model->status" />
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'title' => null,
    'required' => false,
    'placeholder' => null,
    'helper' => null,
    'options' => [],
    'labelCol' => 'col-4',
    'inputCol' => 'col-8',
    'inputClass' => '',
])

@php
    $baseInputClass = 'form-control';
    $fullInputClass = trim($baseInputClass . ' ' . $inputClass);
    $hasError = isset($errors) && $errors->has($name);
    if ($hasError) {
        $fullInputClass .= ' is-invalid';
    }
@endphp

<div class="row mb-2">
    <label for="{{ $name }}" class="{{ $labelCol }} col-form-label @if($required) fw-bold @endif"
        @if($title) title="{{ $title }}" @endif>
        {{ $label }}@if($required) *@endif
    </label>
    <div class="{{ $inputCol }}">
        @if($type === 'textarea')
            <textarea
                class="{{ $fullInputClass }}"
                name="{{ $name }}"
                id="{{ $name }}"
                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                @if($required) required @endif
                {{ $attributes->except(['class', 'name', 'id', 'placeholder', 'required']) }}>{{ old($name, $value) }}</textarea>
        @elseif($type === 'select')
            <select
                class="form-select @if($hasError) is-invalid @endif {{ $inputClass }}"
                name="{{ $name }}"
                id="{{ $name }}"
                @if($required) required @endif
                {{ $attributes->except(['class', 'name', 'id', 'required']) }}>
                @if($placeholder)
                    <option value="">{{ $placeholder }}</option>
                @endif
                @foreach($options as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected(old($name, $value) == $optValue)>
                        {{ $optLabel }}
                    </option>
                @endforeach
            </select>
        @elseif($type === 'hidden')
            <input type="hidden" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $value) }}">
        @else
            <input
                type="{{ $type }}"
                class="{{ $fullInputClass }}"
                name="{{ $name }}"
                id="{{ $name }}"
                value="{{ old($name, $value) }}"
                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                @if($required) required @endif
                {{ $attributes->except(['class', 'name', 'id', 'value', 'placeholder', 'required', 'type']) }}>
        @endif

        {{-- Slot for additional content like autocomplete --}}
        {{ $slot }}

        @if($helper)
            <small class="form-text text-muted">{{ $helper }}</small>
        @endif

        @error($name)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
