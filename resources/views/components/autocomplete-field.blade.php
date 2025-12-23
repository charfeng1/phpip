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
    @props string $rowClass - CSS class for field wrapper (default: 'row mb-2')
    @props string $labelClass - CSS class for label (default: 'col-4 col-form-label')
    @props string $inputClass - CSS class for search input (default: 'form-control form-control-sm combobox-input')
    @props string $selectClass - CSS class for hidden select (default: 'form-select d-none combobox-select')

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
    'rowClass' => 'row mb-2',
    'labelClass' => 'col-4 col-form-label',
    'inputClass' => 'form-control form-control-sm combobox-input',
    'selectClass' => 'form-select d-none combobox-select',
])

@php
    $inputId = $id.'Input';
    $listId = $id.'Options';
    $selectId = $id.'Select';
    $placeholder = $placeholder ?? __('Filter options...');
@endphp

<div class="{{ $rowClass }}">
  <label for="{{ $inputId }}" class="{{ $labelClass }}">{{ $label }}</label>
  <div class="col-8">
    <div class="combobox">
      <input type="search"
             class="{{ $inputClass }}"
             placeholder="{{ $placeholder }}"
             list="{{ $listId }}"
             data-combobox-target="#{{ $selectId }}"
             id="{{ $inputId }}"
             value="{{ $selectedLabel ?? '' }}"
             autocomplete="off">
      <datalist id="{{ $listId }}">
        @foreach ($options as $option)
          <option value="{{ data_get($option, $optionLabel) }}" data-code="{{ data_get($option, $optionValue) }}"></option>
        @endforeach
      </datalist>
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
