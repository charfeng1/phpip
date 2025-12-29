{{--
    Autocomplete field component with combobox functionality.

    Provides a searchable autocomplete input field backed by a hidden select element.
    Combines a datalist for search/filter functionality with a select for form submission.
    Typically used with JavaScript to enable interactive filtering and selection.

    @props string $id - Base ID for the field (used to generate input, list, and select IDs)
    @props string $name - Form field name for the hidden select element
    @props string $label - Label text displayed before the field
    @props array $options - Array of option objects/arrays to populate the field
    @props string $optionValue - Key/property name for option values (default: 'code')
    @props string $optionLabel - Key/property name for option labels (default: 'name')
    @props string $selectedValue - Currently selected option value (default: '')
    @props string $selectedLabel - Currently selected option label (default: '')
    @props bool $required - Whether the field is required (default: false)
    @props string|null $noneLabel - Label for empty option, if provided (default: null)
    @props string|null $placeholder - Placeholder text for search input (default: 'Filter options...')
    @props string $rowClass - CSS class for field wrapper (default: 'flex items-center gap-2 mb-2')
    @props string $labelClass - CSS class for label (default: 'w-1/3 font-semibold text-sm')
    @props string $inputClass - CSS class for search input (default: 'input input-bordered input-sm w-full combobox-input')
    @props string $selectClass - CSS class for hidden select (default: 'hidden combobox-select')

    @example
    <x-autocomplete-field
        id="country"
        name="country_code"
        label="Country"
        :options="$countries"
        option-value="iso"
        option-label="name"
        selected-value="US"
        selected-label="United States"
        :required="true" />
--}}
@props([
    'id',
    'name',
    'label',
    'options' => [],
    'optionValue' => 'code',
    'optionLabel' => 'name',
    'selectedValue' => '',
    'selectedLabel' => '',
    'required' => false,
    'noneLabel' => null,
    'placeholder' => null,
    'rowClass' => 'flex items-center gap-2 mb-2',
    'labelClass' => 'w-1/3 font-semibold text-sm',
    'inputClass' => 'input input-bordered input-sm w-full combobox-input',
    'selectClass' => 'hidden combobox-select',
])

@php
    $inputId = $id.'Input';
    $listId = $id.'Options';
    $selectId = $id.'Select';
    // Determine placeholder based on context
    $placeholder = $placeholder ?? __('Select an option');
    // Display value: show selected label, or placeholder text if empty and has noneLabel
    $displayValue = $selectedLabel ?: '';
@endphp

<div class="{{ $rowClass }}">
  <label for="{{ $inputId }}" class="{{ $labelClass }}">{{ $label }}</label>
  <div class="flex-1 relative">
    <div class="combobox relative">
      <input type="search"
             class="{{ $inputClass }} pr-8"
             placeholder="{{ $placeholder }}"
             list="{{ $listId }}"
             data-combobox-target="#{{ $selectId }}"
             data-empty-text="{{ $noneLabel ?? __('Select an option') }}"
             id="{{ $inputId }}"
             value="{{ $displayValue }}"
             autocomplete="off">
      <datalist id="{{ $listId }}">
        @foreach ($options as $option)
          <option value="{{ data_get($option, $optionLabel) }}" data-code="{{ data_get($option, $optionValue) }}"></option>
        @endforeach
      </datalist>
      {{-- Dropdown indicator --}}
      <span class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-base-content/40">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
      </span>
    </div>
    <select class="{{ $selectClass }}"
            id="{{ $selectId }}"
            name="{{ $name }}"
            @if ($required) required @endif>
      @if ($noneLabel)
        <option value="">{{ $noneLabel }}</option>
      @else
        <option value="" disabled {{ empty($selectedValue) ? 'selected' : '' }}>
          {{ __('Select an option') }}
        </option>
      @endif
      @foreach ($options as $option)
        @php
            $optionValueData = data_get($option, $optionValue);
        @endphp
        <option value="{{ $optionValueData }}"
          @selected((string) $selectedValue === (string) $optionValueData)>
          {{ data_get($option, $optionLabel) }}
        </option>
      @endforeach
    </select>
  </div>
</div>
