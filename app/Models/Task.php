<?php

namespace App\Models;

use App\Enums\ActorRole;
use App\Enums\ClassifierType;
use App\Enums\EventCode;
use App\Enums\UserRole;
use App\Services\TeamService;
use App\Traits\Auditable;
use App\Traits\DatabaseJsonHelper;
use App\Traits\HasTranslationsExtended;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Task Model
 *
 * Represents reminders, deadlines, and renewals automatically generated from events
 * based on rules. Tasks are the primary mechanism for deadline management in phpIP.
 *
 * Database table: task
 *
 * Key relationships:
 * - Belongs to an event (trigger) that generated the task
 * - Belongs to a rule that defined the task generation logic
 * - Has access to matter through the trigger event
 * - Has event name info describing the task type
 *
 * Business logic:
 * - Tasks are automatically created by rules when events occur
 * - Tasks can be assigned to specific users or inherit matter responsibility
 * - Renewal tasks (REN) have special handling for fee calculations
 * - Tasks automatically touch (update timestamp of) their parent matter
 * - Task details are translatable (multi-language support)
 * - Open tasks exclude those from dead matters
 */
class Task extends Model
{
    use Auditable;
    use DatabaseJsonHelper;
    use HasFactory;
    use HasTranslationsExtended;

    /**
     * Attributes to exclude from audit logging.
     *
     * @var array<string>
     */
    protected $auditExclude = ['created_at', 'updated_at'];

    /**
     * The database table associated with the model.
     *
     * @var string
     */
    protected $table = 'task';

    /**
     * Attributes that should be hidden from serialization.
     *
     * @var array<string>
     */
    protected $hidden = ['creator', 'created_at', 'updated_at', 'updater'];

    /**
     * Attributes that are not mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Related models that should be touched when this model is updated.
     *
     * Updates the matter's timestamp when a task changes.
     *
     * @var array<string>
     */
    protected $touches = ['matter'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'done_date' => 'date:Y-m-d',
    ];

    /**
     * Attributes that support multi-language translations.
     *
     * @var array<string>
     */
    public $translatable = ['detail'];

    /**
     * Get the event name information for this task.
     *
     * Returns the EventName model containing description and classification.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function info()
    {
        return $this->belongsTo(EventName::class, 'code');
    }

    /**
     * Get the event that triggered (generated) this task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function trigger()
    {
        return $this->belongsTo(Event::class, 'trigger_id');
    }

    /**
     * Get the matter associated with this task.
     *
     * Uses a has-one-through relationship via the trigger event.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function matter()
    {
        return $this->hasOneThrough(Matter::class, Event::class, 'id', 'id', 'trigger_id', 'matter_id');
    }

    /**
     * Get the rule that was used to generate this task.
     *
     * Rules define the logic for automatic task creation from events.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rule(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Rule::class, 'rule_used', 'id');
    }

    /**
     * Get open task counts grouped by user.
     *
     * Returns a summary of undone tasks for each user/responsible, including:
     * - Number of open tasks per user
     * - Most urgent (earliest) due date per user
     * - User login/identifier
     *
     * Respects user role restrictions:
     * - Clients see only their own matters' tasks
     * - Can filter by assigned user (what_tasks=1), team (what_tasks=2), or client (what_tasks>2)
     * - Excludes tasks from dead matters
     *
     * @return \Illuminate\Support\Collection Collection of task counts by user
     */
    public static function getUsersOpenTaskCount()
    {
        $userid = Auth::user()->id;
        $role = Auth::user()->default_role;
        $what_tasks = request()->input('what_tasks');

        $query = static::with(['matter', 'matter.client'])
            ->where('done', 0)
            ->whereHas('matter', function (Builder $q) {
                $q->where('dead', 0);
            });

        // Apply filters based on what_tasks parameter
        if ($what_tasks == 1) {
            // My tasks - filter by assigned_to
            $query->where('assigned_to', Auth::user()->login);
        } elseif ($what_tasks == 2) {
            // Team tasks - filter by user and their subordinates
            $teamService = app(TeamService::class);
            $teamLogins = $teamService->getSubordinateLogins($userid, true);
            $query->where(function (Builder $q) use ($teamLogins) {
                $q->whereIn('assigned_to', $teamLogins)
                    ->orWhereHas('matter', function (Builder $mq) use ($teamLogins) {
                        $mq->whereIn('responsible', $teamLogins);
                    });
            });
        } elseif ($what_tasks > 2) {
            // Client tasks - filter by client ID
            $query->whereHas('matter.client', function ($q) use ($what_tasks) {
                $q->where('actor_id', $what_tasks);
            });
        }

        // Apply client role restrictions if needed
        if ($role == UserRole::CLIENT->value || empty($role)) {
            $query->whereHas('matter', function ($q) use ($userid) {
                $q->whereHas('client', function ($q2) use ($userid) {
                    $q2->where('actor_id', $userid);
                });
            });
        }

        // Select and group results
        return $query->select(
            DB::raw('count(*) as no_of_tasks'),
            DB::raw('MIN(due_date) as urgent_date'),
            DB::raw('COALESCE(assigned_to, (SELECT responsible FROM matter WHERE id = (SELECT matter_id FROM event WHERE id = task.trigger_id))) as login')
        )
            ->groupBy('login')
            ->get();
    }

    /**
     * Scope query to open tasks.
     *
     * Returns tasks that are not done and belong to matters that are not dead.
     * Eager loads event info, matter titles, and client for efficient querying.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function openTasks()
    {
        return $this->with(['info', 'matter.titles', 'matter.client'])
            ->where('done', 0)
            ->whereHas('matter', function (Builder $q) {
                $q->where('dead', 0);
            });
    }

    /**
     * Build a comprehensive query for renewal tasks with fees and matter details.
     *
     * This method delegates to TaskRepository for the actual query building.
     * Kept for backwards compatibility with existing code.
     *
     * @deprecated Use TaskRepository::renewals() instead for new code
     * @return \Illuminate\Database\Eloquent\Builder Query builder for renewal tasks
     */
    public static function renewals()
    {
        return app(\App\Repositories\TaskRepository::class)->renewals();
    }

    /**
     * Scope to filter tasks by team membership.
     *
     * Filters tasks to show only those where:
     * - The task is assigned to the user or their subordinates, OR
     * - The matter is assigned to the user or their subordinates
     *
     * @param  Builder  $query
     * @param  int|null  $userId  Optional user ID (defaults to authenticated user)
     * @return Builder
     */
    public function scopeForTeam(Builder $query, ?int $userId = null): Builder
    {
        $userId = $userId ?? Auth::id();

        if (! $userId) {
            return $query;
        }

        $teamService = app(TeamService::class);
        $teamLogins = $teamService->getSubordinateLogins($userId, true);

        return $query->where(function ($q) use ($teamLogins) {
            $q->whereIn('assigned_to', $teamLogins)
                ->orWhereHas('matter', function ($mq) use ($teamLogins) {
                    $mq->whereIn('responsible', $teamLogins);
                });
        });
    }

    /**
     * Scope to filter tasks by a specific user.
     *
     * @param  Builder  $query
     * @param  string  $login  The user login to filter by
     * @return Builder
     */
    public function scopeForUser(Builder $query, string $login): Builder
    {
        return $query->where(function ($q) use ($login) {
            $q->where('assigned_to', $login)
                ->orWhereHas('matter', function ($mq) use ($login) {
                    $mq->where('responsible', $login);
                });
        });
    }
}
