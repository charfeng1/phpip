@extends('layouts.app')

@section('content')
<x-list-with-panel
  :title="__('Categories')"
  create-url="category/create"
  :create-label="__('Create Category')"
  :create-title="__('Category')"
  create-resource="/category/"
  :create-attributes="['data-modal-target' => '#ajaxModal']"
  :panel-title="__('Category information')"
  :panel-message="__('Click on category to view and edit details')"
  list-card-style="max-height: 640px;">
  <x-slot name="list">
    <table class="table table-striped table-hover table-sm">
      <thead>
        <tr id="filter" class="table-primary align-middle">
          <th>
            <div class="input-group input-group-sm" style="width: 80px;">
              <input class="form-control" data-source="/category" name="Code" placeholder="{{ __('Code') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Code">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th>
            <div class="input-group input-group-sm" style="width: 150px;">
              <input class="form-control" data-source="/category" name="Category" placeholder="{{ __('Category') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Category">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th colspan="2">{{ __('Display with') }}</th>
        </tr>
      </thead>
      <tbody id="tableList">
        @foreach ($categories as $category)
        <tr class="reveal-hidden" data-id="{{ $category->code }}">
          <td>
            <a href="/category/{{ $category->code }}" data-panel="ajaxPanel" title="{{ __('Category info') }}">
              {{ $category->code }}
            </a>
          </td>
          <td>{{ $category->category }}</td>
          <td>{{ $category->displayWithInfo->category }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </x-slot>
</x-list-with-panel>
@endsection

@section('script')
@endsection
