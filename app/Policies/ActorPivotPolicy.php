<?php

namespace App\Policies;

use App\Models\ActorPivot;
use App\Models\User;
use App\Traits\HasPolicyAuthorization;

/**
 * Authorization policy for ActorPivot model.
 *
 * ActorPivot manages the relationship between actors and matters.
 * Authorization is primarily based on the parent Matter:
 * - Admin (DBA) can manage all actor-matter relationships
 * - Read-write users (DBRW) can manage relationships for matters they have access to
 * - Read-only users (DBRO) can view relationships
 * - Clients (CLI) can view their own matter relationships
 */
class ActorPivotPolicy
{
    use HasPolicyAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canRead($user);
    }

    public function view(User $user, ActorPivot $actorPivot): bool
    {
        return $this->canRead($user);
    }

    public function create(User $user): bool
    {
        return $this->canWrite($user);
    }

    public function update(User $user, ActorPivot $actorPivot): bool
    {
        return $this->canWrite($user);
    }

    public function delete(User $user, ActorPivot $actorPivot): bool
    {
        return $this->canWrite($user);
    }
}
