{{--
    Modal launch button component.

    Creates a button/link that triggers a Bootstrap modal with AJAX content loading.
    Integrates with the phpIP modal system (ajaxModal).

    @props string $href - URL to load in the modal (required)
    @props string $label - Button label text (required)
    @props string $modalTarget - Modal ID to trigger (default: '#ajaxModal')
    @props string $modalSize - Modal size class: 'modal-sm', 'modal-lg', 'modal-xl' (default: null)
    @props string|null $icon - Icon class or SVG content (default: null)
    @props string|null $title - Title attribute for tooltip (default: null)
    @props string|null $resource - Data-resource attribute for form handling (default: null)
    @props string $variant - Button variant: 'primary', 'secondary', 'info', etc. (default: 'primary')
    @props string $size - Button size: 'sm', 'lg', or null for default (default: null)
    @props bool $outline - Use outline style (default: false)
    @props bool $disabled - Disable the button (default: false)

    @example Basic modal button
    <x-modal-button href="/matter/create" label="New Matter" />

    @example With icon and size
    <x-modal-button
        href="/actor/create"
        label="New Actor"
        icon="plus"
        variant="success"
        size="sm" />

    @example With modal sizing
    <x-modal-button
        href="/fee/create"
        label="Add Fee"
        modal-size="modal-lg" />

    @example With resource tracking
    <x-modal-button
        href="/event/create?matter_id=1"
        label="Add Event"
        resource="event" />
--}}
@props([
    'href',
    'label',
    'modalTarget' => '#ajaxModal',
    'modalSize' => null,
    'icon' => null,
    'title' => null,
    'resource' => null,
    'variant' => 'primary',
    'size' => null,
    'outline' => false,
    'disabled' => false,
])

@php
    $btnClass = 'btn ';
    $btnClass .= $outline ? "btn-outline-{$variant}" : "btn-{$variant}";
    if ($size) {
        $btnClass .= " btn-{$size}";
    }
    if ($disabled) {
        $btnClass .= ' disabled';
    }

    // Common Bootstrap icons as inline SVG
    $icons = [
        'plus' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/></svg>',
        'edit' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/></svg>',
        'trash' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>',
        'eye' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>',
        'download' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>',
    ];

    $iconHtml = '';
    if ($icon) {
        $iconHtml = $icons[$icon] ?? $icon;
    }
@endphp

<a href="{{ $href }}"
   class="{{ $btnClass }}"
   data-bs-toggle="modal"
   data-bs-target="{{ $modalTarget }}"
   @if($modalSize) data-size="{{ $modalSize }}" @endif
   @if($title) title="{{ $title }}" @endif
   @if($resource) data-resource="{{ $resource }}" @endif
   @if($disabled) aria-disabled="true" @endif
   {{ $attributes->except(['class', 'href']) }}>
    @if($iconHtml)
        {!! $iconHtml !!}
    @endif
    {{ $label }}
</a>
