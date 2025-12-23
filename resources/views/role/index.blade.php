@extends('layouts.app')

@section('content')
<x-list-with-panel
  :title="__('Actor Roles')"
  create-url="role/create"
  :create-label="__('Create Role')"
  :create-title="__('Role')"
  create-resource="/role/"
  :create-attributes="['data-bs-toggle' => 'modal', 'data-bs-target' => '#ajaxModal']"
  :panel-title="__('Role information')"
  :panel-message="__('Click on role to view and edit details')"
  list-card-style="max-height: 640px; overflow: auto;">
  <x-slot name="list">
    <table class="table table-striped table-hover table-sm">
      <thead>
        <tr id="filter" class="table-primary align-middle">
          <th>
            <div class="input-group input-group-sm" style="width: 80px;">
              <input class="form-control" data-source="/role" name="Code" placeholder="{{ __('Code') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Code">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th>
            <div class="input-group input-group-sm" style="width: 150px;">
              <input class="form-control" data-source="/role" name="Name" placeholder="{{ __('Name') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Name">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th class="text-center" colspan="2">{{ __('Notes') }}</th>
        </tr>
      </thead>
      <tbody id="tableList">
        @foreach ($roles as $role)
        <tr class="reveal-hidden" data-id="{{ $role->code }}">
          <td>
            <a href="/role/{{ $role->code }}" data-panel="ajaxPanel" title="{{ __('Role info') }}">
              {{ $role->code }}
            </a>
          </td>
          <td>{{ $role->name }}</td>
          <td>{{ $role->notes }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </x-slot>
</x-list-with-panel>
@endsection

@section('script')
@endsection
