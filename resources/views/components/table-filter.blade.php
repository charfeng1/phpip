{{--
    Table filter input component for table headers.

    Provides a consistent filter input with optional sort button for
    use in table header cells. Integrates with table filtering JS.

    @props string $name - Filter input name (required)
    @props string|null $placeholder - Placeholder text (default: null)
    @props string|null $value - Current filter value (default: null, uses Request::get)
    @props bool $sortable - Show sort button (default: false)
    @props string|null $sortKey - Sort key for the column (default: same as name)
    @props string|null $currentSort - Current sort key for active state (default: null)
    @props string|null $sortDir - Current sort direction 'asc' or 'desc' (default: null)
    @props string $width - Width style for the input group (default: 'auto')
    @props bool $clearable - Show clear button (default: true)
    @props string $inputClass - Additional CSS classes for input (default: '')

    @example Basic filter
    <x-table-filter name="Name" placeholder="Filter by name" />

    @example With sorting
    <x-table-filter
        name="Ref"
        placeholder="Reference"
        sortable
        sort-key="caseref"
        :current-sort="Request::get('sortkey')"
        :sort-dir="Request::get('sortdir')" />

    @example Fixed width
    <x-table-filter name="Code" width="100px" />
--}}
@props([
    'name',
    'placeholder' => null,
    'value' => null,
    'sortable' => false,
    'sortKey' => null,
    'currentSort' => null,
    'sortDir' => null,
    'width' => 'auto',
    'clearable' => true,
    'inputClass' => '',
])

@php
    $filterValue = $value ?? Request::get($name);
    $effectiveSortKey = $sortKey ?? $name;
    $isActiveSort = $currentSort === $effectiveSortKey;
    $displayPlaceholder = $placeholder ?? $name;

    $inputClasses = 'form-control form-control-sm filter-input';
    if ($inputClass) {
        $inputClasses .= ' ' . $inputClass;
    }
@endphp

<div class="input-group input-group-sm" @if($width !== 'auto') style="width: {{ $width }}" @endif>
    <input type="text"
           class="{{ $inputClasses }}"
           name="{{ $name }}"
           placeholder="{{ $displayPlaceholder }}"
           value="{{ $filterValue }}"
           {{ $attributes->except(['class', 'name', 'placeholder', 'value']) }}>

    @if($clearable && $filterValue)
        <button type="button"
                class="btn btn-outline-secondary clear-filter"
                data-target="{{ $name }}"
                title="{{ __('Clear filter') }}">
            <span>&times;</span>
        </button>
    @endif

    @if($sortable)
        <button type="button"
                class="btn btn-outline-secondary sort-btn @if($isActiveSort) active @endif"
                data-sortkey="{{ $effectiveSortKey }}"
                title="{{ __('Sort by') }} {{ $displayPlaceholder }}">
            @if($isActiveSort && $sortDir === 'desc')
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M3.5 3.5a.5.5 0 0 0-1 0v8.793l-1.146-1.147a.5.5 0 0 0-.708.708l2 1.999.007.007a.497.497 0 0 0 .7-.006l2-2a.5.5 0 0 0-.707-.708L3.5 12.293zm4 .5a.5.5 0 0 1 0-1h1a.5.5 0 0 1 0 1zm0 3a.5.5 0 0 1 0-1h3a.5.5 0 0 1 0 1zm0 3a.5.5 0 0 1 0-1h5a.5.5 0 0 1 0 1zM7 12.5a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 0-1h-7a.5.5 0 0 0-.5.5"/>
                </svg>
            @elseif($isActiveSort && $sortDir === 'asc')
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M3.5 12.5a.5.5 0 0 1-1 0V3.707L1.354 4.854a.5.5 0 1 1-.708-.708l2-1.999.007-.007a.498.498 0 0 1 .7.006l2 2a.5.5 0 1 1-.707.708L3.5 3.707zm3.5-9a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5M7.5 6a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1zm0 3a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1zm0 3a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1z"/>
                </svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M3.5 2.5a.5.5 0 0 0-1 0v8.793l-1.146-1.147a.5.5 0 0 0-.708.708l2 1.999.007.007a.497.497 0 0 0 .7-.006l2-2a.5.5 0 0 0-.707-.708L3.5 11.293zm3.5 1a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5M7.5 6a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1zm0 3a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1zm0 3a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1z"/>
                </svg>
            @endif
        </button>
    @endif
</div>
