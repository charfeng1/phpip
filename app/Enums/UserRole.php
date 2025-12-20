<?php

namespace App\Enums;

/**
 * User roles for access control in the application.
 *
 * These roles determine what operations a user can perform:
 * - ADMIN (DBA): Full access to all features
 * - READ_WRITE (DBRW): Can view and modify data
 * - READ_ONLY (DBRO): Can only view data
 * - CLIENT (CLI): External client with limited access to their own matters
 */
enum UserRole: string
{
    case ADMIN = 'DBA';
    case READ_WRITE = 'DBRW';
    case READ_ONLY = 'DBRO';
    case CLIENT = 'CLI';

    /**
     * Get all internal (non-client) roles.
     *
     * @return array<UserRole>
     */
    public static function internalRoles(): array
    {
        return [self::ADMIN, self::READ_WRITE, self::READ_ONLY];
    }

    /**
     * Get all internal role values as strings.
     *
     * @return array<string>
     */
    public static function internalRoleValues(): array
    {
        return array_map(fn (UserRole $role) => $role->value, self::internalRoles());
    }

    /**
     * Get roles that can read data.
     *
     * @return array<UserRole>
     */
    public static function readableRoles(): array
    {
        return [self::ADMIN, self::READ_WRITE, self::READ_ONLY];
    }

    /**
     * Get role values that can read data.
     *
     * @return array<string>
     */
    public static function readableRoleValues(): array
    {
        return array_map(fn (UserRole $role) => $role->value, self::readableRoles());
    }

    /**
     * Get roles that can write data.
     *
     * @return array<UserRole>
     */
    public static function writableRoles(): array
    {
        return [self::ADMIN, self::READ_WRITE];
    }

    /**
     * Get role values that can write data.
     *
     * @return array<string>
     */
    public static function writableRoleValues(): array
    {
        return array_map(fn (UserRole $role) => $role->value, self::writableRoles());
    }

    /**
     * Check if this role is a client role.
     */
    public function isClient(): bool
    {
        return $this === self::CLIENT;
    }

    /**
     * Check if this role is an admin role.
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Check if this role can read data.
     */
    public function canRead(): bool
    {
        return in_array($this, self::readableRoles(), true);
    }

    /**
     * Check if this role can write data.
     */
    public function canWrite(): bool
    {
        return in_array($this, self::writableRoles(), true);
    }
}
