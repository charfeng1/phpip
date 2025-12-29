{{--
    List with side panel component for index views.

    Provides a standardized two-column layout with a list/table on the left
    and a detail panel on the right. Commonly used in index views where
    clicking a list item displays details in the adjacent panel.

    Updated for DaisyUI + Tailwind CSS styling.
--}}
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
    'listColumnClass' => 'flex-1',
    'panelColumnClass' => 'w-96',
])

@php
    use App\Support\BladeHelpers;
    $createAttributeString = BladeHelpers::formatAttributes($createAttributes);
@endphp

{{-- Page Header --}}
<div class="flex items-center justify-between bg-base-200 px-4 py-3 rounded-lg mb-4 shadow-sm">
  <h1 class="text-xl font-semibold text-base-content">
    @if (isset($titleSlot))
      {{ $titleSlot }}
    @else
      {{ $title }}
    @endif
  </h1>
  @if ($createUrl && $createLabel)
    <a href="{{ $createUrl }}"
       class="btn btn-primary btn-sm"
       @if ($createTitle) title="{{ $createTitle }}" @endif
       @if ($createResource) data-resource="{{ $createResource }}" @endif
       {!! $createAttributeString !== '' ? ' '.$createAttributeString : '' !!}>
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
      {{ $createLabel }}
    </a>
  @endif
</div>

{{-- Main Content --}}
<div class="flex gap-4">
  {{-- List Column --}}
  <div class="{{ $listColumnClass }}">
    <div class="card bg-base-100 shadow-sm border border-base-300">
      <div class="card-body p-0">
        {{ $list }}
      </div>
    </div>
  </div>

  {{-- Panel Column --}}
  <div class="{{ $panelColumnClass }}">
    <div class="card bg-base-100 shadow-sm border border-base-300">
      <div class="card-title bg-info/10 text-info px-4 py-3 text-sm font-medium border-b border-base-300">
        {{ $panelTitle }}
      </div>
      <div class="card-body p-4" id="{{ $panelId }}">
        {!! $panel ?? '' !!}
        @if (! isset($panel))
          <div class="alert alert-info">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-sm">{{ $panelMessage }}</span>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
