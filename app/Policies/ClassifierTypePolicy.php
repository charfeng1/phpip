<?php

namespace App\Policies;

use App\Models\ClassifierType;
use App\Models\User;
use App\Traits\HasPolicyAuthorization;

/**
 * Authorization policy for ClassifierType model.
 *
 * Classifier types define the categories for classifiers:
 * - Admin (DBA) can manage all classifier types
 * - Read-write users (DBRW) can view classifier types
 * - Read-only users (DBRO) can view classifier types
 * - Clients (CLI) cannot access classifier type management
 */
class ClassifierTypePolicy
{
    use HasPolicyAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canRead($user);
    }

    public function view(User $user, ClassifierType $classifierType): bool
    {
        return $this->canRead($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, ClassifierType $classifierType): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, ClassifierType $classifierType): bool
    {
        return $this->isAdmin($user);
    }
}
