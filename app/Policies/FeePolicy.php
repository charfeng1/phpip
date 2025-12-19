<?php

namespace App\Policies;

use App\Models\Fee;
use App\Models\User;

/**
 * Authorization policy for Fee model.
 *
 * Fee schedules are sensitive billing data:
 * - Admin (DBA) can manage all fees
 * - Read-write users (DBRW) can view fees
 * - Read-only users (DBRO) can view fees
 * - Clients (CLI) cannot access fee schedules
 */
class FeePolicy
{
    protected function isAdmin(User $user): bool
    {
        return $user->default_role === 'DBA';
    }

    protected function canRead(User $user): bool
    {
        return in_array($user->default_role, ['DBA', 'DBRW', 'DBRO'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->canRead($user);
    }

    public function view(User $user, Fee $fee): bool
    {
        return $this->canRead($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Fee $fee): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Fee $fee): bool
    {
        return $this->isAdmin($user);
    }
}
