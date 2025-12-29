@extends('layouts.app')

@section('content')
<x-list-with-panel
  title="{{ __('Rules') }}"
  create-url="rule/create"
  :create-label="__('Create Rule')"
  :create-title="__('Rule data')"
  create-resource="/rule/create/"
  :create-attributes="['data-modal-target' => '#ajaxModal', 'data-source' => '/rule']"
  :panel-title="__('Rule information')"
  :panel-message="__('Click on rule to view and edit details')"
  panel-column-class="w-80 lg:w-96">
  <x-slot name="titleSlot">
    <div class="flex items-center gap-2">
      {{ __('Rules') }}
      <a class="text-primary hover:text-primary-focus" href="https://github.com/jjdejong/phpip/wiki/Tables#task_rules" target="_blank">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </a>
    </div>
  </x-slot>
  <x-slot name="list">
    <div class="overflow-x-auto">
      <table class="table table-zebra table-sm">
        <thead>
          <tr id="filter" class="bg-primary/5">
            <th class="w-1/5 font-medium">
              <input class="input input-bordered input-sm w-full" data-source="/rule" name="Task" placeholder="{{ __('Task') }}">
            </th>
            <th class="w-[15%] font-medium">
              <input class="input input-bordered input-sm w-full" data-source="/rule" name="Detail" placeholder="{{ __('Detail') }}">
            </th>
            <th class="w-1/5 font-medium">
              <input class="input input-bordered input-sm w-full" data-source="/rule" name="Trigger" placeholder="{{ __('Trigger event') }}">
            </th>
            <th class="w-[15%] font-medium">
              <input class="input input-bordered input-sm w-full" data-source="/rule" name="Category" placeholder="{{ __('Category') }}">
            </th>
            <th class="w-[7%] font-medium">
              <input class="input input-bordered input-sm w-full" data-source="/rule" name="Country" placeholder="{{ __('Country') }}">
            </th>
            <th class="w-[7%] font-medium">
              <input class="input input-bordered input-sm w-full" data-source="/rule" name="Origin" placeholder="{{ __('Origin') }}">
            </th>
            <th class="w-[9%] font-medium">
              <input class="input input-bordered input-sm w-full" data-source="/rule" name="Type" placeholder="{{ __('Type') }}">
            </th>
            <th class="w-[3%] text-center font-medium" title="{{ __('Clear task') }}">C</th>
            <th class="w-[3%] text-center font-medium" title="{{ __('Delete task') }}">D</th>
          </tr>
        </thead>
        <tbody id="tableList">
          @foreach ($ruleslist as $rule)
          <tr data-id="{{ $rule->id }}" class="hover:bg-base-200/50 cursor-pointer transition-colors">
            <td>
              <a href="/rule/{{ $rule->id }}" data-panel="ajaxPanel" title="{{ __('Rule data') }}" class="link link-primary">
                {{ $rule->taskInfo->name }} ({{ $rule->task }})
              </a>
            </td>
            <td class="text-base-content/70">{{ $rule->detail }}</td>
            <td class="text-base-content/70">{{ $rule->trigger->name }} ({{ $rule->trigger_event }})</td>
            <td class="text-base-content/70">{{ $rule->category?->category }}</td>
            <td class="text-base-content/70">{{ $rule->for_country }}</td>
            <td class="text-base-content/70">{{ $rule->for_origin }}</td>
            <td class="text-base-content/70">{{ $rule->type?->type }}</td>
            <td class="text-center">
              @if($rule->clear_task)
              <span class="text-success">&#10003;</span>
              @endif
            </td>
            <td class="text-center">
              @if($rule->delete_task)
              <span class="text-success">&#10003;</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    {{-- Pagination --}}
    <div class="px-4 py-3 border-t border-base-300">
      {{ $ruleslist->links() }}
    </div>
  </x-slot>
</x-list-with-panel>
@endsection

@section('script')
@endsection
