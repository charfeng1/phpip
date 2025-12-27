<?php

namespace App\Traits;

use App\Services\TeamService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Provides team-based scoping for models.
 *
 * This trait provides query scopes to filter records by team membership,
 * supporting hierarchical team structures where users can see their own
 * records and those of their subordinates.
 *
 * Used by models that have a responsible user or are assigned to users:
 * - Matter: filters by 'responsible' column
 * - Task: filters by 'assigned_to' column and related matter's responsible
 */
trait HasTeamScopes
{
    /**
     * Get the column name used for user assignment.
     *
     * Override this method in models to specify a different column.
     * Default is 'responsible' for matters, but tasks use 'assigned_to'.
     *
     * @return string
     */
    protected function getTeamScopeColumn(): string
    {
        return 'responsible';
    }

    /**
     * Determine if this model should include matter relationship in team filtering.
     *
     * Override to return true for models like Task that should also
     * check the responsible user of related matters.
     *
     * @return bool
     */
    protected function shouldIncludeMatterInTeamScope(): bool
    {
        return false;
    }

    /**
     * Scope to filter records by team membership.
     *
     * Filters records to show only those where the assigned user is
     * the authenticated user or one of their direct/indirect reports.
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
        $column = $this->getTeamScopeColumn();

        if ($this->shouldIncludeMatterInTeamScope()) {
            return $query->where(function ($q) use ($teamLogins, $column) {
                $q->whereIn($column, $teamLogins)
                    ->orWhereHas('matter', function ($mq) use ($teamLogins) {
                        $mq->whereIn('responsible', $teamLogins);
                    });
            });
        }

        return $query->whereIn($column, $teamLogins);
    }

    /**
     * Scope to filter records by a specific user.
     *
     * @param  Builder  $query
     * @param  string  $login  The user login to filter by
     * @return Builder
     */
    public function scopeForUser(Builder $query, string $login): Builder
    {
        $column = $this->getTeamScopeColumn();

        if ($this->shouldIncludeMatterInTeamScope()) {
            return $query->where(function ($q) use ($login, $column) {
                $q->where($column, $login)
                    ->orWhereHas('matter', function ($mq) use ($login) {
                        $mq->where('responsible', $login);
                    });
            });
        }

        return $query->where($column, $login);
    }
}
