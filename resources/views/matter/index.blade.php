@php
    $tab = Request::get('tab') == 1 ? 1 : 0;
    $hideTab0 = $tab == 1 ? 'hidden' : '';
    $hideTab1 = $tab == 0 ? 'hidden' : '';
@endphp

@extends('layouts.app')

@section('body-class', 'matter-index-page')


@section('content')

<div>
  <!-- Header Section -->
  <div class="flex items-center justify-between bg-base-200 px-4 py-3 rounded-lg mb-4 shadow-sm">
    <div>
      <h1 class="text-xl font-semibold text-base-content flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        {{ __('Matter Management') }}
      </h1>
      <p class="text-sm text-base-content/60 mt-1">{{ $matters->total() }} {{ __('cases found') }}</p>
    </div>
    @can('readwrite')
      <a href="/matter/create?operation=new" data-modal-target="#ajaxModal" data-size="modal-sm"
         class="btn btn-primary btn-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ __('New Matter') }}
      </a>
    @endcan
  </div>

  <div class="card bg-base-100 shadow-sm border border-base-300" x-data="{
    tab: {{ $tab }},
    showContainers: {{ Request::get('Ctnr') ? 'true' : 'false' }},
    showMine: {{ Request::has('responsible') ? 'true' : 'false' }},
    showTeam: {{ Request::get('team') ? 'true' : 'false' }},
    includeDead: {{ Request::get('include_dead') ? 'true' : 'false' }}
  }">
    <!-- Filter Section -->
    <div class="bg-base-200/50 px-4 py-3 border-b border-base-300">
      <div class="flex flex-wrap items-center gap-2 mb-3">
        {{-- Containers Toggle --}}
        <label class="btn btn-sm" :class="showContainers ? 'btn-primary' : 'btn-ghost'">
          <input type="checkbox" class="hidden" name="Ctnr" x-model="showContainers" id="btnshowctnr">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          {{ __('Containers') }}
        </label>

        <div class="divider divider-horizontal mx-0"></div>

        {{-- View Toggle --}}
        <div class="join">
          <label class="btn btn-sm join-item" :class="tab == 0 ? 'btn-primary' : 'btn-ghost'">
            <input type="radio" class="hidden" name="tab" value="0" x-model="tab" id="btnactorview">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            {{ __('Actor View') }}
          </label>
          <label class="btn btn-sm join-item" :class="tab == 1 ? 'btn-primary' : 'btn-ghost'">
            <input type="radio" class="hidden" name="tab" value="1" x-model="tab" id="btnstatusview">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            {{ __('Status View') }}
          </label>
        </div>

        @can('readonly')
        <div class="divider divider-horizontal mx-0"></div>

        {{-- Mine / Team Filters --}}
        <label class="btn btn-sm" :class="showMine ? 'btn-secondary' : 'btn-ghost'">
          <input type="checkbox" class="hidden" name="responsible" x-model="showMine" id="btnshowmine">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ __('Mine') }}
        </label>
        <label class="btn btn-sm" :class="showTeam ? 'btn-secondary' : 'btn-ghost'">
          <input type="checkbox" class="hidden" name="team" x-model="showTeam" id="btnshowteam">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          {{ __('My Team') }}
        </label>
        @endcan

        <div class="divider divider-horizontal mx-0"></div>

        {{-- Include Dead --}}
        <label class="btn btn-sm" :class="includeDead ? 'btn-warning' : 'btn-ghost'">
          <input type="checkbox" class="hidden" name="include_dead" x-model="includeDead" id="btnincludedead">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
          </svg>
          {{ __('Include Dead') }}
        </label>

        {{-- Spacer --}}
        <div class="flex-1"></div>

        {{-- Action Buttons --}}
        <button id="exportList" type="button" class="btn btn-sm btn-outline btn-info">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          {{ __('Export') }}
        </button>
        <button id="clearFilters" type="button" class="btn btn-sm btn-ghost" onclick="window.location.href = '/matter'">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          {{ __('Clear filters') }}
        </button>
      </div>

      <input type="hidden" name="display_with" value="{{ Request::get('display_with') }}">
    </div>

    <!-- Table with Filters -->
    <div class="overflow-x-auto">
      <table class="table table-zebra table-sm w-full">
        <thead>
          <tr id="filterFields" class="bg-primary/5">
            <th class="w-[10%]">
              <div class="flex items-center gap-1">
                <input class="input input-bordered input-xs flex-1" name="Ref" placeholder="{{ __('Ref') }}" value="{{ Request::get('Ref') }}">
                <button class="btn btn-xs btn-ghost {{ Request::get('sortkey') == 'caseref' ? 'btn-active' : '' }}"
                        type="button" data-sortkey="caseref" data-sortdir="desc" title="Sort">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
              </div>
            </th>
            <th class="w-[5%]">
              <input class="input input-bordered input-xs text-center w-full" name="Cat" placeholder="{{ __('Cat') }}" value="{{ Request::get('Cat') }}">
            </th>
            <th class="w-[12%]">
              <div class="flex items-center gap-1">
                <input class="input input-bordered input-xs flex-1" name="Status" placeholder="{{ __('Status') }}" value="{{ Request::get('Status') }}">
                <button class="btn btn-xs btn-ghost {{ Request::get('sortkey') == 'event_name.name' ? 'btn-active' : '' }}"
                        type="button" data-sortkey="event_name.name" data-sortdir="asc" title="Sort">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
              </div>
            </th>
            @can('readonly')
            <th class="w-[15%] tab0" :class="{ 'hidden': tab == 1 }">
              <div class="flex items-center gap-1">
                <input class="input input-bordered input-xs flex-1" name="Client" placeholder="{{ __('Client') }}" value="{{ Request::get('Client') }}">
                <button class="btn btn-xs btn-ghost {{ Request::get('sortkey') == 'cli.name' ? 'btn-active' : '' }}"
                        type="button" data-sortkey="cli.name" data-sortdir="asc" title="Sort">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
              </div>
            </th>
            @endcan
            <th class="w-[8%] tab0" :class="{ 'hidden': tab == 1 }">
              <input class="input input-bordered input-xs text-center w-full" name="ClRef" placeholder="{{ __('Cl. Ref') }}" value="{{ Request::get('ClRef') }}">
            </th>
            <th class="w-[12%] tab0" :class="{ 'hidden': tab == 1 }">
              <div class="flex items-center gap-1">
                <input class="input input-bordered input-xs flex-1" name="Applicant" placeholder="{{ __('Applicant') }}" value="{{ Request::get('Applicant') }}">
                <button class="btn btn-xs btn-ghost {{ Request::get('sortkey') == 'app.name' ? 'btn-active' : '' }}"
                        type="button" data-sortkey="app.name" data-sortdir="asc" title="Sort">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
              </div>
            </th>
            <th class="w-[10%] tab0" :class="{ 'hidden': tab == 1 }">
              <input class="input input-bordered input-xs w-full" name="Agent" placeholder="{{ __('Agent') }}" value="{{ Request::get('Agent') }}">
            </th>
            <th class="w-[30%] tab0" :class="{ 'hidden': tab == 1 }">
              <input class="input input-bordered input-xs w-full" name="Title" placeholder="{{ __('Title') }}" value="{{ Request::get('Title') }}">
            </th>
            <th class="w-[10%] tab1" :class="{ 'hidden': tab == 0 }">
              <div class="flex items-center gap-1">
                <input class="input input-bordered input-xs flex-1" name="Status_date" placeholder="{{ __('Date') }}" value="{{ Request::get('Status_date') }}">
                <button class="btn btn-xs btn-ghost {{ Request::get('sortkey') == 'status.event_date' ? 'btn-active' : '' }}"
                        type="button" data-sortkey="status.event_date" data-sortdir="asc" title="Sort">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
              </div>
            </th>
            <th class="w-[8%] tab1" :class="{ 'hidden': tab == 0 }">
              <input class="input input-bordered input-xs text-center w-full" name="Filed" placeholder="{{ __('Filed') }}" value="{{ Request::get('Filed') }}">
            </th>
            <th class="w-[8%] tab1" :class="{ 'hidden': tab == 0 }">
              <input class="input input-bordered input-xs text-center w-full" name="Published" placeholder="{{ __('Published') }}" value="{{ Request::get('Published') }}">
            </th>
            <th class="w-[8%] tab1" :class="{ 'hidden': tab == 0 }">
              <input class="input input-bordered input-xs text-center w-full" name="Granted" placeholder="{{ __('Granted') }}" value="{{ Request::get('Granted') }}">
            </th>
          </tr>
        </thead>
        <tbody id="matterList">
          @foreach ($matters as $matter)
          @php
          // Format the publication number for searching on Espacenet
          $published = 0;
          if ( $matter->PubNo || $matter->GrtNo) {
            $published = 1;
            if ( $matter->origin == 'EP' )
              $CC = 'EP';
            else
              $CC = $matter->country;
            $removethese = [ "/^$matter->country/", '/ /', '/,/', '/-/', '/\//' ];
            $pubno = preg_replace ( $removethese, '', $matter->PubNo );
            if ( $CC == 'US' ) {
              if ( $matter->GrtNo )
                $pubno = preg_replace ( $removethese, '', $matter->GrtNo );
              else
                $pubno = substr ( $pubno, 0, 4 ) . substr ( $pubno, - 6 );
            }
          }
          @endphp
          <tr class="hover:bg-base-200/50 cursor-pointer transition-colors {{ $matter->dead ? 'opacity-50' : '' }} {{ !$matter->container_id ? 'bg-info/5' : '' }}">
            <td>
              <div class="flex items-center gap-2">
                <a href="/matter/{{ $matter->id }}" target="_blank" class="link link-primary link-hover font-medium">
                  {{ $matter->Ref }}
                </a>
                @if ($matter->container_id)
                  <span class="badge badge-outline badge-xs">{{ __('Container') }}</span>
                @endif
              </div>
            </td>
            <td>
              <span class="badge badge-primary badge-sm">{{ $matter->Cat }}</span>
            </td>
            <td>
              @if ($published)
                <a href="http://worldwide.espacenet.com/publicationDetails/biblio?DB=EPODOC&CC={{ $CC }}&NR={{ $pubno }}"
                   target="_blank" class="link link-hover text-sm" title="Open in Espacenet">
                  {{ $matter->Status }}
                </a>
              @else
                <span class="badge badge-ghost badge-sm">{{ $matter->Status }}</span>
              @endif
            </td>
            @can('readonly')
            <td class="tab0" :class="{ 'hidden': tab == 1 }">
              <span class="truncate block max-w-[200px] text-base-content/80">{{ $matter->clientname }}</span>
            </td>
            @endcan
            <td class="tab0" :class="{ 'hidden': tab == 1 }">
              <span class="text-xs text-base-content/60 font-mono">{{ $matter->ClRef }}</span>
            </td>
            <td class="tab0" :class="{ 'hidden': tab == 1 }">
              <span class="truncate block max-w-[200px] text-base-content/80">{{ $matter->Applicant }}</span>
            </td>
            <td class="tab0" :class="{ 'hidden': tab == 1 }">
              <span class="text-xs text-base-content/60">{{ $matter->AgentName }}</span>
            </td>
            <td class="tab0" :class="{ 'hidden': tab == 1 }">
              <div class="truncate max-w-[300px] text-sm" title="{{ $matter->container_id && $matter->Title2 ? $matter->Title2 : $matter->Title }}">
                {{ $matter->container_id && $matter->Title2 ? $matter->Title2 : $matter->Title }}
              </div>
            </td>
            <td class="tab1" :class="{ 'hidden': tab == 0 }">
              <span class="badge {{ $matter->Status_date ? 'badge-success' : 'badge-ghost' }} badge-sm">
                {{ $matter->Status_date }}
              </span>
            </td>
            <td class="tab1" :class="{ 'hidden': tab == 0 }">
              <span class="badge badge-info badge-sm">{{ $matter->Filed }}</span>
            </td>
            <td class="tab1" :class="{ 'hidden': tab == 0 }">
              <span class="badge {{ $matter->Published ? 'badge-success' : 'badge-ghost' }} badge-sm">
                {{ $matter->Published }}
              </span>
            </td>
            <td class="tab1" :class="{ 'hidden': tab == 0 }">
              <span class="badge {{ $matter->Granted ? 'badge-success' : 'badge-warning' }} badge-sm">
                {{ $matter->Granted }}
              </span>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="px-4 py-3 border-t border-base-300">
      {{ $matters->links() }}
    </div>
  </div>
</div>

@endsection

@push('script')
<script>
// Enhanced table interactions
document.addEventListener('DOMContentLoaded', function() {
  // Add click handlers for table rows
  const tableRows = document.querySelectorAll('#matterList tr');
  tableRows.forEach(row => {
    row.addEventListener('click', function(e) {
      // Don't trigger row click if clicking on a link or button
      if (e.target.tagName !== 'A' && !e.target.closest('a')) {
        const firstLink = this.querySelector('a[href*="/matter/"]');
        if (firstLink) {
          firstLink.click();
        }
      }
    });
  });
});
</script>
@endpush
