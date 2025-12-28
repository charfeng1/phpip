@extends('layouts.app')

@section('content')
<div class="space-y-4">
  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
      <h1 class="text-2xl font-bold">{{ __('Fees') }}</h1>
      <a class="text-primary hover:text-primary-focus" href="https://github.com/jjdejong/phpip/wiki/Renewal-Management#costs-and-fees" target="_blank" title="{{ __('Help') }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </a>
    </div>
    <a href="fee/create" class="btn btn-primary btn-sm" data-modal-target="#ajaxModal" title="{{ __('New line') }}" data-resource="/fee/">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
      {{ __('Add a new line') }}
    </a>
  </div>

  {{-- Main Card --}}
  <div class="card bg-base-100 shadow-sm border border-base-300">
    {{-- Header Row --}}
    <div class="bg-primary text-primary-content rounded-t-lg">
      <div class="grid grid-cols-12 text-center text-sm font-medium">
        <div class="col-span-3"></div>
        <div class="col-span-4 grid grid-cols-2">
          <div class="py-2">{{ __('Standard') }}</div>
          <div class="py-2 bg-secondary text-secondary-content">{{ __('Reduced') }}</div>
        </div>
        <div class="col-span-4 grid grid-cols-2">
          <div class="py-2 bg-info text-info-content">{{ __('Grace Standard') }}</div>
          <div class="py-2 bg-secondary text-secondary-content">{{ __('Grace Reduced') }}</div>
        </div>
        <div class="col-span-1"></div>
      </div>

      {{-- Filter Row --}}
      <div id="filter" class="grid grid-cols-12 text-center text-sm px-2 pb-2">
        <div class="col-span-3 grid grid-cols-4 gap-1">
          <input class="input input-bordered input-sm bg-base-100 text-base-content" data-source="/country" name="Country" placeholder="{{ __('Country') }}">
          <input class="input input-bordered input-sm bg-base-100 text-base-content" data-source="/category" name="Category" placeholder="{{ __('Category') }}">
          <input class="input input-bordered input-sm bg-base-100 text-base-content" data-source="/country" name="Origin" placeholder="{{ __('Origin') }}">
          <input class="input input-bordered input-sm bg-base-100 text-base-content" name="Qt" placeholder="{{ __('Yr') }}">
        </div>
        <div class="col-span-4 grid grid-cols-4 items-center">
          <div class="py-1">{{ __('Cost') }}</div>
          <div class="py-1">{{ __('Fee') }}</div>
          <div class="py-1 bg-secondary/50">{{ __('Cost') }}</div>
          <div class="py-1 bg-secondary/50">{{ __('Fee') }}</div>
        </div>
        <div class="col-span-4 grid grid-cols-4 items-center">
          <div class="py-1 bg-info/50">{{ __('Cost') }}</div>
          <div class="py-1 bg-info/50">{{ __('Fee') }}</div>
          <div class="py-1 bg-secondary/50">{{ __('Cost') }}</div>
          <div class="py-1 bg-secondary/50">{{ __('Fee') }}</div>
        </div>
        <div class="col-span-1 flex items-center justify-center">{{ __('Currency') }}</div>
      </div>
    </div>

    {{-- Table Body --}}
    <div class="card-body p-0" id="tableList">
      <div class="overflow-x-auto">
        <table class="table table-zebra table-sm">
          <tbody>
            @foreach ($fees as $fee)
            <tr class="hover:bg-base-200/50" data-resource="/fee/{{ $fee->id }}">
              <td class="w-1/4">
                <div class="grid grid-cols-4 gap-1 text-sm">
                  <span>{{ $fee->for_country }}</span>
                  <span>{{ $fee->for_category }}</span>
                  <span>{{ $fee->for_origin }}</span>
                  <span>{{ $fee->qt }}</span>
                </div>
              </td>
              <td class="w-1/3">
                <div class="grid grid-cols-4 gap-1">
                  <input class="input input-bordered input-sm text-right" name="cost" value="{{ $fee->cost }}">
                  <input class="input input-bordered input-sm text-right" name="fee" value="{{ $fee->fee }}">
                  <input class="input input-bordered input-sm text-right" name="cost_reduced" value="{{ $fee->cost_reduced }}" title="{{ __('Leave empty if not used') }}">
                  <input class="input input-bordered input-sm text-right" name="fee_reduced" value="{{ $fee->fee_reduced }}" title="{{ __('Leave empty if not used') }}">
                </div>
              </td>
              <td class="w-1/3">
                <div class="grid grid-cols-4 gap-1" title="{{ __('Leave empty if not used') }}">
                  <input class="input input-bordered input-sm text-right" name="cost_sup" value="{{ $fee->cost_sup }}">
                  <input class="input input-bordered input-sm text-right" name="fee_sup" value="{{ $fee->fee_sup }}">
                  <input class="input input-bordered input-sm text-right" name="cost_sup_reduced" value="{{ $fee->cost_sup_reduced }}">
                  <input class="input input-bordered input-sm text-right" name="fee_sup_reduced" value="{{ $fee->fee_sup_reduced }}">
                </div>
              </td>
              <td class="w-16">
                <input class="input input-bordered input-sm text-center w-full" name="currency" value="{{ $fee->currency }}">
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="px-4 py-3 border-t border-base-300">
        {{ $fees->links() }}
      </div>
    </div>
  </div>
</div>
@endsection

@section('script')
@endsection
