@extends('layouts.app')

@section('content')
<x-list-with-panel
  :title="__('Actors')"
  create-url="actor/create"
  :create-label="__('Create actor')"
  :create-title="__('Add Actor')"
  :create-attributes="['data-bs-toggle' => 'modal', 'data-bs-target' => '#ajaxModal']"
  :panel-title="__('Actor information')"
  :panel-message="__('Click on actor name to view and edit details')"
  panel-column-class="col-4">
  <x-slot name="list">
    <table class="table table-striped table-hover table-sm">
      <thead>
        <tr id="filter" class="table-primary align-middle">
          <th>
            <div class="input-group input-group-sm" style="width: 150px;">
              <input class="form-control" name="Name" placeholder="{{ __('Name') }}" value="{{ Request::get('Name') }}">
              <button class="btn btn-outline-secondary clear-filter" type="button" style="display: none;" data-target="Name">
                <span>&times;</span>
              </button>
            </div>
          </th>
          <th>{{ __('First name') }}</th>
          <th>{{ __('Display name') }}</th>
          <th class="text-center">{{ __('Company') }} <span class="float-end">{{ __('Person') }}</span></th>
          <th>
            <select id="person" class="form-select form-select-sm px-0" name="selector">
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
        <tr class="reveal-hidden" data-id="{{ $actor->id }}">
          <td>
            <a @if($actor->warn) class="text-danger text-decoration-none" @endif href="/actor/{{ $actor->id }}" data-panel="ajaxPanel" title="{{ __('Actor data') }}">
              {{ $actor->name }}
            </a>
          </td>
          <td>{{ $actor->first_name }}</td>
          <td>{{ $actor->display_name }}</td>
          <td nowrap>{{ empty($actor->company) ? '' : $actor->company->name }}</td>
          <td>
            @if ($actor->phy_person)
            {{ __('Physical') }}
            @else
            {{ __('Legal') }}
            @endif
          </td>
        </tr>
        @endforeach
        <tr>
          <td colspan="5">
            {{ $actorslist->links() }}
          </td>
        </tr>
      </tbody>
    </table>
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
