<?php

namespace App\Policies;

use App\Models\Matter;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
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
