<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Task;
use App\Models\TemplateMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Service for filtering template members in document selection.
 *
 * Handles filtering of document templates by category, language, name,
 * summary, style, event name, and related event/task context.
 */
class DocumentFilterService
{
    /**
     * Allowed filter keys for security (whitelist approach).
     */
    private const ALLOWED_FILTER_KEYS = [
        'Category',
        'Language',
        'Name',
        'Summary',
        'Style',
        'EventName',
        'Event',
        'Task',
    ];

    /**
     * Filter template members based on provided criteria.
     *
     * @param mixed $query The query builder to filter (TemplateMember or Builder)
     * @param array $filters Filter key-value pairs
     * @return array{query: Builder, oldfilters: array<string, string>, view: string, event: ?Event, task: ?Task}
     */
    public function filterTemplateMembers(mixed $query, array $filters): array
    {
        $oldfilters = [];
        $view = 'documents.select';
        $event = null;
        $task = null;

        foreach ($filters as $key => $value) {
            // Skip null, empty, or whitespace-only values
            if ($value === '' || $value === null || (is_string($value) && trim($value) === '')) {
                continue;
            }

            // Skip unknown filter keys for security
            if (! in_array($key, self::ALLOWED_FILTER_KEYS, true)) {
                continue;
            }

            $result = $this->applyFilter($query, $key, $value);
            $query = $result['query'];
            $oldfilters = array_merge($oldfilters, $result['oldfilters']);

            // Update view and context based on filter type
            if ($key === 'EventName') {
                $view = 'documents.select2';
            } elseif ($key === 'Event') {
                $event = $result['event'];
            } elseif ($key === 'Task') {
                $task = $result['task'];
                $event = $task?->trigger;
            }
        }

        // Exclude members linked to any event or task for default view
        if ($view === 'documents.select') {
            $query = $query->whereHas('class', function ($q) {
                $q->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('event_class_lnk')
                        ->whereRaw('template_classes.id = event_class_lnk.template_class_id');
                });
            });
        }

        return [
            'query' => $query->orderBy('summary'),
            'oldfilters' => $oldfilters,
            'view' => $view,
            'event' => $event,
            'task' => $task,
        ];
    }

    /**
     * Apply a single filter to the query.
     *
     * @param mixed $query The query builder
     * @param string $key The filter key
     * @param mixed $value The filter value
     * @return array{query: Builder, oldfilters: array<string, string>, event: ?Event, task: ?Task}
     */
    protected function applyFilter(mixed $query, string $key, mixed $value): array
    {
        $event = null;
        $task = null;
        $oldfilters = [];

        switch ($key) {
            case 'Category':
                $query = $query->whereLike('category', "{$value}%");
                $oldfilters = ['Category' => $value];
                break;
            case 'Language':
                $query = $query->whereLike('language', "{$value}%");
                $oldfilters = ['Language' => $value];
                break;
            case 'Name':
                $query = $query->whereHas('class', fn ($q) => $q->whereLike('name', "{$value}%"));
                $oldfilters = ['Name' => $value];
                break;
            case 'Summary':
                $query = $query->whereLike('summary', "{$value}%");
                // Use 'Summary' key to avoid conflict with Name filter
                $oldfilters = ['Summary' => $value];
                break;
            case 'Style':
                $query = $query->whereLike('style', "{$value}%");
                $oldfilters = ['Style' => $value];
                break;
            case 'EventName':
                $query = $query->whereHas('class', function ($q) use ($value) {
                    $q->whereHas('eventNames', fn ($q2) => $q2->where('event_name_code', $value));
                });
                $oldfilters = ['EventName' => $value];
                break;
            case 'Event':
                $event = Event::find($value);
                // Query unchanged for Event filter - just used for context
                $oldfilters = [];
                break;
            case 'Task':
                $task = Task::find($value);
                // Query unchanged for Task filter - just used for context
                $oldfilters = [];
                break;
        }

        return [
            'query' => $query,
            'oldfilters' => $oldfilters,
            'event' => $event,
            'task' => $task,
        ];
    }
}
