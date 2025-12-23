@extends('layouts.app')

@section('content')
<x-list-with-panel
  :title="__('Matter Types')"
  create-url="type/create"
  :create-label="__('Create Matter Type')"
  :create-title="__('Type')"
  create-resource="/type/"
  :create-attributes="['data-bs-toggle' => 'modal', 'data-bs-target' => '#ajaxModal']"
  :panel-title="__('Type Information')"
  :panel-message="__('Click on type to view and edit details')"
  list-card-style="max-height: 640px; overflow: auto;">
  <x-slot name="list">
    <table class="table table-striped table-hover table-sm">
      <thead>
        <tr id="filter" class="table-primary align-middle">
          <th>
            <div class="input-group input-group-sm" style="width: 80px;">
              <input class="form-control" data-source="/type" name="Code" placeholder="{{ __('Code') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Code">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th>
            <div class="input-group input-group-sm" style="width: 150px;">
              <input class="form-control" data-source="/type" name="Type" placeholder="{{ __('Type') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Type">
                <span>&times;</span>
              </button>
            </div>
          </th>
        </tr>
      </thead>
      <tbody id="tableList">
        @foreach ($matter_types as $type)
        <tr class="reveal-hidden" data-id="{{ $type->code }}">
          <td>
            <a href="/type/{{ $type->code }}" data-panel="ajaxPanel" title="{{ __('Type info') }}">
              {{ $type->code }}
            </a>
          </td>
          <td>{{ $type->type }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </x-slot>
</x-list-with-panel>
@endsection

@section('script')
@endsection
