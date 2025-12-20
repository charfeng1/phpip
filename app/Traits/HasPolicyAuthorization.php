<?php

namespace App\Traits;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Trait for common policy authorization methods.
 *
 * Reduces boilerplate in policy classes by providing standard
 * authorization check methods for user roles.
 */
trait HasPolicyAuthorization
{
    /**
     * Check if the user is an admin (DBA role).
     */
    protected function isAdmin(User $user): bool
    {
        return $user->default_role === UserRole::ADMIN->value;
    }

    /**
     * Check if the user can read data (DBA, DBRW, or DBRO roles).
     */
    protected function canRead(User $user): bool
    {
        return in_array($user->default_role, UserRole::readableRoleValues(), true);
    }

    /**
     * Check if the user can write data (DBA or DBRW roles).
     */
    protected function canWrite(User $user): bool
    {
        return in_array($user->default_role, UserRole::writableRoleValues(), true);
    }

    /**
     * Check if the user is a client (CLI role or no role).
     */
    protected function isClient(User $user): bool
    {
        return $user->default_role === UserRole::CLIENT->value || empty($user->default_role);
    }

    /**
     * Check if the user is an internal user (not a client).
     */
    protected function isInternalUser(User $user): bool
    {
        return ! $this->isClient($user);
    }
}
