@extends('layouts.app')

@section('body-class', 'home-page')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
  <!-- Left Sidebar -->
  <div class="lg:col-span-4 space-y-4">
    <!-- Categories Card -->
    <div class="card bg-base-100 shadow-sm border border-base-300">
      <div class="card-title bg-base-200/50 px-4 py-3 text-sm font-medium border-b border-base-300 flex justify-between items-center">
        <div>
          <h5 class="flex items-center gap-2 text-base font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            {{ __('Categories') }}
          </h5>
          <p class="text-xs text-base-content/60 mt-1">{{ __('Case classifications') }}</p>
        </div>
        @can('readwrite')
          <a href="/matter/create?operation=new" data-modal-target="#ajaxModal" data-size="modal-sm"
             class="btn btn-primary btn-xs" title="{{ __('Create Matter') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('New') }}
          </a>
        @endcan
      </div>
      <div class="card-body p-0" id="categoriesList">
        <div class="overflow-x-auto">
          <table class="table table-sm">
            <thead>
              <tr class="border-b border-base-200">
                <th class="w-2/5 font-medium text-xs text-base-content/70">{{ __('Category') }}</th>
                <th class="w-1/5 text-center font-medium text-xs text-base-content/70">{{ __('Count') }}</th>
                <th class="w-2/5"></th>
              </tr>
            </thead>
            <tbody>
              @foreach ($categories as $group)
              <tr class="hover:bg-base-200/50 transition-colors group">
                <td>
                  <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-primary"></div>
                    <a href="/matter?Cat={{ $group->code }}" class="link link-hover font-medium">
                      {{ $group->category }}
                    </a>
                  </div>
                </td>
                <td class="text-center">
                  <span class="badge badge-primary badge-sm">{{ $group->total }}</span>
                </td>
                <td class="text-right">
                  @can('readwrite')
                    <a class="btn btn-ghost btn-xs opacity-0 group-hover:opacity-100 transition-opacity" href="/matter/create?operation=new&category={{$group->code}}"
                       data-modal-target="#ajaxModal" title="Create {{ $group->category }}" data-size="modal-sm">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </a>
                  @endcan
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- User Tasks Card -->
    <div class="card bg-base-100 shadow-sm border border-base-300">
      <div class="card-title bg-base-200/50 px-4 py-3 text-sm font-medium border-b border-base-300">
        <div>
          <h5 class="flex items-center gap-2 text-base font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ __('Team Tasks') }}
          </h5>
          <p class="text-xs text-base-content/60 mt-1">{{ __('Pending assignments') }}</p>
        </div>
      </div>
      <div class="card-body p-0" id="usersTasksPanel">
        <div class="overflow-x-auto">
          <table class="table table-sm">
            <thead>
              <tr class="border-b border-base-200">
                <th class="w-2/5 font-medium text-xs text-base-content/70">{{ __('User') }}</th>
                <th class="w-1/5 text-center font-medium text-xs text-base-content/70">{{ __('Open') }}</th>
                <th class="w-2/5 text-center font-medium text-xs text-base-content/70">{{ __('Urgent') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($taskscount as $group)
                @if ($group->no_of_tasks > 0)
                <tr class="hover:bg-base-200/50 transition-colors">
                  <td>
                    <div class="flex items-center gap-2">
                      <div class="avatar placeholder">
                        <div class="bg-secondary text-secondary-content rounded-full w-6 h-6">
                          <span class="text-xs font-bold">{{ strtoupper(substr($group->login, 0, 1)) }}</span>
                        </div>
                      </div>
                      <a href="/home?user_dashboard={{ $group->login }}" class="link link-hover font-medium">
                        {{ $group->login }}
                      </a>
                    </div>
                  </td>
                  <td class="text-center">
                    <span class="badge badge-primary badge-sm">{{ $group->no_of_tasks }}</span>
                  </td>
                  <td class="text-center">
                    @if ($group->urgent_date && $group->urgent_date < now())
                      <span class="badge badge-error badge-sm">{{ __('Overdue') }}</span>
                    @elseif ($group->urgent_date && $group->urgent_date < now()->addWeeks(2))
                      <span class="badge badge-warning badge-sm">{{ __('Soon') }}</span>
                    @elseif ($group->urgent_date)
                      <span class="badge badge-success badge-sm">{{ \Carbon\Carbon::parse($group->urgent_date)->isoFormat('L') }}</span>
                    @else
                      <span class="badge badge-ghost badge-sm">{{ __('N/A') }}</span>
                    @endif
                  </td>
                </tr>
                @endif
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 gap-3">
      <div class="card bg-base-100 shadow-sm border border-base-300">
        <div class="card-body p-4 text-center">
          <div class="text-3xl font-bold text-primary">{{ count($categories) }}</div>
          <div class="text-xs text-base-content/60 mt-1">{{ __('Categories') }}</div>
        </div>
      </div>
      <div class="card bg-base-100 shadow-sm border border-base-300">
        <div class="card-body p-4 text-center">
          <div class="text-3xl font-bold text-secondary">{{ collect($taskscount)->sum('no_of_tasks') }}</div>
          <div class="text-xs text-base-content/60 mt-1">{{ __('Total Tasks') }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content Area -->
  <div class="lg:col-span-8 space-y-4" id="filter">
    <!-- Open tasks Section -->
    <div class="card bg-base-100 shadow-sm border border-base-300">
      <div class="bg-base-200/50 px-4 py-3 border-b border-base-300">
        <div class="flex flex-wrap items-center gap-3">
          <h5 class="flex items-center gap-2 font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            {{ __('Open tasks') }}
          </h5>

          @can('readonly')
          <div class="flex flex-wrap items-center gap-2 flex-1">
            <div class="join">
              <input type="radio" class="join-item btn btn-sm" name="what_tasks" id="alltasks" value="0" aria-label="{{ __('Everyone') }}" checked>
              @if(!Request::filled('user_dashboard'))
                <input type="radio" class="join-item btn btn-sm" name="what_tasks" id="usertasks" value="1" aria-label="{{ __('My Tasks') }}">
                <input type="radio" class="join-item btn btn-sm" name="what_tasks" id="teamtasks" value="2" aria-label="{{ __('My Team') }}">
              @endif
              <input type="radio" class="join-item btn btn-sm" name="what_tasks" id="clientTasks" value="3" aria-label="{{ __('Client') }}">
            </div>
            <div class="flex-1 min-w-[200px]">
              <input type="hidden" id="clientId" name="client_id">
              <input type="text" class="input input-bordered input-sm w-full" data-ac="/actor/autocomplete" data-actarget="client_id"
                     placeholder="{{ __('Select Client') }}">
            </div>
          </div>
          @endcan

          @can('readwrite')
          <div class="join ml-auto">
            <button class="btn btn-sm btn-outline btn-primary join-item" type="button" id="clearOpenTasks">
              {{ __('Clear Selected') }}
            </button>
            <input type="text" class="input input-bordered input-sm join-item w-28" name="datetaskcleardate" id="taskcleardate"
                   value="{{ now()->isoFormat('L') }}">
          </div>
          @endcan
        </div>
      </div>

      <div class="card-body p-6" id="tasklist">
        @include('task.index', ['tasks' => $tasks, 'isrenewals' => false])
      </div>
    </div>

    <!-- Open Renewals Section -->
    <div class="card bg-base-100 shadow-sm border border-base-300">
      <div class="bg-base-200/50 px-4 py-3 border-b border-base-300">
        <div class="flex flex-wrap items-center gap-3">
          <h5 class="flex items-center gap-2 font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ __('Renewals') }}
          </h5>

          @can('readwrite')
          <div class="join ml-auto">
            <button class="btn btn-sm btn-outline btn-success join-item" type="button" id="clearRenewals">
              {{ __('Clear Selected') }}
            </button>
            <input type="text" class="input input-bordered input-sm join-item w-28" name="renewalcleardate" id="renewalcleardate"
                   value="{{ now()->isoFormat('L') }}">
          </div>
          @endcan
        </div>
      </div>

      <div class="card-body p-6" id="renewallist">
        @include('task.index', ['tasks' => $renewals, 'isrenewals' => true])
      </div>
    </div>
  </div>
</div>

@if (session('status'))
<div class="alert alert-success mb-4">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
  </svg>
  <span>{{ session('status') }}</span>
</div>
@endif

@endsection

@section('script')
@endsection
