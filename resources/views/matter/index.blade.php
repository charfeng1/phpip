@php
    $tab = Request::get('tab') == 1 ? 1 : 0;
    $hideTab0 = $tab == 1 ? 'd-none' : '';
    $hideTab1 = $tab == 0 ? 'd-none' : '';
@endphp

@extends('layouts.app')

@section('style')
<style>
  .matter-table {
    border-radius: var(--radius-2xl);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    background: var(--bg-primary);
    border: 1px solid var(--border-light);
  }

  .matter-table thead {
    background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
  }

  .matter-table th {
    font-weight: var(--font-weight-semibold);
    font-size: var(--font-size-xs);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-tertiary);
    padding: var(--space-4) var(--space-5);
    border-bottom: 2px solid var(--border-light);
    white-space: nowrap;
  }

  .matter-table td {
    padding: var(--space-4) var(--space-5);
    vertical-align: middle;
    border-bottom: 1px solid var(--border-light);
    color: var(--text-primary);
  }

  .matter-table tbody tr {
    transition: all var(--transition-fast);
    cursor: pointer;
  }

  .matter-table tbody tr:hover {
    background: var(--bg-hover);
    transform: scale(1.005);
    box-shadow: var(--shadow-sm);
  }

  .matter-table tbody tr:last-child td {
    border-bottom: none;
  }

  .filter-input {
    border-radius: var(--radius-xl);
    padding: var(--space-2) var(--space-3);
    font-size: var(--font-size-sm);
    border: 1px solid var(--border-medium);
    background: var(--bg-primary);
    transition: all var(--transition-fast);
  }

  .filter-input:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px var(--color-primary-light);
    outline: none;
  }

  .filter-input:not(:placeholder-shown) {
    border-color: var(--color-primary);
    font-weight: var(--font-weight-medium);
  }

  .filter-btn-group {
    display: flex;
    gap: var(--space-2);
    flex-wrap: wrap;
    margin-bottom: var(--space-4);
  }

  .filter-btn {
    border-radius: var(--radius-xl);
    padding: var(--space-2) var(--space-4);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    transition: all var(--transition-fast);
    border: 1px solid var(--border-medium);
    background: var(--bg-primary);
    color: var(--text-secondary);
    position: relative;
    overflow: hidden;
  }

  .filter-btn:hover {
    background: var(--bg-hover);
    color: var(--text-primary);
    border-color: var(--border-strong);
    transform: translateY(-1px);
  }

  .filter-btn.active {
    background: var(--color-primary);
    color: var(--text-inverse);
    border-color: var(--color-primary);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
  }

  .sort-btn {
    background: transparent;
    border: 1px solid var(--border-medium);
    border-radius: var(--radius-md);
    padding: var(--space-1) var(--space-2);
    font-size: var(--font-size-xs);
    transition: all var(--transition-fast);
    margin-left: var(--space-1);
  }

  .sort-btn:hover {
    background: var(--color-primary);
    color: var(--text-inverse);
    border-color: var(--color-primary);
    transform: translateY(-1px);
  }

  .sort-btn.active {
    background: var(--color-primary);
    color: var(--text-inverse);
    border-color: var(--color-primary);
  }

  .matter-link {
    color: var(--text-link);
    text-decoration: none;
    font-weight: var(--font-weight-medium);
    transition: color var(--transition-fast);
  }

  .matter-link:hover {
    color: var(--color-primary-hover);
    text-decoration: underline;
  }

  .status-badge {
    padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .status-active {
    background: var(--color-success);
    color: white;
  }

  .status-pending {
    background: var(--color-warning);
    color: white;
  }

  .status-inactive {
    background: var(--color-gray-400);
    color: white;
  }

  .container-indicator {
    display: inline-flex;
    align-items: center;
    padding: var(--space-1) var(--space-2);
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
    font-size: var(--font-size-xs);
    color: var(--text-tertiary);
    margin-left: var(--space-2);
  }

  .dead-matter {
    opacity: 0.6;
    text-decoration: line-through;
  }

  .action-buttons {
    display: flex;
    gap: var(--space-2);
    margin-top: var(--space-4);
  }

  .export-btn {
    background: var(--color-secondary);
    color: var(--text-inverse);
    border: none;
    border-radius: var(--radius-xl);
    padding: var(--space-2) var(--space-4);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    transition: all var(--transition-fast);
  }

  .export-btn:hover {
    background: var(--color-secondary);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(88, 86, 214, 0.3);
  }

  .clear-btn {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    border: 1px solid var(--border-medium);
    border-radius: var(--radius-xl);
    padding: var(--space-2) var(--space-4);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    transition: all var(--transition-fast);
  }

  .clear-btn:hover {
    background: var(--color-danger);
    color: var(--text-inverse);
    border-color: var(--color-danger);
    transform: translateY(-1px);
  }

  @media (max-width: 768px) {
    .filter-btn-group {
      gap: var(--space-1);
    }

    .filter-btn {
      font-size: var(--font-size-xs);
      padding: var(--space-1) var(--space-3);
    }

    .matter-table {
      font-size: var(--font-size-xs);
    }

    .matter-table th,
    .matter-table td {
      padding: var(--space-2) var(--space-3);
    }

    .action-buttons {
      flex-direction: column;
      gap: var(--space-2);
    }
  }
</style>
@endsection

@section('content')

<div class="animate-fade-in">
  <!-- Header Section -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h2 mb-1 d-flex align-items-center">
        <svg width="28" height="28" fill="var(--color-primary)" viewBox="0 0 24 24" class="me-3">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/>
          <path d="M14 2v6h6"/>
        </svg>
        {{ __('Matter Management') }}
      </h1>
      <p class="text-tertiary mb-0">{{ count($matters) }} {{ __('cases found') }}</p>
    </div>
    @can('readwrite')
      <a href="/matter/create?operation=new" data-bs-target="#ajaxModal" data-bs-toggle="modal" data-size="modal-sm"
         class="btn btn-primary">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-2">
          <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
        </svg>
        {{ __('New Matter') }}
      </a>
    @endcan
  </div>

  <div class="dashboard-card" x-data="{
    tab: {{ $tab }},
    showContainers: {{ Request::get('Ctnr') ? 'true' : 'false' }},
    showMine: {{ Request::has('responsible') ? 'true' : 'false' }},
    includeDead: {{ Request::get('include_dead') ? 'true' : 'false' }}
  }">
    <!-- Filter Section -->
    <div class="card-header bg-gradient">
      <div class="filter-btn-group">
        <div class="btn-group" role="group">
          <input type="checkbox" class="btn-check" name="Ctnr" x-model="showContainers" id="btnshowctnr">
          <label class="filter-btn" :class="{ 'active': showContainers }" for="btnshowctnr">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-1">
              <path d="M10 4H4c-1.11 0-2 .89-2 2v3h2V6h4V4zM20 6h-4V4h4c1.11 0 2 .89 2 2v3h-2V6zM4 16h4v4H4c-1.11 0-2-.89-2-2v-3h2v3zm16 0v3c0 1.11-.89 2-2 2h-4v-2h4v-3h2z"/>
            </svg>
            {{ __('Containers') }}
          </label>
        </div>

        <div class="btn-group" role="group">
          <input type="radio" class="btn-check" name="tab" value="0" x-model="tab" id="btnactorview">
          <label class="filter-btn" :class="{ 'active': tab == 0 }" for="btnactorview">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-1">
              <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
            {{ __('Actor View') }}
          </label>
          <input type="radio" class="btn-check" name="tab" value="1" x-model="tab" id="btnstatusview">
          <label class="filter-btn" :class="{ 'active': tab == 1 }" for="btnstatusview">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-1">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            {{ __('Status View') }}
          </label>
        </div>

        @can('readonly')
        <div class="btn-group" role="group">
          <input type="checkbox" class="btn-check" name="responsible" x-model="showMine" id="btnshowmine">
          <label class="filter-btn" :class="{ 'active': showMine }" for="btnshowmine">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-1">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
            </svg>
            {{ __('Mine') }}
          </label>
        </div>
        @endcan

        <div class="btn-group" role="group">
          <input type="checkbox" class="btn-check" name="include_dead" x-model="includeDead" id="btnincludedead">
          <label class="filter-btn" :class="{ 'active': includeDead }" for="btnincludedead">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-1">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            </svg>
            {{ __('Include Dead') }}
          </label>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <button id="exportList" type="button" class="export-btn">
          <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-2">
            <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
          </svg>
          {{ __('Export') }}
        </button>
        <button id="clearFilters" type="button" class="clear-btn" onclick="window.location.href = '/matter'">
          <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-2">
            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
          </svg>
          {{ __('Clear filters') }}
        </button>
      </div>

      <input type="hidden" name="display_with" value="{{ Request::get('display_with') }}">
    </div>

    <!-- Table with Filters -->
    <div class="card-body p-0">
      <table class="matter-table w-100">
        <thead>
          <tr id="filterFields">
            <th width="10%">
              <div class="d-flex align-items-center">
                <input class="filter-input flex-grow-1" name="Ref" placeholder="{{ __('Ref') }}" value="{{ Request::get('Ref') }}">
                <button class="sort-btn {{ Request::get('sortkey') == 'caseref' ? 'active' : '' }}"
                        type="button" data-sortkey="caseref" data-sortdir="desc" title="Sort">
                  <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7 10l5 5 5-5z"/>
                  </svg>
                </button>
              </div>
            </th>
            <th width="5%">
              <input class="filter-input text-center" name="Cat" placeholder="{{ __('Cat') }}" value="{{ Request::get('Cat') }}">
            </th>
            <th width="12%">
              <div class="d-flex align-items-center">
                <input class="filter-input flex-grow-1" name="Status" placeholder="{{ __('Status') }}" value="{{ Request::get('Status') }}">
                <button class="sort-btn {{ Request::get('sortkey') == 'event_name.name' ? 'active' : '' }}"
                        type="button" data-sortkey="event_name.name" data-sortdir="asc" title="Sort">
                  <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7 10l5 5 5-5z"/>
                  </svg>
                </button>
              </div>
            </th>
            @can('readonly')
            <th width="15%" class="tab0" :class="{ 'd-none': tab == 1 }">
              <div class="d-flex align-items-center">
                <input class="filter-input flex-grow-1" name="Client" placeholder="{{ __('Client') }}" value="{{ Request::get('Client') }}">
                <button class="sort-btn {{ Request::get('sortkey') == 'cli.name' ? 'active' : '' }}"
                        type="button" data-sortkey="cli.name" data-sortdir="asc" title="Sort">
                  <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7 10l5 5 5-5z"/>
                  </svg>
                </button>
              </div>
            </th>
            @endcan
            <th width="8%" class="tab0" :class="{ 'd-none': tab == 1 }">
              <input class="filter-input text-center" name="ClRef" placeholder="{{ __('Cl. Ref') }}" value="{{ Request::get('ClRef') }}">
            </th>
            <th width="12%" class="tab0" :class="{ 'd-none': tab == 1 }">
              <div class="d-flex align-items-center">
                <input class="filter-input flex-grow-1" name="Applicant" placeholder="{{ __('Applicant') }}" value="{{ Request::get('Applicant') }}">
                <button class="sort-btn {{ Request::get('sortkey') == 'app.name' ? 'active' : '' }}"
                        type="button" data-sortkey="app.name" data-sortdir="asc" title="Sort">
                  <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7 10l5 5 5-5z"/>
                  </svg>
                </button>
              </div>
            </th>
            <th width="10%" class="tab0" :class="{ 'd-none': tab == 1 }">
              <input class="filter-input" name="Agent" placeholder="{{ __('Agent') }}" value="{{ Request::get('Agent') }}">
            </th>
            <th width="30%" class="tab0" :class="{ 'd-none': tab == 1 }">
              <input class="filter-input" name="Title" placeholder="{{ __('Title') }}" value="{{ Request::get('Title') }}">
            </th>
            <th width="10%" class="tab1" :class="{ 'd-none': tab == 0 }">
              <div class="d-flex align-items-center">
                <input class="filter-input flex-grow-1" name="Status_date" placeholder="{{ __('Date') }}" value="{{ Request::get('Status_date') }}">
                <button class="sort-btn {{ Request::get('sortkey') == 'status.event_date' ? 'active' : '' }}"
                        type="button" data-sortkey="status.event_date" data-sortdir="asc" title="Sort">
                  <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7 10l5 5 5-5z"/>
                  </svg>
                </button>
              </div>
            </th>
            <th width="8%" class="tab1" :class="{ 'd-none': tab == 0 }">
              <input class="filter-input text-center" name="Filed" placeholder="{{ __('Filed') }}" value="{{ Request::get('Filed') }}">
            </th>
            <th width="8%" class="tab1" :class="{ 'd-none': tab == 0 }">
              <input class="filter-input text-center" name="Published" placeholder="{{ __('Published') }}" value="{{ Request::get('Published') }}">
            </th>
            <th width="8%" class="tab1" :class="{ 'd-none': tab == 0 }">
              <input class="filter-input text-center" name="Granted" placeholder="{{ __('Granted') }}" value="{{ Request::get('Granted') }}">
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
          <tr {!! $matter->dead ? 'class="dead-matter"' : ($matter->container_id ? '' : 'class="table-info"') !!}>
            <td>
              <div class="d-flex align-items-center">
                <a href="/matter/{{ $matter->id }}" target="_blank" class="matter-link">
                  {{ $matter->Ref }}
                </a>
                @if ($matter->container_id)
                  <span class="container-indicator">{{ __('Container') }}</span>
                @endif
              </div>
            </td>
            <td>
              <span class="status-badge status-active">{{ $matter->Cat }}</span>
            </td>
            <td>
              @if ($published)
                <a href="http://worldwide.espacenet.com/publicationDetails/biblio?DB=EPODOC&CC={{ $CC }}&NR={{ $pubno }}"
                   target="_blank" class="matter-link" title="Open in Espacenet">
                  {{ $matter->Status }}
                </a>
              @else
                <span class="status-badge status-pending">{{ $matter->Status }}</span>
              @endif
            </td>
            @can('readonly')
            <td class="tab0" :class="{ 'd-none': tab == 1 }">
              <span class="text-truncate d-block">{{ $matter->Client }}</span>
            </td>
            @endcan
            <td class="tab0" :class="{ 'd-none': tab == 1 }">
              <small class="text-tertiary">{{ $matter->ClRef }}</small>
            </td>
            <td class="tab0" :class="{ 'd-none': tab == 1 }">
              <span class="text-truncate d-block">{{ $matter->Applicant }}</span>
            </td>
            <td class="tab0" :class="{ 'd-none': tab == 1 }">
              <small class="text-tertiary">{{ $matter->Agent }}</small>
            </td>
            <td class="tab0" :class="{ 'd-none': tab == 1 }">
              <div class="text-truncate" title="{{ $matter->container_id && $matter->Title2 ? $matter->Title2 : $matter->Title }}">
                {{ $matter->container_id && $matter->Title2 ? $matter->Title2 : $matter->Title }}
              </div>
            </td>
            <td class="tab1" :class="{ 'd-none': tab == 0 }">
              <span class="status-badge {{ $matter->Status_date ? 'status-active' : 'status-pending' }}">
                {{ $matter->Status_date }}
              </span>
            </td>
            <td class="tab1" :class="{ 'd-none': tab == 0 }">
              <span class="status-badge status-active">{{ $matter->Filed }}</span>
            </td>
            <td class="tab1" :class="{ 'd-none': tab == 0 }">
              <span class="status-badge {{ $matter->Published ? 'status-active' : 'status-pending' }}">
                {{ $matter->Published }}
              </span>
            </td>
            <td class="tab1" :class="{ 'd-none': tab == 0 }">
              <span class="status-badge {{ $matter->Granted ? 'status-active' : 'status-inactive' }}">
                {{ $matter->Granted }}
              </span>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="p-4 border-top">
        {{ $matters->links() }}
      </div>
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

  // Enhanced filter button interactions
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      // Add ripple effect
      const ripple = document.createElement('span');
      ripple.style.position = 'absolute';
      ripple.style.width = '20px';
      ripple.style.height = '20px';
      ripple.style.background = 'rgba(255, 255, 255, 0.3)';
      ripple.style.borderRadius = '50%';
      ripple.style.transform = 'translate(-50%, -50%)';
      ripple.style.pointerEvents = 'none';
      ripple.style.animation = 'ripple 0.6s ease-out';

      const rect = this.getBoundingClientRect();
      ripple.style.left = `${e.clientX - rect.left}px`;
      ripple.style.top = `${e.clientY - rect.top}px`;

      this.style.position = 'relative';
      this.style.overflow = 'hidden';
      this.appendChild(ripple);

      setTimeout(() => ripple.remove(), 600);
    });
  });

  // Enhanced sorting buttons
  document.querySelectorAll('.sort-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      // Add visual feedback
      this.style.transform = 'scale(0.95)';
      setTimeout(() => {
        this.style.transform = 'scale(1)';
      }, 150);
    });
  });
});
</script>
@endpush
