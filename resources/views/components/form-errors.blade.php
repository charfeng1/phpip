{{--
    Form validation errors display component.

    Displays a summary of validation errors in an alert box.
    Can show all errors or filter to specific fields.

    @props array|null $fields - Specific field names to show errors for (default: null = all)
    @props bool $dismissible - Allow dismissing the alert (default: true)
    @props string $title - Title text for errors (default: 'Please fix the following errors:')

    @example Show all errors
    <x-form-errors />

    @example Show specific field errors only
    <x-form-errors :fields="['name', 'email', 'password']" />

    @example Non-dismissible with custom title
    <x-form-errors
        :dismissible="false"
        title="Validation Failed" />
--}}
@props([
    'fields' => null,
    'dismissible' => true,
    'title' => null,
])

@php
    $displayTitle = $title ?? __('Please fix the following errors:');

    // Ensure $errors is available (it's auto-injected by ShareErrorsFromSession middleware)
    $errorBag = [];
    if (isset($errors) && $errors->any()) {
        if ($fields) {
            $errorBag = $errors->only($fields);
        } else {
            $errorBag = $errors->all();
        }
    }
@endphp

@if(count($errorBag) > 0)
    <div class="alert alert-danger @if($dismissible) alert-dismissible fade show @endif" role="alert">
        <strong>{{ $displayTitle }}</strong>
        <ul class="mb-0 mt-2">
            @if($fields)
                @foreach($fields as $field)
                    @if(isset($errors) && $errors->has($field))
                        <li>{{ $errors->first($field) }}</li>
                    @endif
                @endforeach
            @else
                @foreach($errorBag as $error)
                    <li>{{ $error }}</li>
                @endforeach
            @endif
        </ul>
        @if($dismissible)
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
        @endif
    </div>
@endif
