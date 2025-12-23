@extends('layouts.app')

@section('content')
<x-list-with-panel
  :title="__('Email Template Classes')"
  create-url="document/create"
  :create-label="__('Create Email Template Class')"
  :create-title="__('Document class')"
  create-resource="/document/create/"
  :create-attributes="['data-bs-toggle' => 'modal', 'data-bs-target' => '#ajaxModal', 'data-source' => '/document']"
  :panel-title="__('Class information')"
  :panel-message="__('Click on class to view and edit details')"
  panel-column-class="col-4"
  list-card-class="card border-primar p-1">
  <x-slot name="list">
    <table class="table table-striped table-hover table-sm">
      <thead>
        <tr id="filter" class="table-primary align-middle">
          <th><input class="form-control" data-source="/document" name="Name" placeholder="{{ __('Name') }}"></th>
          <th><input class="form-control" data-source="/document" name="Notes" placeholder="{{ __('Notes') }}"></th>
        </tr>
      </thead>
      <tbody id="tableList">
        @foreach ($template_classes as $class)
        <tr data-id="{{ $class->id }}" class="reveal-hidden">
          <td>
            <a href="/document/{{ $class->id }}" data-panel="ajaxPanel" title="{{ __('Class data') }}">
              {{ $class->name }}
            </a>
          </td>
          <td>{{ $class->notes }}</td>
        </tr>
        @endforeach
        <tr>
          <td colspan="5">
            {{ $template_classes->links() }}
          </td>
        </tr>
      </tbody>
    </table>
  </x-slot>
</x-list-with-panel>
@endsection

@section('script')
@endsection
