<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Matter;
use App\Models\RenewalsLog;
use App\Models\User;

/**
 * Authorization policy for RenewalsLog model.
 *
 * Renewal logs track payment processing:
 * - Admin (DBA) and read-write users (DBRW) can mark renewals as paid
 * - Read-only users (DBRO) can view renewal logs
 * - Clients (CLI) can view renewal logs for their own matters
 */
class RenewalPolicy
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
        // Only internal users can view lists of renewal logs
        // Clients must access individual renewal logs through view() which scopes by matter ownership
        return $this->canRead($user);
    }

    public function view(User $user, RenewalsLog $renewalsLog): bool
    {
        if ($this->canRead($user)) {
            return true;
        }

        // Client users can view renewal logs for their own matters
        if ($user->default_role === UserRole::CLIENT->value || empty($user->default_role)) {
            // Get matter through task relationship (renewals_logs has task_id, not matter_id)
            $matter = optional($renewalsLog->task)->matter;
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

    public function update(User $user, RenewalsLog $renewalsLog): bool
    {
        return $this->canWrite($user);
    }

    public function delete(User $user, RenewalsLog $renewalsLog): bool
    {
        return $this->canWrite($user);
    }
}
