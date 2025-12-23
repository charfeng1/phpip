@extends('layouts.app')

@section('content')
<x-list-with-panel
  title="{{ __('Rules') }}"
  create-url="rule/create"
  :create-label="__('Create Rule')"
  :create-title="__('Rule data')"
  create-resource="/rule/create/"
  :create-attributes="['data-bs-toggle' => 'modal', 'data-bs-target' => '#ajaxModal', 'data-source' => '/rule']"
  :panel-title="__('Rule information')"
  :panel-message="__('Click on rule to view and edit details')"
  panel-column-class="col-4">
  <x-slot name="titleSlot">
    {{ __('Rules') }} <a class="text-primary" href="https://github.com/jjdejong/phpip/wiki/Tables#task_rules" target="_blank"><svg width="16" height="16" fill="currentColor"><use xlink:href="#question-circle-fill"/></svg></a>
  </x-slot>
  <x-slot name="list">
    <table class="table table-striped table-hover table-sm">
      <thead>
        <tr id="filter" class="table-primary align-middle">
          <th style="width: 20%">
            <div class="input-group input-group-sm">
              <input class="form-control" data-source="/rule" name="Task" placeholder="{{ __('Task') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Task">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th style="width: 15%">
            <div class="input-group input-group-sm">
              <input class="form-control" data-source="/rule" name="Detail" placeholder="{{ __('Detail') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Detail">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th style="width: 20%">
            <div class="input-group input-group-sm">
              <input class="form-control" data-source="/rule" name="Trigger" placeholder="{{ __('Trigger event') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Trigger">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th style="width: 15%">
            <div class="input-group input-group-sm">
              <input class="form-control" data-source="/rule" name="Category" placeholder="{{ __('Category') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Category">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th style="width: 7%">
            <div class="input-group input-group-sm">
              <input class="form-control" data-source="/rule" name="Country" placeholder="{{ __('Country') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Country">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th style="width: 7%">
            <div class="input-group input-group-sm">
              <input class="form-control" data-source="/rule" name="Origin" placeholder="{{ __('Origin') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Origin">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th style="width: 9%">
            <div class="input-group input-group-sm">
              <input class="form-control" data-source="/rule" name="Type" placeholder="{{ __('Type') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Type">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th style="width: 3%" title="{{ __('Clear task') }}">C</th>
          <th style="width: 3%" title="{{ __('Delete task') }}">D</th>
        </tr>
      </thead>
      <tbody id="tableList">
        @foreach ($ruleslist as $rule)
        <tr data-id="{{ $rule->id }}" class="reveal-hidden">
          <td>
            <a href="/rule/{{ $rule->id }}" data-panel="ajaxPanel" title="{{ __('Rule data') }}">
              {{ $rule->taskInfo->name }} ({{ $rule->task }})
            </a>
          </td>
          <td>{{ $rule->detail }}</td>
          <td>{{ $rule->trigger->name }} ({{ $rule->trigger_event }})</td>
          <td>{{ $rule->category?->category }}</td>
          <td>{{ $rule->for_country }}</td>
          <td>{{ $rule->for_origin }}</td>
          <td>{{ $rule->type?->type }}</td>
          <td class="text-center">@if($rule->clear_task) ✓ @endif</td>
          <td class="text-center">@if($rule->delete_task) ✓ @endif</td>
        </tr>
        @endforeach
        <tr>
          <td colspan="5">
            {{ $ruleslist->links() }}
          </td>
        </tr>
      </tbody>
    </table>
  </x-slot>
</x-list-with-panel>
@endsection

@section('script')
@endsection
