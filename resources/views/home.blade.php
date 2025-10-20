@extends('layouts.app')

@section('content')
<div class="row g-4">
  <div class="col-12 col-lg-4 d-flex flex-column gap-4" id="leftPanels">
    <div class="card h-100" id="categoriesPanel">
      <div class="card-header d-flex align-items-start justify-content-between gap-3">
        <div>
          <span class="section-eyebrow d-block">{{ __('Overview') }}</span>
          <h2 class="card-title">{{ __('Categories') }}</h2>
        </div>
        @can('readwrite')
          <a href="/matter/create?operation=new" data-bs-target="#ajaxModal" data-bs-toggle="modal" data-size="modal-sm" class="btn btn-primary btn-sm px-3" title="{{ __('Create Matter') }}">{{ __('Create') }}</a>
        @endcan
      </div>
      <div class="card-body card-scroll" id="categoriesList">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th scope="col" class="text-uppercase">{{ __('Category') }}</th>
              <th scope="col" class="text-center">{{ __('Count') }}</th>
              @can('readwrite')
                <th scope="col" class="text-end">{{ __('New') }}</th>
              @endcan
            </tr>
          </thead>
          <tbody>
            @foreach ($categories as $group)
              <tr class="reveal-hidden">
                <th scope="row" class="fw-semibold">
                  <a href="/matter?Cat={{ $group->code }}" class="stretched-link text-decoration-none">{{ $group->category }}</a>
                </th>
                <td class="text-center fw-semibold">{{ $group->total }}</td>
                @can('readwrite')
                  <td class="text-end">
                    <a class="hidden-action" href="/matter/create?operation=new&category={{ $group->code }}" data-bs-target="#ajaxModal" title="Create {{ $group->category }}" data-bs-toggle="modal" data-size="modal-sm">
                      <svg width="18" height="18" fill="currentColor" style="pointer-events: none"><use xlink:href="#plus-circle-fill" /></svg>
                    </a>
                  </td>
                @endcan
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <div class="card" id="usersTasks">
      <div class="card-header d-flex align-items-start justify-content-between gap-3">
        <div>
          <span class="section-eyebrow d-block">{{ __('Team Pulse') }}</span>
          <h2 class="card-title">{{ __('Users tasks') }}</h2>
        </div>
        @can('readwrite')
          <span class="stat-badge">{{ __('Live view') }}</span>
        @endcan
      </div>
      <div class="card-body card-scroll" id="usersTasksPanel">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th scope="col">{{ __('User') }}</th>
              <th scope="col" class="text-center">{{ __('Open') }}</th>
              <th scope="col" class="text-end">{{ __('Hottest') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($taskscount as $group)
              @if ($group->no_of_tasks > 0)
                <tr>
                  <th scope="row" class="fw-semibold">
                    <a href="/home?user_dashboard={{ $group->login }}">{{ $group->login }}</a>
                  </th>
                  <td class="text-center">{{ $group->no_of_tasks }}</td>
                  @php
                    $rowClass = '';
                    if ($group->urgent_date < now()) {
                      $rowClass = 'text-danger fw-semibold';
                    } elseif ($group->urgent_date < now()->addWeeks(2)) {
                      $rowClass = 'text-warning fw-semibold';
                    } else {
                      $rowClass = 'text-muted';
                    }
                  @endphp
                  <td class="text-end {{ $rowClass }}">
                    {{ \Carbon\Carbon::parse($group->urgent_date)->isoFormat('L') }}
                  </td>
                </tr>
              @endif
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-8 d-flex flex-column gap-4" id="filter">
    <div class="card border-0 shadow-sm" id="openTasks">
      <div class="card-header border-0 pb-0">
        <span class="section-eyebrow">{{ __('Focus') }}</span>
        <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3">
          <h2 class="card-title mb-0">{{ __('Open tasks') }}</h2>
          @can('readonly')
            <form class="row gy-2 gx-3 align-items-center w-100" id="openTasksFilter">
              <div class="col-12 col-xl-6">
                <div class="btn-group w-100" role="group" aria-label="{{ __('Filter tasks') }}">
                  <label class="btn btn-light flex-fill mb-0">
                    <input type="radio" class="btn-check" name="what_tasks" id="alltasks" value="0">{{ __('Everyone') }}
                  </label>
                  @if (!Request::filled('user_dashboard'))
                    <label class="btn btn-light flex-fill mb-0">
                      <input type="radio" class="btn-check" name="what_tasks" id="mytasks" value="1">{{ Auth::user()->login }}
                    </label>
                  @endif
                  <label class="btn btn-light flex-fill mb-0">
                    <input type="radio" class="btn-check" name="what_tasks" id="clientTasks" value="2">{{ __('Client') }}
                  </label>
                </div>
              </div>
              <div class="col-12 col-xl-6">
                <div class="input-group shadow-sm">
                  <input type="hidden" id="clientId" name="client_id">
                  <input type="text" class="form-control" data-ac="/actor/autocomplete" data-actarget="client_id" placeholder="{{ __('Select Client') }}">
                  @can('readwrite')
                    <button class="btn btn-light" type="button" id="clearOpenTasks">{{ __('Clear selected on') }}</button>
                    <input type="text" class="form-control" name="datetaskcleardate" id="taskcleardate" value="{{ now()->isoFormat('L') }}">
                  @endcan
                </div>
              </div>
            </form>
          @endcan
        </div>
        <div class="row text-uppercase text-muted fw-semibold small mt-4 g-0 d-none d-lg-flex">
          <div class="col">{{ __('Summary') }}</div>
          <div class="col-2 text-center">{{ __('Matter') }}</div>
          <div class="col text-center">{{ __('Description') }}</div>
          <div class="col-2 text-end">{{ __('Due date') }}</div>
          @can('readwrite')
            <div class="col-1 text-end">{{ __('Clear') }}</div>
          @endcan
        </div>
      </div>
      <div class="card-body pt-4" id="tasklist">
        {{-- Placeholder --}}
      </div>
    </div>

    <div class="card border-0 shadow-sm" id="openRenewals">
      <div class="card-header border-0 pb-0">
        <span class="section-eyebrow">{{ __('Lifecycle') }}</span>
        <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3">
          <h2 class="card-title mb-0">{{ __('Open renewals') }}</h2>
          @can('readwrite')
            <div class="d-flex flex-column flex-sm-row gap-2 w-100 justify-content-end">
              <button class="btn btn-light" type="button" id="clearRenewals">{{ __('Clear selected on') }}</button>
              <input type="text" class="form-control" name="renewalcleardate" id="renewalcleardate" value="{{ now()->isoFormat('L') }}">
            </div>
          @endcan
        </div>
        <div class="row text-uppercase text-muted fw-semibold small mt-4 g-0 d-none d-lg-flex">
          <div class="col">{{ __('Summary') }}</div>
          <div class="col-2 text-center">{{ __('Matter') }}</div>
          <div class="col text-center">{{ __('Description') }}</div>
          <div class="col-2 text-end">{{ __('Due date') }}</div>
          @can('readwrite')
            <div class="col-1 text-end">{{ __('Clear') }}</div>
          @endcan
        </div>
      </div>
      <div class="card-body pt-4" id="renewallist">
        {{-- Placeholder --}}
      </div>
    </div>
  </div>
</div>

@if (session('status'))
  <div class="alert alert-success mt-4 shadow-sm border-0">
    {{ session('status') }}
  </div>
@endif
@endsection

@section('script')
  <script src="{{ asset('js/home.js') }}" defer></script>
@endsection
