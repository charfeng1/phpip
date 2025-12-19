<?php

namespace App\Policies;

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
        return in_array($user->default_role, ['DBA', 'DBRW', 'DBRO'], true);
    }

    protected function canWrite(User $user): bool
    {
        return in_array($user->default_role, ['DBA', 'DBRW'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->canRead($user) || $user->default_role === 'CLI' || empty($user->default_role);
    }

    public function view(User $user, RenewalsLog $renewalsLog): bool
    {
        if ($this->canRead($user)) {
            return true;
        }

        // Client users can view renewal logs for their own matters
        if ($user->default_role === 'CLI' || empty($user->default_role)) {
            $matter = $renewalsLog->matter()->first();
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
