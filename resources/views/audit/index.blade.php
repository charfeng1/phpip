@extends('layouts.app')

@section('content')
<legend class="alert alert-dark py-2 mb-1">
  {{ __('Audit Trail') }}
  <a href="{{ route('audit.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary float-end" title="{{ __('Export to CSV') }}">
    {{ __('Export') }}
  </a>
</legend>
<div class="row">
  <div class="col">
    <div class="card">
      <div class="card-header">
        <form method="GET" action="{{ route('audit.index') }}" class="row g-2 align-items-end">
          <div class="col-md-2">
            <label class="form-label small mb-0">{{ __('Model Type') }}</label>
            <select name="model" class="form-select form-select-sm">
              <option value="">{{ __('All Models') }}</option>
              @foreach($modelTypes as $key => $label)
                <option value="{{ $key }}" {{ request('model') == $key ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-0">{{ __('Action') }}</label>
            <select name="action" class="form-select form-select-sm">
              <option value="">{{ __('All Actions') }}</option>
              @foreach($actions as $action)
                <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>{{ ucfirst($action) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-0">{{ __('User') }}</label>
            <input type="text" name="user" class="form-control form-control-sm" value="{{ request('user') }}" placeholder="{{ __('User login') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-0">{{ __('Record ID') }}</label>
            <input type="number" name="record_id" class="form-control form-control-sm" value="{{ request('record_id') }}" placeholder="{{ __('ID') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-0">{{ __('From Date') }}</label>
            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-0">{{ __('To Date') }}</label>
            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
          </div>
          <div class="col-12 mt-2">
            <button type="submit" class="btn btn-sm btn-primary">{{ __('Filter') }}</button>
            <a href="{{ route('audit.index') }}" class="btn btn-sm btn-secondary">{{ __('Clear') }}</a>
          </div>
        </form>
      </div>
      <table class="table table-striped table-hover table-sm mb-0">
        <thead class="table-light">
          <tr>
            <th>{{ __('Date/Time') }}</th>
            <th>{{ __('User') }}</th>
            <th>{{ __('Action') }}</th>
            <th>{{ __('Model') }}</th>
            <th>{{ __('Record ID') }}</th>
            <th>{{ __('Changed Fields') }}</th>
            <th>{{ __('Details') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($auditLogs as $log)
            <tr>
              <td class="text-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
              <td>{{ $log->user_login ?? __('System') }}</td>
              <td>
                @if($log->action === 'created')
                  <span class="badge bg-success">{{ __('Created') }}</span>
                @elseif($log->action === 'updated')
                  <span class="badge bg-warning text-dark">{{ __('Updated') }}</span>
                @elseif($log->action === 'deleted')
                  <span class="badge bg-danger">{{ __('Deleted') }}</span>
                @else
                  <span class="badge bg-secondary">{{ $log->action }}</span>
                @endif
              </td>
              <td>{{ $log->model_name }}</td>
              <td>
                <a href="{{ route('audit.history', ['type' => strtolower($log->model_name), 'id' => $log->auditable_id]) }}">
                  {{ $log->auditable_id }}
                </a>
              </td>
              <td>
                <span title="{{ implode(', ', $log->changed_fields) }}">
                  {{ $log->change_summary }}
                </span>
              </td>
              <td>
                <a href="{{ route('audit.detail', $log) }}" class="btn btn-sm btn-outline-primary">
                  {{ __('View') }}
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                {{ __('No audit logs found.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
      <div class="card-footer">
        {{ $auditLogs->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
