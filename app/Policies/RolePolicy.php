<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Traits\HasPolicyAuthorization;

/**
 * Authorization policy for Role model (actor_role table).
 *
 * Actor roles define the relationship types between actors and matters:
 * - Admin (DBA) can manage all roles
 * - Read-write users (DBRW) can view roles
 * - Read-only users (DBRO) can view roles
 * - Clients (CLI) cannot access role management
 */
class RolePolicy
{
    use HasPolicyAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canRead($user);
    }

    public function view(User $user, Role $role): bool
    {
        return $this->canRead($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Role $role): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->isAdmin($user);
    }
}
