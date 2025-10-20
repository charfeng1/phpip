@extends('layouts.app')

@section('style')
<style type="text/css">
  .dashboard-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-2xl);
    box-shadow: var(--shadow-sm);
    transition: all var(--transition-base);
    overflow: hidden;
  }

  .dashboard-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
  }

  .dashboard-card .card-header {
    background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
    border-bottom: 1px solid var(--border-light);
    padding: var(--space-5) var(--space-6);
    border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
  }

  .category-table {
    border-collapse: separate;
    border-spacing: 0;
  }

  .category-table th {
    background: var(--bg-tertiary);
    padding: var(--space-3) var(--space-4);
    font-weight: var(--font-weight-semibold);
    font-size: var(--font-size-xs);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-tertiary);
    border-bottom: 1px solid var(--border-light);
  }

  .category-table td {
    padding: var(--space-3) var(--space-4);
    vertical-align: middle;
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
  }

  .category-table tr {
    transition: all var(--transition-fast);
  }

  .category-table tr:hover {
    background: var(--bg-hover);
  }

  .category-table tr:last-child td {
    border-bottom: none;
  }

  .task-item {
    padding: var(--space-4);
    background: var(--bg-primary);
    border-radius: var(--radius-lg);
    margin-bottom: var(--space-2);
    border: 1px solid var(--border-light);
    transition: all var(--transition-fast);
    cursor: pointer;
  }

  .task-item:hover {
    background: var(--bg-hover);
    transform: translateX(4px);
    box-shadow: var(--shadow-sm);
  }

  .task-item.urgent {
    border-left: 4px solid var(--color-danger);
  }

  .task-item.warning {
    border-left: 4px solid var(--color-warning);
  }

  .task-item.normal {
    border-left: 4px solid var(--color-success);
  }

  .btn-filter {
    border-radius: var(--radius-xl);
    padding: var(--space-2) var(--space-4);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    transition: all var(--transition-fast);
    border: 1px solid var(--border-medium);
    background: var(--bg-primary);
    color: var(--text-secondary);
  }

        .btn-check:checked + .btn-filter {
            background: var(--color-primary);
            color: var(--text-inverse);
            border-color: var(--color-primary);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
        }

        .btn-filter:hover:not(.active) {
            background: var(--bg-hover);
            color: var(--text-primary);
            border-color: var(--border-strong);
        }

  .stats-number {
    font-size: var(--font-size-3xl);
    font-weight: var(--font-weight-bold);
    color: var(--text-primary);
    line-height: 1;
  }

  .stats-label {
    font-size: var(--font-size-sm);
    color: var(--text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: var(--font-weight-semibold);
  }

  .empty-state {
    text-align: center;
    padding: var(--space-12);
    color: var(--text-tertiary);
  }

  .empty-state svg {
    width: 64px;
    height: 64px;
    margin-bottom: var(--space-4);
    opacity: 0.3;
  }

  .card-body {
    max-height: 400px;
    min-height: 100px;
    overflow-y: auto;
  }

  .card-body::-webkit-scrollbar {
    width: 6px;
  }

  .card-body::-webkit-scrollbar-track {
    background: transparent;
  }

  .card-body::-webkit-scrollbar-thumb {
    background: var(--color-gray-300);
    border-radius: var(--radius-full);

    &:hover {
      background: var(--color-gray-400);
    }
  }

  input[data-ac] {
    position: relative;
  }

  input[data-ac] + .dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1050;
    min-width: 200px;
    margin-top: 0.125rem;
    border: 1px solid var(--border-light) !important;
    border-radius: var(--radius-xl) !important;
    box-shadow: var(--shadow-floating) !important;
    backdrop-filter: blur(20px);
    background: var(--bg-primary) !important;
    padding: var(--space-2) !important;
  }

  @media (max-width: 768px) {
    .dashboard-card {
      margin-bottom: var(--space-4);
    }

    .stats-number {
      font-size: var(--font-size-2xl);
    }

    .btn-filter {
      font-size: var(--font-size-xs);
      padding: var(--space-2) var(--space-3);
    }
  }
</style>
@endsection

@section('content')

