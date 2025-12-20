<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Country;
use App\Models\User;

/**
 * Authorization policy for Country model.
 *
 * Country settings are reference data - critical system configuration:
 * - Admin (DBA) can manage country settings
 * - Read-write users (DBRW) can view countries
 * - Read-only users (DBRO) can view countries
 * - Clients (CLI) cannot access country configuration
 */
class CountryPolicy
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

    public function view(User $user, Country $country): bool
    {
        return $this->canRead($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Country $country): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Country $country): bool
    {
        return $this->isAdmin($user);
    }
}
