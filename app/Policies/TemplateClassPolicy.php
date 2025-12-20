<?php

namespace App\Policies;

use App\Models\TemplateClass;
use App\Models\User;
use App\Traits\HasPolicyAuthorization;

/**
 * Authorization policy for TemplateClass model.
 *
 * Template classes organize document templates - system configuration:
 * - Admin (DBA) can manage template classes
 * - Read-write users (DBRW) can view template classes
 * - Read-only users (DBRO) can view template classes
 * - Clients (CLI) cannot access template configuration
 */
class TemplateClassPolicy
{
    use HasPolicyAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canRead($user);
    }

    public function view(User $user, TemplateClass $templateClass): bool
    {
        return $this->canRead($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, TemplateClass $templateClass): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, TemplateClass $templateClass): bool
    {
        return $this->isAdmin($user);
    }
}
