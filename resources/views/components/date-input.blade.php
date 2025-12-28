{{--
    Locale-aware date input component.

    Provides a consistent date input with locale formatting support.
    Can display dates in the application's locale format while
    maintaining proper form submission.

    @props string $name - Input field name (required)
    @props mixed $value - Date value (Carbon, string, or null) (default: null)
    @props string|null $label - Optional label text (default: null)
    @props bool $required - Whether field is required (default: false)
    @props string|null $placeholder - Placeholder text (default: null)
    @props string $inputClass - Additional CSS classes (default: '')
    @props string $format - Display format using Carbon isoFormat (default: 'L' for locale short date)
    @props bool $useNative - Use native date input type (default: false)
    @props string|null $min - Minimum date (for native input) (default: null)
    @props string|null $max - Maximum date (for native input) (default: null)
    @props bool $inline - Display without row wrapper (default: false)

    @example Basic date input
    <x-date-input name="due_date" label="Due Date" />

    @example With current date as default
    <x-date-input name="event_date" :value="now()" required />

    @example Native HTML5 date input
    <x-date-input name="start_date" use-native :min="now()->toDateString()" />

    @example Inline (no row wrapper)
    <x-date-input name="filter_date" inline input-class="form-control-sm" />
--}}
@props([
    'name',
    'value' => null,
    'label' => null,
    'required' => false,
    'placeholder' => null,
    'inputClass' => '',
    'format' => 'L',
    'useNative' => false,
    'min' => null,
    'max' => null,
    'inline' => false,
])

@php
    use Carbon\Carbon;

    $displayValue = '';
    $nativeValue = '';

    if ($value) {
        $dateObj = $value instanceof Carbon ? $value : Carbon::parse($value);
        $displayValue = $dateObj->isoFormat($format);
        $nativeValue = $dateObj->format('Y-m-d');
    }

    $baseClass = 'form-control';
    $fullClass = trim($baseClass . ' ' . $inputClass);

    $hasError = isset($errors) && $errors->has($name);
    if ($hasError) {
        $fullClass .= ' is-invalid';
    }
@endphp

@if($inline)
    {{-- Inline mode: just the input, no wrapper --}}
    @if($useNative)
        <input type="date"
               class="{{ $fullClass }}"
               name="{{ $name }}"
               id="{{ $name }}"
               value="{{ old($name, $nativeValue) }}"
               @if($placeholder) placeholder="{{ $placeholder }}" @endif
               @if($required) required @endif
               @if($min) min="{{ $min }}" @endif
               @if($max) max="{{ $max }}" @endif
               {{ $attributes->except(['class', 'name', 'id', 'value', 'placeholder', 'required', 'min', 'max']) }}>
    @else
        <input type="text"
               class="{{ $fullClass }}"
               name="{{ $name }}"
               id="{{ $name }}"
               value="{{ old($name, $displayValue) }}"
               placeholder="{{ $placeholder ?? __('dd/mm/yyyy') }}"
               @if($required) required @endif
               {{ $attributes->except(['class', 'name', 'id', 'value', 'placeholder', 'required']) }}>
    @endif

    @if(isset($errors) && $errors->has($name))
        <div class="invalid-feedback">{{ $errors->first($name) }}</div>
    @endif
@else
    {{-- Standard row mode with label --}}
    <div class="row mb-2">
        @if($label)
            <label for="{{ $name }}" class="col-4 col-form-label @if($required) fw-bold @endif">
                {{ $label }}@if($required) *@endif
            </label>
        @endif
        <div class="{{ $label ? 'col-8' : 'col-12' }}">
            @if($useNative)
                <input type="date"
                       class="{{ $fullClass }}"
                       name="{{ $name }}"
                       id="{{ $name }}"
                       value="{{ old($name, $nativeValue) }}"
                       @if($placeholder) placeholder="{{ $placeholder }}" @endif
                       @if($required) required @endif
                       @if($min) min="{{ $min }}" @endif
                       @if($max) max="{{ $max }}" @endif
                       {{ $attributes->except(['class', 'name', 'id', 'value', 'placeholder', 'required', 'min', 'max']) }}>
            @else
                <input type="text"
                       class="{{ $fullClass }}"
                       name="{{ $name }}"
                       id="{{ $name }}"
                       value="{{ old($name, $displayValue) }}"
                       placeholder="{{ $placeholder ?? __('dd/mm/yyyy') }}"
                       @if($required) required @endif
                       {{ $attributes->except(['class', 'name', 'id', 'value', 'placeholder', 'required']) }}>
            @endif

            @if(isset($errors) && $errors->has($name))
                <div class="invalid-feedback">{{ $errors->first($name) }}</div>
            @endif
        </div>
    </div>
@endif
