{{--
    Status badge component with conditional styling based on date/status.

    Displays a colored badge that changes based on urgency or status.
    Commonly used for deadline indicators and status displays.

    @props Carbon\Carbon|string|null $date - Date to evaluate (for urgency type)
    @props string $type - Badge type: 'urgency', 'status', 'custom' (default: 'urgency')
    @props string|null $status - Status value (for status type): 'success', 'warning', 'danger', 'info', 'secondary'
    @props string|null $label - Custom label text (overrides default)
    @props int $warningDays - Days threshold for warning state (default: 14)
    @props string $dateFormat - Date format for display (default: 'L' for locale format)
    @props bool $showDate - Whether to show date in label for urgency type (default: true)

    @example Urgency badge (date-based)
    <x-status-badge :date="$task->due_date" />

    @example Urgency with custom warning threshold
    <x-status-badge :date="$task->due_date" :warning-days="7" />

    @example Status badge
    <x-status-badge type="status" status="success" label="Active" />

    @example Custom badge
    <x-status-badge type="custom" status="info" label="Pending Review" />
--}}
@props([
    'date' => null,
    'type' => 'urgency',
    'status' => null,
    'label' => null,
    'warningDays' => 14,
    'dateFormat' => 'L',
    'showDate' => true,
])

@php
    use Carbon\Carbon;

    $badgeClass = 'badge ';
    $displayLabel = $label;

    if ($type === 'urgency' && $date) {
        $dateObj = $date instanceof Carbon ? $date : Carbon::parse($date);
        $now = now();

        if ($dateObj->lt($now)) {
            // Overdue
            $badgeClass .= 'bg-danger';
            $displayLabel = $label ?? __('Overdue');
        } elseif ($dateObj->lt($now->copy()->addDays($warningDays))) {
            // Warning (within threshold)
            $badgeClass .= 'bg-warning text-dark';
            $displayLabel = $label ?? ($showDate ? $dateObj->isoFormat($dateFormat) : __('Soon'));
        } else {
            // Normal
            $badgeClass .= 'bg-success';
            $displayLabel = $label ?? ($showDate ? $dateObj->isoFormat($dateFormat) : __('On Track'));
        }
    } elseif ($type === 'status' || $type === 'custom') {
        $statusColors = [
            'success' => 'bg-success',
            'warning' => 'bg-warning text-dark',
            'danger' => 'bg-danger',
            'info' => 'bg-info',
            'secondary' => 'bg-secondary',
            'primary' => 'bg-primary',
            'light' => 'bg-light text-dark',
            'dark' => 'bg-dark',
        ];
        $badgeClass .= $statusColors[$status] ?? 'bg-secondary';
    }
@endphp

@if($displayLabel)
    <span class="{{ $badgeClass }}" {{ $attributes }}>{{ $displayLabel }}</span>
@endif
