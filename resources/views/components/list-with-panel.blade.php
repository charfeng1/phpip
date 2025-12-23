{{--
    List with side panel component for index views.

    Provides a standardized two-column layout with a list/table on the left
    and a detail panel on the right. Commonly used in index views where
    clicking a list item displays details in the adjacent panel.

    @props string $title - Main page title (or use titleSlot for HTML content)
    @props string|null $createUrl - URL for the create button
    @props string|null $createLabel - Label text for the create button
    @props string|null $createTitle - Tooltip for the create button
    @props string|null $createResource - Data attribute for AJAX resource
    @props array $createAttributes - Additional HTML attributes for create button
    @props string $panelTitle - Title text for the side panel
    @props string|null $panelMessage - Default message shown when panel is empty
    @props string $panelId - HTML ID for the panel container (default: 'ajaxPanel')
    @props string $listColumnClass - CSS class for list column (default: 'col')
    @props string $panelColumnClass - CSS class for panel column (default: 'col-5')
    @props string $listCardClass - CSS class for list card wrapper
    @props string|null $listCardStyle - Inline CSS for list card
    @props string $panelCardClass - CSS class for panel card wrapper
    @props string $panelHeaderClass - CSS class for panel header

    @slot titleSlot - Optional named slot for HTML title content
    @slot list - Main content slot for the list/table
    @slot panel - Optional content slot for the side panel

    @example
    <x-list-with-panel
        title="Users"
        create-url="/users/create"
        create-label="New User"
        panel-title="User Details">
        <x-slot:list>
            <table>...</table>
        </x-slot>
    </x-list-with-panel>
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
    'listColumnClass' => 'col',
    'panelColumnClass' => 'col-5',
    'listCardClass' => 'card border-primary p-1',
    'listCardStyle' => null,
    'panelCardClass' => 'card border-info',
    'panelHeaderClass' => 'card-header bg-info text-light',
])

@php
    use App\Support\BladeHelpers;

    $createAttributeString = BladeHelpers::formatAttributes($createAttributes);
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
