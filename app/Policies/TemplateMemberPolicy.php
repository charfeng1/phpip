<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\TemplateMember;
use App\Models\User;

/**
 * Authorization policy for TemplateMember model.
 *
 * Template members are individual document templates - system configuration:
 * - Admin (DBA) can manage template members
 * - Read-write users (DBRW) can view template members
 * - Read-only users (DBRO) can view template members
 * - Clients (CLI) cannot access template configuration
 */
class TemplateMemberPolicy
{
    protected function isAdmin(User $user): bool
    {
        return $user->default_role === UserRole::ADMIN->value;
    }

    protected function canRead(User $user): bool
    {
        return in_array($user->default_role, UserRole::readableRoleValues(), true);
    }

    public function viewAny(User $user): bool
    {
        return $this->canRead($user);
    }

    public function view(User $user, TemplateMember $templateMember): bool
    {
        return $this->canRead($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, TemplateMember $templateMember): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, TemplateMember $templateMember): bool
    {
        return $this->isAdmin($user);
    }
}
