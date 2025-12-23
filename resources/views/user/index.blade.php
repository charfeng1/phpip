@extends('layouts.app')

@section('content')
<x-list-with-panel
  :title="__('Users')"
  create-url="user/create"
  :create-label="__('Create user')"
  :create-title="__('Create User')"
  :create-attributes="['data-bs-toggle' => 'modal', 'data-bs-target' => '#ajaxModal']"
  :panel-title="__('User information')"
  :panel-message="__('Click on user name to view and edit details')"
  panel-column-class="col-4">
  <x-slot name="list">
    <table class="table table-striped table-hover table-sm">
      <thead class="card-header">
        <tr id="filter" class="table-primary align-middle">
          <th><input class="form-control" name="Name" placeholder="{{ __('Name') }}" value="{{ Request::get('Name') }}"></th>
          <th>{{ __('Role') }}</th>
          <th>{{ __('User name') }}</th>
          <th>{{ __('Company') }}</th>
        </tr>
      </thead>
      <tbody id="tableList" class="card-body">
        @foreach ($userslist as $user)
        <tr class="reveal-hidden" data-id="{{ $user->id }}">
          <td>
            <a @if($user->warn) class="text-danger text-decoration-none" @endif href="/user/{{ $user->id }}" data-panel="ajaxPanel" title="{{ __('User data') }}">
              {{ $user->name }}
            </a>
          </td>
          <td>{{ $user->default_role }}</td>
          <td>{{ $user->login }}</td>
          <td>{{ empty($user->company) ? '' : $user->company->name }}</td>
        </tr>
        @endforeach
        <tr>
          <td colspan="5">
            {{ $userslist->links() }}
          </td>
        </tr>
      </tbody>
    </table>
  </x-slot>
</x-list-with-panel>
@endsection

@section('script')
@endsection
