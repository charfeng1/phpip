<?php

namespace App\Policies;

use App\Models\MatterType;
use App\Models\User;
use App\Traits\HasPolicyAuthorization;

/**
 * Authorization policy for MatterType model.
 *
 * Matter types define the classification of matters:
 * - Admin (DBA) can manage all matter types
 * - Read-write users (DBRW) can view matter types
 * - Read-only users (DBRO) can view matter types
 * - Clients (CLI) cannot access matter type management
 */
class MatterTypePolicy
{
    use HasPolicyAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canRead($user);
    }

    public function view(User $user, MatterType $matterType): bool
    {
        return $this->canRead($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, MatterType $matterType): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, MatterType $matterType): bool
    {
        return $this->isAdmin($user);
    }
}