<div class="row g-4">
  <!-- Left Sidebar -->
  <div class="col-lg-4">
    <!-- Categories Card -->
    <div class="dashboard-card animate-fade-in">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-1 d-flex align-items-center">
            <svg width="20" height="20" fill="var(--color-primary)" viewBox="0 0 24 24" class="me-2">
              <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/>
            </svg>
            {{ __('Categories') }}
          </h5>
          <p class="text-tertiary mb-0 small">{{ __('Case classifications') }}</p>
        </div>
        @can('readwrite')
          <a href="/matter/create?operation=new" data-bs-target="#ajaxModal" data-bs-toggle="modal" data-size="modal-sm"
             class="btn btn-primary btn-sm" title="{{ __('Create Matter') }}">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
              <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            {{ __('New') }}
          </a>
        @endcan
      </div>
      <div class="card-body p-0" id="categoriesList">
        <table class="category-table w-100">
          <thead>
            <tr>
              <th width="40%">{{ __('Category') }}</th>
              <th width="20%" class="text-center">{{ __('Count') }}</th>
              <th width="40%"></th>
            </tr>
          </thead>
          <tbody>
            @foreach ($categories as $group)
            <tr class="reveal-hidden">
              <td>
                <div class="d-flex align-items-center">
                  <div class="category-dot me-2" style="width: 8px; height: 8px; border-radius: 50%; background: var(--color-primary);"></div>
                  <a href="/matter?Cat={{ $group->code }}" class="text-decoration-none fw-medium">
                    {{ $group->category }}
                  </a>
                </div>
              </td>
              <td class="text-center">
                <span class="badge bg-light text-primary">{{ $group->total }}</span>
              </td>
              <td class="text-end">
                @can('readwrite')
                  <a class="hidden-action btn btn-ghost btn-sm" href="/matter/create?operation=new&category={{$group->code}}"
                     data-bs-target="#ajaxModal" title="Create {{ $group->category }}" data-bs-toggle="modal" data-size="modal-sm">
                    <svg width="16" height="16" fill="var(--color-success)" viewBox="0 0 24 24">
                      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
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

    <!-- User Tasks Card -->
    <div class="dashboard-card mt-4 animate-fade-in" style="animation-delay: 0.1s;">
      <div class="card-header">
        <div>
          <h5 class="mb-1 d-flex align-items-center">
            <svg width="20" height="20" fill="var(--color-warning)" viewBox="0 0 24 24" class="me-2">
              <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm4.2 14.2L11 13V7h1.5v5.2l4.5 2.7-.8 1.3z"/>
            </svg>
            {{ __('Team Tasks') }}
          </h5>
          <p class="text-tertiary mb-0 small">{{ __('Pending assignments') }}</p>
        </div>
      </div>
      <div class="card-body p-0" id="usersTasksPanel">
        <table class="category-table w-100">
          <thead>
            <tr>
              <th width="40%">{{ __('User') }}</th>
              <th width="20%" class="text-center">{{ __('Open') }}</th>
              <th width="40%" class="text-center">{{ __('Urgent') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($taskscount as $group)
              @if ($group->no_of_tasks > 0)
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="user-avatar-small me-2" style="width: 24px; height: 24px; background: var(--color-secondary); color: white; border-radius: 50%; font-weight: 600; font-size: 10px; display: flex; align-items: center; justify-content: center;">
                      {{ strtoupper(substr($group->login, 0, 1)) }}
                    </div>
                    <a href="/home?user_dashboard={{ $group->login }}" class="text-decoration-none fw-medium">
                      {{ $group->login }}
                    </a>
                  </div>
                </td>
                <td class="text-center">
                  <span class="badge bg-primary">{{ $group->no_of_tasks }}</span>
                </td>
                <td class="text-center">
                  @if ($group->urgent_date < now())
                    <span class="badge bg-danger">{{ __('Overdue') }}</span>
                  @elseif ($group->urgent_date < now()->addWeeks(2))
                    <span class="badge bg-warning text-dark">{{ __('Soon') }}</span>
                  @else
                    <span class="badge bg-success">{{ \Carbon\Carbon::parse($group->urgent_date)->isoFormat('L') }}</span>
                  @endif
                </td>
              </tr>
              @endif
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mt-4 g-3">
      <div class="col-6">
        <div class="dashboard-card text-center p-4 animate-fade-in" style="animation-delay: 0.2s;">
          <div class="stats-number">{{ count($categories) }}</div>
          <div class="stats-label">{{ __('Categories') }}</div>
        </div>
      </div>
      <div class="col-6">
        <div class="dashboard-card text-center p-4 animate-fade-in" style="animation-delay: 0.3s;">
          <div class="stats-number">{{ collect($taskscount)->sum('no_of_tasks') }}</div>
          <div class="stats-label">{{ __('Total Tasks') }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content Area -->
  <div class="col-lg-8" id="filter">
    <!-- Open tasks Section -->
    <div class="dashboard-card mb-4 animate-slide-in-up">
      <div class="card-header">
        <div class="row align-items-center">
          <div class="col-auto">
            <h5 class="mb-0 d-flex align-items-center">
              <svg width="20" height="20" fill="var(--color-info)" viewBox="0 0 24 24" class="me-2">
                <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
              </svg>
              {{ __('Open tasks') }}
            </h5>
          </div>

          @can('readonly')
          <div class="col">
            <div class="d-flex gap-2 flex-wrap align-items-center">
              <div>
                <input type="radio" class="btn-check" name="what_tasks" id="alltasks" value="0" checked>
                <label class="btn-filter" for="alltasks">{{ __('Everyone') }}</label>
              </div>
              @if(!Request::filled('user_dashboard'))
                <div>
                  <input type="radio" class="btn-check" name="what_tasks" id="usertasks" value="1">
                  <label class="btn-filter" for="usertasks">{{ __('My Tasks') }}</label>
                </div>
              @endif
              <div>
                <input type="radio" class="btn-check" name="what_tasks" id="clientTasks" value="2">
                <label class="btn-filter" for="clientTasks">{{ __('Client') }}</label>
              </div>
              <div style="position: relative; flex: 1; min-width: 200px;">
                <input type="hidden" id="clientId" name="client_id">
                <input type="text" class="form-control" data-ac="/actor/autocomplete" data-actarget="client_id"
                       placeholder="{{ __('Select Client') }}" style="border-radius: var(--radius-xl);">
              </div>
            </div>
          </div>
          @endcan

          @can('readwrite')
          <div class="col-auto">
            <div class="input-group">
              <button class="btn btn-outline-primary btn-sm" type="button" id="clearOpenTasks">
                {{ __('Clear Selected') }}
              </button>
              <input type="text" class="form-control form-control-sm" name="datetaskcleardate" id="taskcleardate"
                     value="{{ now()->isoFormat('L') }}" style="max-width: 120px;">
            </div>
          </div>
          @endcan
        </div>
      </div>

      <div class="card-body p-4" id="tasklist">
        <div class="empty-state">
          <svg fill="currentColor" viewBox="0 0 24 24">
            <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1z"/>
          </svg>
          <h6 class="text-tertiary">{{ __('Loading tasks...') }}</h6>
          <p class="small">{{ __('Please wait while we fetch your tasks') }}</p>
        </div>
      </div>
    </div>

    <!-- Open Renewals Section -->
    <div class="dashboard-card animate-slide-in-up" style="animation-delay: 0.1s;">
      <div class="card-header">
        <div class="row align-items-center">
          <div class="col-auto">
            <h5 class="mb-0 d-flex align-items-center">
              <svg width="20" height="20" fill="var(--color-success)" viewBox="0 0 24 24" class="me-2">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1.81.45 1.61 1.67 1.61 1.16 0 1.6-.64 1.6-1.46 0-.84-.68-1.22-2.05-1.68-1.65-.54-3.43-1.31-3.43-3.43 0-1.61 1.13-2.85 2.93-3.21V5h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.63-1.63-1.63-1.01 0-1.46.54-1.46 1.34 0 .74.49 1.12 1.84 1.58 1.68.54 3.64 1.24 3.64 3.53.01 1.68-1.12 3.03-3.08 3.43z"/>
              </svg>
              {{ __('Renewals') }}
            </h5>
          </div>

          @can('readwrite')
          <div class="col-auto">
            <div class="input-group">
              <button class="btn btn-outline-success btn-sm" type="button" id="clearRenewals">
                {{ __('Clear Selected') }}
              </button>
              <input type="text" class="form-control form-control-sm" name="renewalcleardate" id="renewalcleardate"
                     value="{{ now()->isoFormat('L') }}" style="max-width: 120px;">
            </div>
          </div>
          @endcan
        </div>
      </div>

      <div class="card-body p-4" id="renewallist">
        <div class="empty-state">
          <svg fill="currentColor" viewBox="0 0 24 24">
            <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
          </svg>
          <h6 class="text-tertiary">{{ __('Loading renewals...') }}</h6>
          <p class="small">{{ __('Fetching upcoming renewal deadlines') }}</p>
        </div>
      </div>
    </div>
  </div>
</div>

@if (session('status'))
<div class="alert alert-success alert-dismissible fade show animate-slide-in-down" role="alert">
  <div class="d-flex align-items-center">
    <svg width="20" height="20" fill="currentColor" class="me-2">
      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
    </svg>
    {{ session('status') }}
  </div>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@endsection

@section('script')
<script>
// Add some enhanced JavaScript for the dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats numbers on scroll
            const animateNumbers = () => {
                const numbers = document.querySelectorAll('.stats-number');
                numbers.forEach(num => {
                    const target = parseInt(num.textContent);
      let current = 0;
      const increment = target / 50;
      const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
          current = target;
          clearInterval(timer);
        }
        num.textContent = Math.floor(current);
      }, 30);
    });
  };

  // Trigger animation when element is in viewport
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateNumbers();
        observer.disconnect();
      }
    });
  });

            const statsCards = document.querySelectorAll('.stats-number');
            if (statsCards.length > 0) {
                observer.observe(statsCards[0]);
            }

            // Enhanced filter button interactions
            document.querySelectorAll('.btn-filter').forEach(btn => {
                btn.addEventListener('click', function(event) {
                    const ripple = document.createElement('span');
                    ripple.style.position = 'absolute';
                    ripple.style.width = '20px';
                    ripple.style.height = '20px';
                    ripple.style.background = 'rgba(255, 255, 255, 0.5)';
                    ripple.style.borderRadius = '50%';
                    ripple.style.transform = 'translate(-50%, -50%)';
                    ripple.style.pointerEvents = 'none';
                    ripple.style.animation = 'ripple 0.6s ease-out';

                    const rect = this.getBoundingClientRect();
                    ripple.style.left = `${event.clientX - rect.left}px`;
                    ripple.style.top = `${event.clientY - rect.top}px`;

                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);

                    setTimeout(() => ripple.remove(), 600);
                });
            });
        });
</script>

<style>
@keyframes ripple {
  to {
    transform: translate(-50%, -50%) scale(4);
    opacity: 0;
  }
}
</style>
@endsection
