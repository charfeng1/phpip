@extends('layouts.app')

@section('content')
<x-list-with-panel
  :title="__('Actors')"
  create-url="actor/create"
  :create-label="__('Create actor')"
  :create-title="__('Add Actor')"
  :create-attributes="['data-modal-target' => '#ajaxModal']"
  :panel-title="__('Actor information')"
  :panel-message="__('Click on actor name to view and edit details')"
  panel-column-class="w-80 lg:w-96">
  <x-slot name="list">
    <div class="overflow-x-auto">
      <table class="table table-zebra table-sm">
        <thead>
          <tr id="filter" class="bg-primary/5">
            <th class="font-medium">
              <input
                type="text"
                class="input input-bordered input-sm w-full max-w-[150px]"
                name="Name"
                placeholder="{{ __('Name') }}"
                value="{{ Request::get('Name') }}"
              >
            </th>
            <th class="font-medium text-base-content/70">{{ __('First name') }}</th>
            <th class="font-medium text-base-content/70">{{ __('Display name') }}</th>
            <th class="font-medium text-base-content/70">{{ __('Company') }}</th>
            <th class="font-medium">
              <select
                id="person"
                class="select select-bordered select-sm w-full max-w-[120px]"
                name="selector"
              >
                <option value="" selected>{{ __('All') }}</option>
                <option value="phy_p">{{ __('Physical') }}</option>
                <option value="leg_p">{{ __('Legal') }}</option>
                <option value="warn">{{ __('Warn') }}</option>
              </select>
            </th>
          </tr>
        </thead>
        <tbody id="tableList">
          @foreach ($actorslist as $actor)
          <tr class="hover:bg-base-200/50 cursor-pointer transition-colors" data-id="{{ $actor->id }}">
            <td>
              <a
                href="/actor/{{ $actor->id }}"
                data-panel="ajaxPanel"
                title="{{ __('Actor data') }}"
                class="link link-hover font-medium {{ $actor->warn ? 'text-error' : 'text-primary' }}"
              >
                {{ $actor->name }}
              </a>
            </td>
            <td class="text-base-content/70">{{ $actor->first_name }}</td>
            <td class="text-base-content/70">{{ $actor->display_name }}</td>
            <td class="text-base-content/70 whitespace-nowrap">{{ empty($actor->company) ? '' : $actor->company->name }}</td>
            <td>
              @if ($actor->phy_person)
              <span class="badge badge-ghost badge-sm">{{ __('Physical') }}</span>
              @else
              <span class="badge badge-outline badge-sm">{{ __('Legal') }}</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    {{-- Pagination --}}
    <div class="px-4 py-3 border-t border-base-300">
      {{ $actorslist->links() }}
    </div>
  </x-slot>
</x-list-with-panel>
@endsection

@section('script')
<script>
  person.onchange = (e) => {
    if (e.target.value.length === 0) {
      url.searchParams.delete(e.target.name);
    } else {
      url.searchParams.set(e.target.name, e.target.value);
    }
    refreshList();
  }
</script>
@endsection
