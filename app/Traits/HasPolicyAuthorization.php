<?php

namespace App\Traits;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Trait for common policy authorization methods.
 *
 * Reduces boilerplate in policy classes by providing standard
 * authorization check methods for user roles.
 *
 * Role Hierarchy:
 * - ADMIN (DBA): Full access to all features
 * - READ_WRITE (DBRW): Can view and modify data
 * - READ_ONLY (DBRO): Can only view data
 * - CLIENT (CLI): External client with limited access to their own matters
 *
 * Important: Users with null or empty default_role are treated as clients.
 * This is a security-conscious default - unassigned users get minimal permissions
 * rather than elevated access.
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
     *
     * Note: Users with null or empty default_role are treated as clients.
     * This ensures unassigned users have minimal permissions by default,
     * following the principle of least privilege.
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
