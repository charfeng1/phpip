<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

/**
 * Authorization policy for Category model.
 *
 * Categories define IP types (PAT, TM, DS) - critical system configuration:
 * - Admin (DBA) can manage categories
 * - Read-write users (DBRW) can view categories
 * - Read-only users (DBRO) can view categories
 * - Clients (CLI) cannot access category configuration
 */
class CategoryPolicy
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

    public function view(User $user, Category $category): bool
    {
        return $this->canRead($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->isAdmin($user);
    }
}
