@props([
    'rows' => [],
    'tableClass' => 'table table-sm',
])

@php
    $formatAttributes = function (array $attributes) {
        return collect($attributes)
            ->map(function ($value, $key) {
                if (is_bool($value)) {
                    return $value ? $key : '';
                }

                return $key.'="'.e($value).'"';
            })
            ->filter()
            ->implode(' ');
    };
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
            {!! $field['content'] ?? '' !!}
          @elseif ($type === 'textarea')
            <textarea class="{{ $inputClass }}" name="{{ $name }}" {!! $formatAttributes($inputAttributes) !!}>{{ $value }}</textarea>
          @else
            <input type="{{ $type }}"
                   class="{{ $inputClass }}"
                   name="{{ $name }}"
                   value="{{ $value }}"
                   {!! $formatAttributes($inputAttributes) !!}>
          @endif
        </td>
      @endforeach
    </tr>
  @endforeach
</table>
