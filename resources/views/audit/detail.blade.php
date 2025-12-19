@extends('layouts.app')

@section('content')
<legend class="alert alert-dark py-2 mb-1">
  {{ __('Audit Log Detail') }} #{{ $auditLog->id }}
  <div class="float-end">
    <a href="{{ route('audit.history', ['type' => strtolower($auditLog->model_name), 'id' => $auditLog->auditable_id]) }}" class="btn btn-sm btn-outline-secondary">
      {{ __('View Record History') }}
    </a>
    <a href="{{ route('audit.index') }}" class="btn btn-sm btn-outline-secondary">
      {{ __('Back to Audit Trail') }}
    </a>
  </div>
</legend>
<div class="row">
  <div class="col-md-4">
    <div class="card mb-3">
      <div class="card-header">
        <strong>{{ __('Overview') }}</strong>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">{{ __('Action') }}</dt>
          <dd class="col-sm-8">
            @if($auditLog->action === 'created')
              <span class="badge bg-success">{{ __('Created') }}</span>
            @elseif($auditLog->action === 'updated')
              <span class="badge bg-warning text-dark">{{ __('Updated') }}</span>
            @elseif($auditLog->action === 'deleted')
              <span class="badge bg-danger">{{ __('Deleted') }}</span>
            @else
              <span class="badge bg-secondary">{{ $auditLog->action }}</span>
            @endif
          </dd>

          <dt class="col-sm-4">{{ __('Model') }}</dt>
          <dd class="col-sm-8">{{ $auditLog->model_name }}</dd>

          <dt class="col-sm-4">{{ __('Record ID') }}</dt>
          <dd class="col-sm-8">{{ $auditLog->auditable_id }}</dd>

          <dt class="col-sm-4">{{ __('User') }}</dt>
          <dd class="col-sm-8">
            {{ $auditLog->user_login ?? __('System') }}
            @if($auditLog->user_name)
              <br><small class="text-muted">{{ $auditLog->user_name }}</small>
            @endif
          </dd>

          <dt class="col-sm-4">{{ __('Date/Time') }}</dt>
          <dd class="col-sm-8">{{ $auditLog->created_at->format('Y-m-d H:i:s') }}</dd>

          <dt class="col-sm-4">{{ __('IP Address') }}</dt>
          <dd class="col-sm-8">{{ $auditLog->ip_address ?? '-' }}</dd>

          <dt class="col-sm-4">{{ __('URL') }}</dt>
          <dd class="col-sm-8">
            <small class="text-break">{{ $auditLog->url ?? '-' }}</small>
          </dd>
        </dl>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    @if($auditLog->action === 'created')
      <div class="card mb-3">
        <div class="card-header bg-success text-white">
          <strong>{{ __('Created Values') }}</strong>
        </div>
        <div class="card-body">
          @if($auditLog->new_values)
            <table class="table table-sm table-bordered mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 30%">{{ __('Field') }}</th>
                  <th>{{ __('Value') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach($auditLog->new_values as $field => $value)
                  <tr>
                    <td><code>{{ $field }}</code></td>
                    <td>
                      @if(is_array($value))
                        <pre class="mb-0 small">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                      @else
                        {{ $value ?? '<null>' }}
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          @else
            <p class="text-muted mb-0">{{ __('No values recorded.') }}</p>
          @endif
        </div>
      </div>
    @elseif($auditLog->action === 'deleted')
      <div class="card mb-3">
        <div class="card-header bg-danger text-white">
          <strong>{{ __('Deleted Values') }}</strong>
        </div>
        <div class="card-body">
          @if($auditLog->old_values)
            <table class="table table-sm table-bordered mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 30%">{{ __('Field') }}</th>
                  <th>{{ __('Value') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach($auditLog->old_values as $field => $value)
                  <tr>
                    <td><code>{{ $field }}</code></td>
                    <td>
                      @if(is_array($value))
                        <pre class="mb-0 small">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                      @else
                        {{ $value ?? '<null>' }}
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          @else
            <p class="text-muted mb-0">{{ __('No values recorded.') }}</p>
          @endif
        </div>
      </div>
    @else
      <div class="card mb-3">
        <div class="card-header bg-warning">
          <strong>{{ __('Changes') }}</strong>
        </div>
        <div class="card-body">
          @if($auditLog->old_values || $auditLog->new_values)
            <table class="table table-sm table-bordered mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 20%">{{ __('Field') }}</th>
                  <th style="width: 40%">{{ __('Old Value') }}</th>
                  <th style="width: 40%">{{ __('New Value') }}</th>
                </tr>
              </thead>
              <tbody>
                @php
                  $allFields = array_unique(array_merge(
                    array_keys($auditLog->old_values ?? []),
                    array_keys($auditLog->new_values ?? [])
                  ));
                @endphp
                @foreach($allFields as $field)
                  @php
                    $oldValue = $auditLog->old_values[$field] ?? null;
                    $newValue = $auditLog->new_values[$field] ?? null;
                    $changed = $oldValue !== $newValue;
                  @endphp
                  <tr class="{{ $changed ? 'table-warning' : '' }}">
                    <td><code>{{ $field }}</code></td>
                    <td>
                      @if(is_array($oldValue))
                        <pre class="mb-0 small">{{ json_encode($oldValue, JSON_PRETTY_PRINT) }}</pre>
                      @else
                        {{ $oldValue ?? '<null>' }}
                      @endif
                    </td>
                    <td>
                      @if(is_array($newValue))
                        <pre class="mb-0 small">{{ json_encode($newValue, JSON_PRETTY_PRINT) }}</pre>
                      @else
                        {{ $newValue ?? '<null>' }}
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          @else
            <p class="text-muted mb-0">{{ __('No changes recorded.') }}</p>
          @endif
        </div>
      </div>
    @endif

    @if($auditLog->user_agent)
    <div class="card">
      <div class="card-header">
        <strong>{{ __('User Agent') }}</strong>
      </div>
      <div class="card-body">
        <small class="text-muted text-break">{{ $auditLog->user_agent }}</small>
      </div>
    </div>
    @endif
  </div>
</div>
@endsection
