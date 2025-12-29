@extends('layouts.app')

@section('content')
<x-list-with-panel
  :title="__('Users')"
  create-url="user/create"
  :create-label="__('Create user')"
  :create-title="__('Create User')"
  :create-attributes="['data-modal-target' => '#ajaxModal']"
  :panel-title="__('User information')"
  :panel-message="__('Click on user name to view and edit details')"
  panel-column-class="w-80 lg:w-96">
  <x-slot name="list">
    <div class="overflow-x-auto">
      <table class="table table-zebra table-sm">
        <thead>
          <tr id="filter" class="bg-primary/5">
            <th class="font-medium">
              <input
                type="text"
                class="input input-bordered input-sm w-full"
                name="Name"
                placeholder="{{ __('Name') }}"
                value="{{ Request::get('Name') }}"
              >
            </th>
            <th class="font-medium text-base-content/70">{{ __('Role') }}</th>
            <th class="font-medium text-base-content/70">{{ __('User name') }}</th>
            <th class="font-medium text-base-content/70">{{ __('Company') }}</th>
          </tr>
        </thead>
        <tbody id="tableList">
          @foreach ($userslist as $user)
          <tr class="hover:bg-base-200/50 cursor-pointer transition-colors" data-id="{{ $user->id }}">
            <td>
              <a
                href="/user/{{ $user->id }}"
                data-panel="ajaxPanel"
                title="{{ __('User data') }}"
                class="link link-hover font-medium {{ $user->warn ? 'text-error' : 'text-primary' }}"
              >
                {{ $user->name }}
              </a>
            </td>
            <td class="text-base-content/70">
              <span class="badge badge-ghost badge-sm">{{ $user->default_role }}</span>
            </td>
            <td class="font-mono text-sm text-base-content/70">{{ $user->login }}</td>
            <td class="text-base-content/70">{{ empty($user->company) ? '' : $user->company->name }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    {{-- Pagination --}}
    <div class="px-4 py-3 border-t border-base-300">
      {{ $userslist->links() }}
    </div>
  </x-slot>
</x-list-with-panel>
@endsection

@section('script')
@endsection
