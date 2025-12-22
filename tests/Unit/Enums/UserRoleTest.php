<?php

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    /** @test */
    public function enum_has_expected_values()
    {
        $this->assertEquals('DBA', UserRole::ADMIN->value);
        $this->assertEquals('DBRW', UserRole::READ_WRITE->value);
        $this->assertEquals('DBRO', UserRole::READ_ONLY->value);
        $this->assertEquals('CLI', UserRole::CLIENT->value);
    }

    /** @test */
    public function internal_roles_excludes_client()
    {
        $internalRoles = UserRole::internalRoles();

        $this->assertContains(UserRole::ADMIN, $internalRoles);
        $this->assertContains(UserRole::READ_WRITE, $internalRoles);
        $this->assertContains(UserRole::READ_ONLY, $internalRoles);
        $this->assertNotContains(UserRole::CLIENT, $internalRoles);
    }

    /** @test */
    public function internal_role_values_returns_strings()
    {
        $values = UserRole::internalRoleValues();

        $this->assertIsArray($values);
        $this->assertContains('DBA', $values);
        $this->assertContains('DBRW', $values);
        $this->assertContains('DBRO', $values);
        $this->assertNotContains('CLI', $values);
    }

    /** @test */
    public function readable_roles_includes_all_internal_users()
    {
        $readableRoles = UserRole::readableRoles();

        $this->assertContains(UserRole::ADMIN, $readableRoles);
        $this->assertContains(UserRole::READ_WRITE, $readableRoles);
        $this->assertContains(UserRole::READ_ONLY, $readableRoles);
    }

    /** @test */
    public function readable_role_values_returns_strings()
    {
        $values = UserRole::readableRoleValues();

        $this->assertIsArray($values);
        $this->assertContains('DBA', $values);
        $this->assertContains('DBRW', $values);
        $this->assertContains('DBRO', $values);
    }

    /** @test */
    public function writable_roles_only_includes_admin_and_read_write()
    {
        $writableRoles = UserRole::writableRoles();

        $this->assertContains(UserRole::ADMIN, $writableRoles);
        $this->assertContains(UserRole::READ_WRITE, $writableRoles);
        $this->assertNotContains(UserRole::READ_ONLY, $writableRoles);
        $this->assertNotContains(UserRole::CLIENT, $writableRoles);
    }

    /** @test */
    public function writable_role_values_returns_strings()
    {
        $values = UserRole::writableRoleValues();

        $this->assertIsArray($values);
        $this->assertContains('DBA', $values);
        $this->assertContains('DBRW', $values);
        $this->assertNotContains('DBRO', $values);
        $this->assertNotContains('CLI', $values);
    }

    /** @test */
    public function is_client_returns_true_only_for_client_role()
    {
        $this->assertTrue(UserRole::CLIENT->isClient());
        $this->assertFalse(UserRole::ADMIN->isClient());
        $this->assertFalse(UserRole::READ_WRITE->isClient());
        $this->assertFalse(UserRole::READ_ONLY->isClient());
    }

    /** @test */
    public function is_admin_returns_true_only_for_admin_role()
    {
        $this->assertTrue(UserRole::ADMIN->isAdmin());
        $this->assertFalse(UserRole::CLIENT->isAdmin());
        $this->assertFalse(UserRole::READ_WRITE->isAdmin());
        $this->assertFalse(UserRole::READ_ONLY->isAdmin());
    }

    /** @test */
    public function can_read_returns_true_for_internal_roles()
    {
        $this->assertTrue(UserRole::ADMIN->canRead());
        $this->assertTrue(UserRole::READ_WRITE->canRead());
        $this->assertTrue(UserRole::READ_ONLY->canRead());
        $this->assertFalse(UserRole::CLIENT->canRead());
    }

    /** @test */
    public function can_write_returns_true_only_for_admin_and_read_write()
    {
        $this->assertTrue(UserRole::ADMIN->canWrite());
        $this->assertTrue(UserRole::READ_WRITE->canWrite());
        $this->assertFalse(UserRole::READ_ONLY->canWrite());
        $this->assertFalse(UserRole::CLIENT->canWrite());
    }

    /** @test */
    public function all_cases_can_be_enumerated()
    {
        $cases = UserRole::cases();

        $this->assertCount(4, $cases);
    }
}
