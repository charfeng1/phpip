@extends('layouts.app')

@section('content')
<x-list-with-panel
  :title="__('Default Actors')"
  create-url="default_actor/create"
  :create-label="__('Create Default Actor')"
  :create-title="__('Default actors')"
  create-resource="/default_actor/"
  :create-attributes="['data-bs-toggle' => 'modal', 'data-bs-target' => '#ajaxModal']"
  :panel-title="__('Default actor information')"
  :panel-message="__('Click on line to view and edit details')"
  list-card-style="max-height: 640px; overflow: auto;">
  <x-slot name="list">
    <table class="table table-striped table-hover table-sm">
      <thead>
        <tr id="filter" class="table-primary align-middle">
          <th><input class="form-control" data-source="/default_actor" name="Actor" placeholder="{{ __('Actor') }}"></th>
          <th><input class="form-control" data-source="/default_actor" name="Role" placeholder="{{ __('Role') }}"></th>
          <th><input class="form-control" data-source="/default_actor" name="Country" placeholder="{{ __('Country') }}"></th>
          <th><input class="form-control" data-source="/default_actor" name="Category" placeholder="{{ __('Category') }}"></th>
          <th><input class="form-control" data-source="/default_actor" name="Client" placeholder="{{ __('Client') }}"></th>
        </tr>
      </thead>
      <tbody id="tableList">
        @foreach ($default_actors as $default_actor)
        <tr class="reveal-hidden" data-id="{{ $default_actor->id }}">
          <td>
            <a href="/default_actor/{{ $default_actor->id }}" data-panel="ajaxPanel" title="{{ __('Actor') }}">
              {{ $default_actor->actor->name }}
            </a>
          </td>
          <td>{{ empty($default_actor->roleInfo) ? '' : $default_actor->roleInfo->name }}</td>
          <td>{{ empty($default_actor->country) ? '' : $default_actor->country->name }}</td>
          <td>{{ empty($default_actor->category) ? '' : $default_actor->category->category }}</td>
          <td>{{ empty($default_actor->client) ? '' : $default_actor->client->name }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </x-slot>
</x-list-with-panel>
@endsection

@section('script')
@endsection
