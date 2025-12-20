<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Classifier;
use App\Models\Matter;
use App\Models\User;

/**
 * Authorization policy for Classifier model.
 *
 * Classifiers are metadata attached to matters (titles, IPC codes, keywords):
 * - Admin (DBA) and read-write users (DBRW) can manage classifiers
 * - Read-only users (DBRO) can view classifiers
 * - Clients (CLI) can view classifiers for their own matters
 */
class ClassifierPolicy
{
    protected function canRead(User $user): bool
    {
        return in_array($user->default_role, UserRole::readableRoleValues(), true);
    }

    protected function canWrite(User $user): bool
    {
        return in_array($user->default_role, UserRole::writableRoleValues(), true);
    }

    public function viewAny(User $user): bool
    {
        return $this->canRead($user) || $user->default_role === UserRole::CLIENT->value || empty($user->default_role);
    }

    public function view(User $user, Classifier $classifier): bool
    {
        if ($this->canRead($user)) {
            return true;
        }

        // Client users can view classifiers for their own matters
        if ($user->default_role === UserRole::CLIENT->value || empty($user->default_role)) {
            $matter = $classifier->matter()->first();
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

    public function update(User $user, Classifier $classifier): bool
    {
        return $this->canWrite($user);
    }

    public function delete(User $user, Classifier $classifier): bool
    {
        return $this->canWrite($user);
    }
}
