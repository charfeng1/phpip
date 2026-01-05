<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing team hierarchy traversal.
 *
 * Provides methods for traversing the user hierarchy tree to determine
 * team membership and supervisor relationships. Supports Partner → Senior → Junior
 * hierarchies where leaders can see all work for their direct and indirect reports.
 *
 * Uses the users.parent_id field for supervisor relationships.
 */
class TeamService
{
    /**
     * Cache duration in seconds for hierarchy queries.
     */
    protected const CACHE_TTL = 300; // 5 minutes

    /**
     * Get all subordinate user IDs for a given user (direct and indirect reports).
     *
     * Uses recursive CTE for database efficiency when available,
     * falls back to collection-based traversal otherwise.
     *
     * @param  int  $userId  The supervisor's user ID
     * @param  bool  $includeself  Whether to include the user themselves in results
     * @return Collection Collection of subordinate user IDs
     */
    public function getSubordinateIds(int $userId, bool $includeSelf = true): Collection
    {
        $cacheKey = "team_subordinates_{$userId}_{$includeSelf}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $includeSelf) {
            $subordinates = $this->getSubordinatesRecursive($userId);

            if ($includeSelf) {
                $subordinates->prepend($userId);
            }

            return $subordinates->unique()->values();
        });
    }

    /**
     * Get all subordinate logins for a given user (direct and indirect reports).
     *
     * @param  int  $userId  The supervisor's user ID
     * @param  bool  $includeSelf  Whether to include the user themselves in results
     * @return Collection Collection of subordinate login strings
     */
    public function getSubordinateLogins(int $userId, bool $includeSelf = true): Collection
    {
        $subordinateIds = $this->getSubordinateIds($userId, $includeSelf);

        return User::whereIn('id', $subordinateIds)
            ->whereNotNull('login')
            ->pluck('login');
    }

    /**
     * Get all supervisor IDs for a given user (direct and indirect managers).
     *
     * Traverses up the hierarchy from the user to the root using a recursive CTE.
     *
     * @param  int  $userId  The user's ID
     * @param  bool  $includeSelf  Whether to include the user themselves in results
     * @return Collection Collection of supervisor user IDs
     */
    public function getSupervisorIds(int $userId, bool $includeSelf = false): Collection
    {
        $cacheKey = "team_supervisors_{$userId}_{$includeSelf}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $includeSelf) {
            // Use recursive CTE for efficient single-query hierarchy traversal
            $sql = "
                WITH RECURSIVE supervisor_chain AS (
                    -- Base case: Start with the user's direct supervisor
                    SELECT parent_id AS id
                    FROM users
                    WHERE id = ?
                    AND parent_id IS NOT NULL

                    UNION ALL

                    -- Recursive case: Get each supervisor's supervisor
                    SELECT u.parent_id
                    FROM users u
                    INNER JOIN supervisor_chain sc ON u.id = sc.id
                    WHERE u.parent_id IS NOT NULL
                )
                SELECT DISTINCT id FROM supervisor_chain
            ";

            $supervisors = collect(DB::select($sql, [$userId]))->pluck('id');

            if ($includeSelf) {
                $supervisors->prepend($userId);
            }

            return $supervisors->unique()->values();
        });
    }

    /**
     * Check if a user is a supervisor (has any direct reports).
     *
     * @param  int  $userId  The user's ID
     * @return bool True if the user has any direct reports
     */
    public function isSupervisor(int $userId): bool
    {
        $cacheKey = "team_is_supervisor_{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            return User::where('parent_id', $userId)->exists();
        });
    }

    /**
     * Check if a user can view another user's work based on hierarchy.
     *
     * A user can view work if:
     * - They are the same user
     * - They are a supervisor (direct or indirect) of the target user
     *
     * @param  int  $viewerUserId  The user attempting to view
     * @param  int  $targetUserId  The user whose work is being viewed
     * @return bool True if the viewer can see the target's work
     */
    public function canViewUserWork(int $viewerUserId, int $targetUserId): bool
    {
        if ($viewerUserId === $targetUserId) {
            return true;
        }

        $subordinateIds = $this->getSubordinateIds($viewerUserId, false);

        return $subordinateIds->contains($targetUserId);
    }

    /**
     * Check if a user can view work by login based on hierarchy.
     *
     * @param  int  $viewerUserId  The user attempting to view
     * @param  string  $targetLogin  The login of the user whose work is being viewed
     * @return bool True if the viewer can see the target's work
     */
    public function canViewUserWorkByLogin(int $viewerUserId, string $targetLogin): bool
    {
        $targetUser = User::where('login', $targetLogin)->first();

        if (! $targetUser) {
            return false;
        }

        return $this->canViewUserWork($viewerUserId, $targetUser->id);
    }

    /**
     * Get direct reports for a user.
     *
     * @param  int  $userId  The supervisor's user ID
     * @return Collection Collection of User models that directly report to this user
     */
    public function getDirectReports(int $userId): Collection
    {
        return User::where('parent_id', $userId)
            ->whereNotNull('login')
            ->get();
    }

    /**
     * Get all team members (subordinates) as User models.
     *
     * @param  int  $userId  The supervisor's user ID
     * @param  bool  $includeSelf  Whether to include the user themselves in results
     * @return Collection Collection of User models
     */
    public function getTeamMembers(int $userId, bool $includeSelf = true): Collection
    {
        $subordinateIds = $this->getSubordinateIds($userId, $includeSelf);

        return User::whereIn('id', $subordinateIds)
            ->whereNotNull('login')
            ->get();
    }

    /**
     * Get the team hierarchy as a nested tree structure.
     *
     * @param  int  $userId  The root user's ID
     * @return array Nested array representing the team tree
     */
    public function getTeamTree(int $userId): array
    {
        $user = User::find($userId);

        if (! $user) {
            return [];
        }

        return $this->buildTreeNode($user);
    }

    /**
     * Clear all team-related caches for a user.
     *
     * Should be called when user hierarchy changes (parent_id updates).
     *
     * @param  int  $userId  The user whose caches should be cleared
     */
    public function clearCache(int $userId): void
    {
        // Clear subordinate caches
        Cache::forget("team_subordinates_{$userId}_true");
        Cache::forget("team_subordinates_{$userId}_false");

        // Clear supervisor caches
        Cache::forget("team_supervisors_{$userId}_true");
        Cache::forget("team_supervisors_{$userId}_false");

        // Clear is_supervisor cache
        Cache::forget("team_is_supervisor_{$userId}");

        // Also clear caches for all users in the hierarchy
        $this->clearHierarchyCaches($userId);
    }

    /**
     * Get all subordinate user IDs using a recursive CTE.
     *
     * Uses a single database query instead of N+1 recursive queries.
     *
     * @param  int  $userId  The supervisor's user ID
     * @return Collection Collection of subordinate user IDs
     */
    protected function getSubordinatesRecursive(int $userId): Collection
    {
        // Use recursive CTE for efficient single-query hierarchy traversal
        $sql = "
            WITH RECURSIVE subordinate_tree AS (
                -- Base case: Direct reports of the user
                SELECT id
                FROM users
                WHERE parent_id = ?

                UNION ALL

                -- Recursive case: Get each subordinate's direct reports
                SELECT u.id
                FROM users u
                INNER JOIN subordinate_tree st ON u.parent_id = st.id
            )
            SELECT DISTINCT id FROM subordinate_tree
        ";

        return collect(DB::select($sql, [$userId]))->pluck('id');
    }

    /**
     * Build a tree node for a user and their subordinates.
     *
     * @param  User  $user  The user to build the node for
     * @return array The tree node
     */
    protected function buildTreeNode(User $user): array
    {
        $node = [
            'id' => $user->id,
            'name' => $user->name,
            'login' => $user->login,
            'children' => [],
        ];

        $directReports = User::where('parent_id', $user->id)
            ->whereNotNull('login')
            ->get();

        foreach ($directReports as $report) {
            $node['children'][] = $this->buildTreeNode($report);
        }

        return $node;
    }

    /**
     * Clear caches for all users in a hierarchy chain.
     *
     * @param  int  $userId  Starting user ID
     */
    protected function clearHierarchyCaches(int $userId): void
    {
        // Clear caches for supervisors (they need to recalculate subordinates)
        $supervisors = $this->getSupervisorIds($userId, false);
        foreach ($supervisors as $supervisorId) {
            Cache::forget("team_subordinates_{$supervisorId}_true");
            Cache::forget("team_subordinates_{$supervisorId}_false");
            Cache::forget("team_is_supervisor_{$supervisorId}");
        }

        // Clear the old parent's cache if needed
        $user = User::find($userId);
        if ($user && $user->parent_id) {
            Cache::forget("team_subordinates_{$user->parent_id}_true");
            Cache::forget("team_subordinates_{$user->parent_id}_false");
            Cache::forget("team_is_supervisor_{$user->parent_id}");
        }
    }
}
