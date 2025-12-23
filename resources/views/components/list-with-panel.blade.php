@props([
    'title',
    'createUrl' => null,
    'createLabel' => null,
    'createTitle' => null,
    'createResource' => null,
    'createAttributes' => [],
    'panelTitle',
    'panelMessage' => null,
    'panelId' => 'ajaxPanel',
    'listColumnClass' => 'col',
    'panelColumnClass' => 'col-5',
    'listCardClass' => 'card border-primary p-1',
    'listCardStyle' => null,
    'panelCardClass' => 'card border-info',
    'panelHeaderClass' => 'card-header bg-info text-light',
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

    $createAttributeString = $formatAttributes($createAttributes);
@endphp

<legend class="alert alert-dark d-flex justify-content-between py-2 mb-1">
  <span>
    @if (isset($titleSlot))
      {{ $titleSlot }}
    @else
      {{ $title }}
    @endif
  </span>
  @if ($createUrl && $createLabel)
    <a href="{{ $createUrl }}"
       class="btn btn-primary"
       @if ($createTitle) title="{{ $createTitle }}" @endif
       @if ($createResource) data-resource="{{ $createResource }}" @endif
       {!! $createAttributeString !== '' ? ' '.$createAttributeString : '' !!}>
      {{ $createLabel }}
    </a>
  @endif
</legend>
<div class="row">
  <div class="{{ $listColumnClass }}">
    <div class="{{ $listCardClass }}" @if ($listCardStyle) style="{{ $listCardStyle }}" @endif>
      {{ $list }}
    </div>
  </div>
  <div class="{{ $panelColumnClass }}">
    <div class="{{ $panelCardClass }}">
      <div class="{{ $panelHeaderClass }}">
        {{ $panelTitle }}
      </div>
      <div class="card-body p-2" id="{{ $panelId }}">
        {!! $panel ?? '' !!}
        @if (! isset($panel))
          <div class="alert alert-info" role="alert">
            {{ $panelMessage }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
