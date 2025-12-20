@extends('layouts.app')

@section('content')
<legend class="alert alert-dark py-2 mb-1">
  {{ __('Audit History') }}: {{ $modelName }} #{{ $id }}
  <a href="{{ route('audit.index') }}" class="btn btn-sm btn-outline-secondary float-end">
    {{ __('Back to Audit Trail') }}
  </a>
</legend>
<div class="row">
  <div class="col">
    @if($record)
    <div class="card mb-3">
      <div class="card-header">
        <strong>{{ __('Current Record') }}</strong>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          @foreach($record->getAttributes() as $key => $value)
            @if(!in_array($key, ['password', 'remember_token']))
            <dt class="col-sm-3">{{ $key }}</dt>
            <dd class="col-sm-9">{{ is_array($value) ? json_encode($value) : $value }}</dd>
            @endif
          @endforeach
        </dl>
      </div>
    </div>
    @else
    <div class="alert alert-warning">
      {{ __('This record has been deleted.') }}
    </div>
    @endif

    <div class="card">
      <div class="card-header">
        <strong>{{ __('Change History') }}</strong>
        <span class="text-muted">({{ $auditLogs->total() }} {{ __('entries') }})</span>
      </div>
      <table class="table table-striped table-hover table-sm mb-0">
        <thead class="table-light">
          <tr>
            <th>{{ __('Date/Time') }}</th>
            <th>{{ __('User') }}</th>
            <th>{{ __('Action') }}</th>
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
              <td>{{ $log->change_summary }}</td>
              <td>
                <a href="{{ route('audit.detail', $log) }}" class="btn btn-sm btn-outline-primary">
                  {{ __('View') }}
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted py-4">
                {{ __('No audit history found for this record.') }}
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
