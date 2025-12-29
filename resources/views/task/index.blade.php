<div class="overflow-x-auto">
  <table class="table table-sm">
    <tbody>
      @foreach ($tasks as $task)
      <tr class="hover:bg-base-200/50 transition-colors">
        <td class="truncate py-1">
          <a href="/matter/{{ $task->matter->id }}/{{ $isrenewals ? 'renewals' : 'tasks' }}" data-modal-target="#ajaxModal" data-size="modal-lg" data-resource="/task/" title="{{ __('All tasks') }}" class="link link-hover">
            {{ $task->info->name }} {{ $task->detail }}
          </a>
        </td>
        <td class="w-24 py-1">
          <a href="/matter/{{ $task->matter->id }}" class="link link-primary">
            {{ $task->matter->uid }}
          </a>
        </td>
        <td class="truncate py-1 text-base-content/70">
          {{ optional($task->matter->titles->first())->value ?? "NO TITLE" }}
        </td>
        <td class="w-28 py-1 px-2">
          <div class="flex items-center gap-1">
            <span>{{ \Carbon\Carbon::parse($task->due_date)->isoFormat('L') }}</span>
            @if ($task->due_date < now())
            <span class="badge badge-error badge-xs" title="{{ __('Overdue') }}">&nbsp;</span>
            @elseif ($task->due_date < now()->addWeeks(2))
            <span class="badge badge-warning badge-xs" title="{{ __('Urgent') }}">&nbsp;</span>
            @endif
          </div>
        </td>
        @can('readwrite')
        <td class="w-12 py-1 px-3 text-center">
          <input id="{{ $task->id }}" class="checkbox checkbox-primary checkbox-sm clear-open-task" type="checkbox">
        </td>
        @endcan
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
<div class="px-4 py-2 border-t border-base-300">
  {{ $tasks->links() }}
</div>
