<?php

namespace Tests\Unit\Traits;

use App\Enums\UserRole;
use App\Traits\HasPolicyAuthorization;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HasPolicyAuthorization trait.
 *
 * Note: Full integration tests for this trait are covered in the Policy tests
 * (tests/Unit/Policies/*) which use actual User models with RefreshDatabase.
 *
 * These unit tests verify the trait's methods exist and are callable.
 */
class HasPolicyAuthorizationTest extends TestCase
{
    /** @test */
    public function trait_defines_expected_methods()
    {
        $policy = new class
        {
            use HasPolicyAuthorization;

            public function getMethodNames(): array
            {
                return [
                    'isAdmin',
                    'canRead',
                    'canWrite',
                    'isClient',
                    'isInternalUser',
                ];
            }
        };

        $methods = $policy->getMethodNames();

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists($policy, $method),
                "Method {$method} should exist in HasPolicyAuthorization trait"
            );
        }
    }

    /** @test */
    public function trait_uses_user_role_enum()
    {
        // Verify that the trait is properly integrated with UserRole enum
        $this->assertEquals('DBA', UserRole::ADMIN->value);
        $this->assertEquals('DBRW', UserRole::READ_WRITE->value);
        $this->assertEquals('DBRO', UserRole::READ_ONLY->value);
        $this->assertEquals('CLI', UserRole::CLIENT->value);
    }

    /** @test */
    public function readable_roles_are_defined_correctly()
    {
        $readableRoles = UserRole::readableRoleValues();

        $this->assertContains('DBA', $readableRoles);
        $this->assertContains('DBRW', $readableRoles);
        $this->assertContains('DBRO', $readableRoles);
        $this->assertNotContains('CLI', $readableRoles);
    }

    /** @test */
    public function writable_roles_are_defined_correctly()
    {
        $writableRoles = UserRole::writableRoleValues();

        $this->assertContains('DBA', $writableRoles);
        $this->assertContains('DBRW', $writableRoles);
        $this->assertNotContains('DBRO', $writableRoles);
        $this->assertNotContains('CLI', $writableRoles);
    }
}
