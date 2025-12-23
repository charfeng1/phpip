@extends('layouts.app')

@section('content')
<x-list-with-panel
  :title="__('Event Names')"
  create-url="eventname/create"
  :create-label="__('Create Event Name')"
  :create-title="__('Event name')"
  create-resource="/eventname/"
  :create-attributes="['data-bs-toggle' => 'modal', 'data-bs-target' => '#ajaxModal']"
  :panel-title="__('Event name information')"
  :panel-message="__('Click on event name to view and edit details')"
  list-card-style="max-height: 640px; overflow: auto;">
  <x-slot name="list">
    <table class="table table-striped table-hover table-sm">
      <thead>
        <tr id="filter" class="table-primary align-middle">
          <th>
            <div class="input-group input-group-sm" style="width: 80px;">
              <input class="form-control" data-source="/eventname" name="Code" placeholder="{{ __('Code') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Code">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th>
            <div class="input-group input-group-sm" style="width: 150px;">
              <input class="form-control" data-source="/eventname" name="Name" placeholder="{{ __('Name') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Name">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th class="text-center" colspan="2">{{ __('Notes') }}</th>
        </tr>
      </thead>
      <tbody id="tableList">
        @foreach ($enameslist as $event)
        <tr class="reveal-hidden" data-id="{{ $event->code }}">
          <td>
            <a href="/eventname/{{ $event->code }}" data-panel="ajaxPanel" title="{{ __('Event name info') }}">
              {{ $event->code }}
            </a>
          </td>
          <td>{{ $event->name }}</td>
          <td>{{ $event->notes }}</td>
        </tr>
        @endforeach
        <tr>
          <td colspan="5">
            {{ $enameslist->links() }}
          </td>
        </tr>
      </tbody>
    </table>
  </x-slot>
</x-list-with-panel>
@endsection

@section('script')
@endsection
