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
