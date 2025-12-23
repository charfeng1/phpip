{{--
    Data-driven form table generator component.

    Generates a table-based form layout from a structured array configuration.
    Each row in the form contains one or more fields, with each field having
    a label cell and an input cell.

    @props array $rows - Array of row arrays, where each row contains field definitions
    @props string $tableClass - CSS classes for the table element (default: 'table table-sm')

    Field definition structure:
    [
        'label' => string,           // Label text (optional)
        'title' => string,           // Tooltip for label (optional)
        'labelClass' => string,      // CSS classes for label (optional)
        'labelColspan' => int,       // Colspan for label cell (optional)
        'name' => string,            // Input name attribute (required for non-custom fields)
        'type' => string,            // Input type: 'text', 'textarea', 'custom', etc. (default: 'text')
        'value' => string,           // Input value (optional, default: '')
        'inputClass' => string,      // CSS classes for input (default: 'form-control form-control-sm')
        'inputColspan' => int,       // Colspan for input cell (optional)
        'attributes' => array,       // Additional HTML attributes for input (optional)
        'content' => HtmlString,     // For type='custom' only: pre-sanitized HTML content (optional)
    ]

    SECURITY NOTE: When using type='custom', the 'content' field MUST be pre-sanitized
    by the caller (e.g., wrapped in Illuminate\Support\HtmlString) to prevent XSS vulnerabilities.

    @example
    <x-form-generator :rows="[
        [
            ['label' => 'Name', 'name' => 'name', 'labelClass' => 'fw-bold'],
            ['label' => 'Email', 'name' => 'email', 'type' => 'email'],
        ],
        [
            ['label' => 'Notes', 'name' => 'notes', 'type' => 'textarea', 'inputColspan' => 3],
        ],
    ]" />
--}}
@props([
    'rows' => [],
    'tableClass' => 'table table-sm',
])

@php
    use App\Support\BladeHelpers;
@endphp

<table class="{{ $tableClass }}">
  @foreach ($rows as $row)
    <tr>
      @foreach ($row as $field)
        @php
            $labelText = $field['label'] ?? null;
            $labelTitle = $field['title'] ?? null;
            $labelClass = $field['labelClass'] ?? '';
            $inputClass = $field['inputClass'] ?? 'form-control form-control-sm';
            $inputAttributes = $field['attributes'] ?? [];
            $inputColspan = $field['inputColspan'] ?? null;
            $labelColspan = $field['labelColspan'] ?? null;
            $type = $field['type'] ?? 'text';
            $name = $field['name'] ?? null;
            $value = $field['value'] ?? '';
        @endphp
        <td @if ($labelColspan) colspan="{{ $labelColspan }}" @endif>
          @if ($labelText)
            <label @if ($labelTitle) title="{{ $labelTitle }}" @endif class="{{ $labelClass }}">
              {{ $labelText }}
            </label>
          @endif
        </td>
        <td @if ($inputColspan) colspan="{{ $inputColspan }}" @endif>
          @if ($type === 'custom')
            {{-- Custom content must be pre-sanitized by caller (e.g., wrapped in Illuminate\Support\HtmlString) --}}
            {!! $field['content'] ?? '' !!}
          @elseif ($type === 'textarea' && $name)
            <textarea class="{{ $inputClass }}" name="{{ $name }}" {!! BladeHelpers::formatAttributes($inputAttributes) !!}>{{ $value }}</textarea>
          @elseif ($type !== 'custom' && $type !== 'textarea' && $name)
            <input type="{{ $type }}"
                   class="{{ $inputClass }}"
                   name="{{ $name }}"
                   value="{{ $value }}"
                   {!! BladeHelpers::formatAttributes($inputAttributes) !!}>
          @endif
        </td>
      @endforeach
    </tr>
  @endforeach
</table>
