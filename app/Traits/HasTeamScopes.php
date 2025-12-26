<?php

namespace App\Traits;

use App\Services\TeamService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Provides team-based query scopes for filtering models by user hierarchy.
 *
 * Models using this trait can filter records based on team membership,
 * where a "team" consists of the authenticated user and their subordinates.
 *
 * Usage:
 * - Implement getTeamFilterColumn() to specify which column to filter
 * - Optionally override applyTeamFilter() for custom filtering logic
 */
trait HasTeamScopes
{
    /**
     * Get the column name used for team filtering.
     *
     * Override this in your model to specify the appropriate column.
     * Default is 'responsible'.
     */
    protected function getTeamFilterColumn(): string
    {
        return 'responsible';
    }

    /**
     * Get the column name used for user filtering.
     *
     * Override this in your model if different from team filter column.
     */
    protected function getUserFilterColumn(): string
    {
        return $this->getTeamFilterColumn();
    }

    /**
     * Get team logins for the given user (including subordinates).
     *
     * @param  int|null  $userId  User ID (defaults to authenticated user)
     * @return array<string> Array of login strings
     */
    protected function getTeamLogins(?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();

        if (! $userId) {
            return [];
        }

        $teamService = app(TeamService::class);

        return $teamService->getSubordinateLogins($userId, true)->toArray();
    }

    /**
     * Scope to filter records by team membership.
     *
     * Filters to records where the responsible/assigned column belongs to
     * the authenticated user or one of their direct/indirect reports.
     *
     * @param  Builder  $query
     * @param  int|null  $userId  Optional user ID (defaults to authenticated user)
     * @return Builder
     */
    public function scopeForTeam(Builder $query, ?int $userId = null): Builder
    {
        $teamLogins = $this->getTeamLogins($userId);

        if (empty($teamLogins)) {
            return $query;
        }

        return $this->applyTeamFilter($query, $teamLogins);
    }

    /**
     * Apply the team filter to the query.
     *
     * Override this method for custom team filtering logic (e.g., Task model
     * which also checks the matter's responsible field).
     *
     * @param  Builder  $query
     * @param  array  $teamLogins  Array of login strings to filter by
     * @return Builder
     */
    protected function applyTeamFilter(Builder $query, array $teamLogins): Builder
    {
        return $query->whereIn($this->getTeamFilterColumn(), $teamLogins);
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
        return $this->applyUserFilter($query, $login);
    }

    /**
     * Apply the user filter to the query.
     *
     * Override this method for custom user filtering logic.
     *
     * @param  Builder  $query
     * @param  string  $login  The user login to filter by
     * @return Builder
     */
    protected function applyUserFilter(Builder $query, string $login): Builder
    {
        return $query->where($this->getUserFilterColumn(), $login);
    }
}
