<?php

namespace App\Policies;

use App\Models\Matter;
use App\Models\Task;
use App\Models\User;
use App\Services\TeamService;

class TaskPolicy
{
    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    protected function canRead(User $user): bool
    {
        return in_array($user->default_role, ['DBA', 'DBRW', 'DBRO'], true);
    }

    protected function canWrite(User $user): bool
    {
        return in_array($user->default_role, ['DBA', 'DBRW'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->canRead($user);
    }

    public function view(User $user, Task $task): bool
    {
        if ($this->canRead($user)) {
            return true;
        }

        if ($user->default_role === 'CLI' || empty($user->default_role)) {
            $matter = $task->matter()->first();
            if ($matter instanceof Matter) {
                $clientActor = optional($matter->clientFromLnk())->actor_id;

                return (int) $clientActor === (int) $user->id;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can view the task based on team hierarchy.
     *
     * Users can view tasks that are:
     * - Assigned to them
     * - Assigned to their direct/indirect reports
     * - On matters they or their reports are responsible for
     *
     * @return bool
     */
    public function viewAsTeamMember(User $user, Task $task): bool
    {
        // Check if task is assigned to user or their team
        $assignedTo = $task->assigned_to;
        if ($assignedTo && $this->teamService->canViewUserWorkByLogin($user->id, $assignedTo)) {
            return true;
        }

        // Check if matter is assigned to user or their team
        $matter = $task->matter()->first();
        if ($matter && $matter->responsible) {
            return $this->teamService->canViewUserWorkByLogin($user->id, $matter->responsible);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->canWrite($user);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->canWrite($user);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->canWrite($user);
    }
}
