<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Traits\Filterable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * AuditLogController
 *
 * Manages the audit trail viewing functionality for compliance and dispute resolution.
 * Provides read-only access to audit logs with filtering and search capabilities.
 *
 * Features:
 * - View all audit logs with pagination
 * - Filter by model type, user, action, and date range
 * - View detailed change history for specific records
 * - Export audit logs for reporting
 */
class AuditLogController extends Controller
{
    use Filterable;

    /**
     * Filter rules for index and export methods.
     */
    protected array $filterRules = [];

    public function __construct()
    {
        $this->filterRules = [
            'model' => function ($q, $v) {
                $modelClass = $this->resolveModelClass($v);
                if ($modelClass) {
                    $q->where('auditable_type', $modelClass);
                }
            },
            'user' => function ($q, $v) {
                // Escape LIKE wildcards to prevent SQL wildcard injection
                $userSearch = str_replace(['%', '_'], ['\\%', '\\_'], $v);
                $q->where('user_login', 'like', $userSearch.'%');
            },
            'action' => fn ($q, $v) => $q->where('action', $v),
            'from_date' => fn ($q, $v) => $q->where('created_at', '>=', $v),
            'to_date' => fn ($q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfDay()),
            'record_id' => fn ($q, $v) => $q->where('auditable_id', $v),
        ];
    }

    /**
     * Display a paginated list of audit logs with filtering.
     *
     * @param  Request  $request  Filter parameters
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Only admins can view audit logs
        Gate::authorize('admin');

        // Validate input
        $request->validate([
            'model' => 'nullable|string|max:255',
            'user' => 'nullable|string|max:255',
            'action' => 'nullable|in:created,updated,deleted',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'record_id' => 'nullable|integer|min:1',
        ]);

        $query = AuditLog::query()
            ->with(['auditable', 'user'])
            ->orderByDesc('created_at');

        $this->applyFilters($query, $request);

        $auditLogs = $query->paginate(50)->withQueryString();

        // Get available model types for filter dropdown
        $modelTypes = $this->getAuditableModels();

        // Get available actions for filter dropdown
        $actions = ['created', 'updated', 'deleted'];

        if ($request->wantsJson()) {
            return response()->json($auditLogs);
        }

        return view('audit.index', compact('auditLogs', 'modelTypes', 'actions'));
    }

    /**
     * Display the audit history for a specific record.
     *
     * @param  string  $type  Model type (e.g., 'matter', 'event')
     * @param  int  $id  Record ID
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $type, int $id)
    {
        // Only admins can view audit logs
        Gate::authorize('admin');

        $modelClass = $this->resolveModelClass($type);

        if (! $modelClass) {
            abort(404, 'Invalid model type');
        }

        $auditLogs = AuditLog::where('auditable_type', $modelClass)
            ->where('auditable_id', $id)
            ->with(['auditable', 'user'])
            ->orderByDesc('created_at')
            ->paginate(50);

        // Try to get the current record
        $record = null;
        if (class_exists($modelClass)) {
            $record = $modelClass::find($id);
        }

        $modelName = class_basename($modelClass);

        if ($request->wantsJson()) {
            return response()->json([
                'record' => $record,
                'audit_logs' => $auditLogs,
            ]);
        }

        return view('audit.show', compact('auditLogs', 'record', 'modelName', 'id'));
    }

    /**
     * Display detailed view of a single audit log entry.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function detail(Request $request, AuditLog $auditLog)
    {
        // Only admins can view audit logs
        Gate::authorize('admin');

        if ($request->wantsJson()) {
            return response()->json($auditLog);
        }

        return view('audit.detail', compact('auditLog'));
    }

    /**
     * Export audit logs as CSV.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        // Only admins can export audit logs
        Gate::authorize('admin');

        // Validate input (same as index method)
        $request->validate([
            'model' => 'nullable|string|max:255',
            'user' => 'nullable|string|max:255',
            'action' => 'nullable|in:created,updated,deleted',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'record_id' => 'nullable|integer|min:1',
        ]);

        $query = AuditLog::query()
            ->with(['auditable', 'user'])
            ->orderByDesc('created_at');

        $this->applyFilters($query, $request);

        $filename = 'audit_log_'.date('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Write CSV header
            fputcsv($handle, [
                'ID',
                'Date/Time',
                'User',
                'Action',
                'Model',
                'Record ID',
                'Changed Fields',
                'IP Address',
            ]);

            // Write data rows
            $query->chunk(1000, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->user_login,
                        $log->action,
                        $log->model_name,
                        $log->auditable_id,
                        $log->change_summary,
                        $log->ip_address,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Resolve a short model name to its full class name.
     */
    protected function resolveModelClass(string $type): ?string
    {
        $models = [
            'matter' => \App\Models\Matter::class,
            'event' => \App\Models\Event::class,
            'task' => \App\Models\Task::class,
            'actor' => \App\Models\Actor::class,
            'classifier' => \App\Models\Classifier::class,
        ];

        $type = strtolower($type);

        // Check if it's a short name
        if (isset($models[$type])) {
            return $models[$type];
        }

        // Check if it's already a full class name
        if (class_exists($type)) {
            return $type;
        }

        // Try to construct the full class name
        $fullClass = 'App\\Models\\'.ucfirst($type);
        if (class_exists($fullClass)) {
            return $fullClass;
        }

        return null;
    }

    /**
     * Get a list of auditable models for the filter dropdown.
     */
    protected function getAuditableModels(): array
    {
        return [
            'matter' => 'Matter',
            'event' => 'Event',
            'task' => 'Task',
            'actor' => 'Actor',
            'classifier' => 'Classifier',
        ];
    }
}
