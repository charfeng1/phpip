@extends('layouts.app')

@section('content')
<x-list-with-panel
  :title="__('Countries')"
  create-url="countries/create"
  :create-label="__('Create Country')"
  :create-title="__('Country')"
  create-resource="/countries/"
  :create-attributes="['data-bs-toggle' => 'modal', 'data-bs-target' => '#ajaxModal']"
  :panel-title="__('Country information')"
  :panel-message="__('Click on country to view and edit details')"
  list-card-style="max-height: 640px;">
  <x-slot name="list">
    <table class="table table-striped table-hover table-sm">
      <thead>
        <tr id="filter" class="table-primary align-middle">
          <th style="width: 80px;">
            <div class="input-group input-group-sm" style="width: 80px;">
              <input class="form-control" data-source="/countries" name="iso" placeholder="{{ __('ISO') }}" style="width: 50px;">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="iso">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th style="width: 200px;">
            <div class="input-group input-group-sm" style="width: 200px;">
              <input class="form-control" data-source="/countries" name="name" placeholder="{{ __('Name') }}" style="width: 170px;">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="name">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th class="text-center" style="width: 60px;">{{ __('EP') }}</th>
          <th class="text-center" style="width: 60px;">{{ __('WO') }}</th>
        </tr>
      </thead>
      <tbody id="tableList">
        @foreach ($countries as $country)
        <tr class="reveal-hidden" data-id="{{ $country->iso }}">
          <td>
            <a href="{{ url('countries/' . $country->iso) }}" data-panel="ajaxPanel" title="{{ __('Country info') }}">
              {{ $country->iso }}
            </a>
          </td>
          <td>{{ $country->getTranslation('name', app()->getLocale()) }}
          </td>
          <td class="text-center">
            @if($country->ep == 1)
              <svg class="text-success" width="16" height="16" fill="currentColor" title="{{ __('EP Member') }}">
                <use xlink:href="#check-circle-fill"/>
              </svg>
            @endif
          </td>
          <td class="text-center">
            @if($country->wo == 1)
              <svg class="text-success" width="16" height="16" fill="currentColor" title="{{ __('PCT Member') }}">
                <use xlink:href="#check-circle-fill"/>
              </svg>
            @endif
          </td>
        </tr>
        @endforeach
        <tr>
          <td colspan="4">
            {{ $countries->links() }}
          </td>
        </tr>
      </tbody>
    </table>
  </x-slot>
</x-list-with-panel>
@endsection

@section('script')
@endsection
