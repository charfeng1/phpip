@extends('layouts.app')

@section('body-class', 'renewals-page')

@section('content')
<div class="card bg-base-100 shadow-sm border border-base-300">
  {{-- Header --}}
  <div class="card-title bg-base-200/50 px-4 py-3 border-b border-base-300 flex flex-wrap items-center justify-between gap-2">
    <div class="flex items-center gap-2">
      <h2 class="text-lg font-semibold">{{ __('Manage renewals') }}</h2>
      <a href="https://github.com/jjdejong/phpip/wiki/Renewal-Management" class="text-primary hover:text-primary-focus" target="_blank" title="{{ __('Help') }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </a>
    </div>
    <div class="flex items-center gap-2">
      <a href="/logs" class="btn btn-info btn-sm">{{ __('View logs') }}</a>
      <button id="clearFilters" type="button" class="btn btn-ghost btn-sm">&larrpl; {{ __('Clear filters') }}</button>
    </div>
  </div>

  {{-- Tabs --}}
  <div role="tablist" class="tabs tabs-boxed bg-base-200 m-2 rounded-lg" id="tabsGroup">
    <a role="tab" class="tab {{ !$step && !$invoice_step ? 'tab-active' : '' }}" href="#p1" data-step="0">{{ __('First call') }}</a>
    <a role="tab" class="tab {{ $step == 2 ? 'tab-active' : '' }}" href="#p2" data-step="2">{{ __('Reminder') }}</a>
    <a role="tab" class="tab {{ $step == 4 ? 'tab-active' : '' }}" href="#p3" data-step="4">{{ __('Payment') }}</a>
    @if (config('renewal.general.receipt_tabs'))
    <a role="tab" class="tab {{ $step == 6 ? 'tab-active' : '' }}" href="#p4" data-step="6">{{ __('Receipts') }}</a>
    <a role="tab" class="tab {{ $step == 8 ? 'tab-active' : '' }}" href="#p5" data-step="8">{{ __('Receipts received') }}</a>
    @endif
    <a role="tab" class="tab {{ $step == 12 ? 'tab-active' : '' }}" href="#p6" data-step="12">{{ __('Abandoned') }}</a>
    <a role="tab" class="tab {{ $step == 14 ? 'tab-active' : '' }}" href="#p9" data-step="14">{{ __('Lapsed') }}</a>
    <a role="tab" class="tab {{ $step == 10 ? 'tab-active' : '' }}" href="#p10" data-step="10">{{ __('Closed') }}</a>
    <a role="tab" class="tab {{ $invoice_step == 1 ? 'tab-active' : '' }}" href="#p7" data-invoice_step="1">{{ __('Invoicing') }}</a>
    <a role="tab" class="tab {{ $invoice_step == 2 ? 'tab-active' : '' }}" href="#p8" data-invoice_step="2">{{ __('Invoiced') }}</a>
    <a role="tab" class="tab {{ $invoice_step == 3 ? 'tab-active' : '' }}" href="#p11" data-invoice_step="3">{{ __('Invoices paid') }}</a>
  </div>

  {{-- Tab Content Panels with Action Buttons --}}
  <div class="px-4 py-2">
    {{-- First Call --}}
    <div class="tab-pane {{ !$step && !$invoice_step ? '' : 'hidden' }}" id="p1">
      <div class="flex justify-end gap-2">
        <button class="btn btn-info btn-sm" type="button" id="callRenewals">{{ __('Send call email') }}</button>
        <button class="btn btn-info btn-sm" type="button" id="renewalsSent">{{ __('Call sent manually') }}</button>
      </div>
    </div>

    {{-- Reminder --}}
    <div class="tab-pane {{ $step == 2 ? '' : 'hidden' }}" id="p2">
      <div class="flex justify-end gap-2 flex-wrap">
        <button class="btn btn-outline btn-info btn-sm" type="button" id="reminderRenewals">{{ __('Send reminder email') }}</button>
        <button class="btn btn-outline btn-info btn-sm" type="button" id="lastReminderRenewals" title="{{ __('Send reminder and enter grace period') }}">{{ __('Send last reminder email') }}</button>
        <button class="btn btn-info btn-sm" type="button" id="instructedRenewals" title="{{ __('Instructions received to pay') }}">{{ __('Payment order received') }}</button>
        <button class="btn btn-info btn-sm" type="button" id="abandonRenewals" title="{{ __('Abandon instructions received') }}">{{ __('Abandon') }}</button>
        <button class="btn btn-info btn-sm" type="button" id="lapsedRenewals" title="{{ __('Office lapse communication received') }}">{{ __('Lapsed') }}</button>
      </div>
    </div>

    {{-- Payment --}}
    <div class="tab-pane {{ $step == 4 ? '' : 'hidden' }}" id="p3">
      <div class="flex justify-end gap-2">
        <button class="btn btn-outline btn-info btn-sm" type="button" id="xmlRenewals" title="{{ __('Generate xml files for EP or FR') }}">{{ __('Download XML order to pay') }}</button>
        <button class="btn btn-info btn-sm" type="button" id='doneRenewals'>{{ __('Paid') }}</button>
      </div>
    </div>

    @if (config('renewal.general.receipt_tabs'))
    {{-- Receipts --}}
    <div class="tab-pane {{ $step == 6 ? '' : 'hidden' }}" id="p4">
      <div class="flex justify-end">
        <button class="btn btn-info btn-sm" type="button" id="receiptRenewals">{{ __('Official receipts received') }}</button>
      </div>
    </div>

    {{-- Receipts received --}}
    <div class="tab-pane {{ $step == 8 ? '' : 'hidden' }}" id="p5">
      <div class="flex justify-end">
        <button class="btn btn-info btn-sm" type="button" id="sendReceiptsRenewals">{{ __('Receipts sent') }}</button>
      </div>
    </div>
    @endif

    {{-- Abandoned --}}
    <div class="tab-pane {{ $step == 12 ? '' : 'hidden' }}" id="p6">
      <div class="flex justify-end">
        <button class="btn btn-info btn-sm" type="button" id="lapsingRenewals">{{ __('Lapse') }}</button>
      </div>
    </div>

    {{-- Lapsed --}}
    <div class="tab-pane {{ $step == 14 ? '' : 'hidden' }}" id="p9">
      <div class="flex justify-end">
        <button class="btn btn-info btn-sm" type="button" id="sendLapsedRenewals">{{ __('Lapse communication sent') }}</button>
      </div>
    </div>

    {{-- Closed --}}
    <div class="tab-pane {{ $step == 10 ? '' : 'hidden' }}" id="p10">
      <div class="flex justify-end">
        <button class="btn btn-ghost btn-sm" type="button" disabled>{{ __('Closed renewals') }}</button>
      </div>
    </div>

    {{-- Invoicing --}}
    <div class="tab-pane {{ $invoice_step == 1 ? '' : 'hidden' }}" id="p7">
      <div class="flex justify-end gap-2">
        @if (config('renewal.invoice.backend') == 'dolibarr')
        <button class="btn btn-info btn-sm" type="button" id="invoiceRenewals">{{ __('Generate invoice') }}</button>
        @endif
        <button class="btn btn-outline btn-info btn-sm" type="button" id="renewalsExport">{{ __('Export all') }}</button>
        <button class="btn btn-info btn-sm" type="button" id="renewalsInvoiced">{{ __('Invoiced') }}</button>
      </div>
    </div>

    {{-- Invoiced --}}
    <div class="tab-pane {{ $invoice_step == 2 ? '' : 'hidden' }}" id="p8">
      <div class="flex justify-end">
        <button class="btn btn-info btn-sm" type="button" id="invoicesPaid">{{ __('Paid') }}</button>
      </div>
    </div>

    {{-- Invoices paid --}}
    <div class="tab-pane {{ $invoice_step == 3 ? '' : 'hidden' }}" id="p11">
      <div class="flex justify-end">
        <button class="btn btn-ghost btn-sm" type="button" disabled>{{ __('Paid invoices') }}</button>
      </div>
    </div>
  </div>

  {{-- Table --}}
  <div class="card-body p-0">
    <div class="overflow-x-auto">
      <table class="table table-sm">
        <thead>
          <tr class="bg-primary/10" id="filterFields">
            <th class="w-1/6">
              <input class="input input-bordered input-sm w-full" name="Name" value="{{ Request::get('Name') }}" placeholder="{{ __('Client') }}">
            </th>
            <th class="w-1/4">
              <input class="input input-bordered input-sm w-full" name="Title" value="{{ Request::get('Title') }}" placeholder="{{ __('Title') }}">
            </th>
            <th class="w-16">
              <input class="input input-bordered input-sm w-full" name="Case" value="{{ Request::get('Case') }}" placeholder="{{ __('Matter') }}">
            </th>
            <th class="w-1/4">
              <div class="grid grid-cols-6 gap-1 items-center text-center">
                <input class="input input-bordered input-sm" name="Country" value="{{ Request::get('Country') }}" placeholder="{{ __('Ctry') }}">
                <input class="input input-bordered input-sm" name="Qt" value="{{ Request::get('Qt') }}" placeholder="{{ __('Qt') }}">
                <label class="flex items-center gap-1 cursor-pointer">
                  <input id="grace" name="grace_period" type="checkbox" class="checkbox checkbox-primary checkbox-xs">
                  <span class="text-xs font-medium">{{ __('Grace') }}</span>
                </label>
                <span class="text-xs font-medium">{{ __('Cost') }}</span>
                <span class="text-xs font-medium">{{ __('Fee') }}</span>
                <span></span>
              </div>
            </th>
            <th class="w-1/6">
              <div class="join w-full">
                <input type="date" class="input input-bordered input-sm join-item w-1/2" name="Fromdate" id="Fromdate" title="{{ __('From selected date') }}" value="{{ Request::get('Fromdate') }}">
                <input type="date" class="input input-bordered input-sm join-item w-1/2" name="Untildate" id="Untildate" title="{{ __('Until selected date') }}" value="{{ Request::get('Untildate') }}">
              </div>
            </th>
            <th class="w-12 text-center">
              <input id="selectAll" type="checkbox" class="checkbox checkbox-primary checkbox-sm" title="{{ __('Select all') }}">
            </th>
          </tr>
        </thead>
        <tbody id="renewalList">
          @if (count($renewals) == 0)
          <tr>
            <td colspan="6" class="text-center text-error py-8">
              {{ __('The list is empty') }}
            </td>
          </tr>
          @else
          @foreach ($renewals as $task)
          <tr class="hover:bg-base-200/50" data-resource="/task/{{ $task->id }}">
            <td>{{ $task->client_name }}</td>
            <td class="truncate max-w-xs">{{ $task->short_title }}</td>
            <td>
              <a href="/matter/{{ $task->matter_id }}" class="link link-primary">{{ $task->uid }}</a>
            </td>
            <td>
              <div class="grid grid-cols-6 gap-1 items-center text-center text-sm">
                <span>{{ $task->country }}</span>
                <span>{{ $task->detail }}</span>
                <span>
                  @if ($task->grace_period)
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-auto text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  @endif
                </span>
                <span class="text-right">{{ $task->cost }}</span>
                <span class="text-right">{{ $task->fee }}</span>
                <span></span>
              </div>
            </td>
            <td class="text-center">
              <div class="flex items-center justify-center gap-1">
                <span>{{ \Carbon\Carbon::parse($task->due_date)->isoFormat('L') }}</span>
                @if ($task->done)
                <span class="text-success" title="Done">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </span>
                @elseif ($task->due_date < now())
                <span class="text-error" title="Overdue">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                </span>
                @elseif ($task->due_date < now()->addWeeks(1))
                <span class="text-warning" title="Urgent">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                </span>
                @endif
              </div>
            </td>
            <td class="text-center">
              <input id="{{ $task->id }}" class="checkbox checkbox-primary checkbox-sm clear-ren-task" type="checkbox">
            </td>
          </tr>
          @endforeach
          @endif
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    @if (count($renewals) > 0)
    <div class="px-4 py-3 border-t border-base-300">
      {{ $renewals->links() }}
    </div>
    @endif
  </div>
</div>
@endsection
