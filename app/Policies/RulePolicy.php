<?php

namespace App\Policies;

use App\Models\Rule;
use App\Models\User;
use App\Traits\HasPolicyAuthorization;

/**
 * Authorization policy for Rule model.
 *
 * Rules define deadline calculation logic - critical system configuration:
 * - Admin (DBA) can manage all rules
 * - Read-write users (DBRW) can view rules
 * - Read-only users (DBRO) can view rules
 * - Clients (CLI) cannot access rules
 */
class RulePolicy
{
    use HasPolicyAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canRead($user);
    }

    public function view(User $user, Rule $rule): bool
    {
        return $this->canRead($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Rule $rule): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Rule $rule): bool
    {
        return $this->isAdmin($user);
    }
}
