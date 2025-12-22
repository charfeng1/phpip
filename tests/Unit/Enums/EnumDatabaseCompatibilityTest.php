<?php

namespace Tests\Unit\Enums;

use App\Enums\ActorRole;
use App\Enums\CategoryCode;
use App\Enums\ClassifierType;
use App\Enums\EventCode;
use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

/**
 * Tests to ensure enum values remain compatible with database seeder data.
 *
 * These tests serve as a safeguard against accidentally changing enum values
 * that would break compatibility with existing database records.
 */
class EnumDatabaseCompatibilityTest extends TestCase
{
    /**
     * Actor role codes that exist in the actor_role database table.
     * Source: database/seeders/ActorRoleTableSeeder.php
     */
    private const DATABASE_ACTOR_ROLES = [
        'ADV', 'AGT', 'AGT2', 'ANN', 'APP', 'CLI', 'CNT', 'DBA', 'DBRO', 'DBRW',
        'DEL', 'FAGT', 'FOWN', 'INV', 'LCN', 'OFF', 'OPP', 'OWN', 'PAY', 'PTNR',
        'TRA', 'WRI',
    ];

    /**
     * User role codes used for database access control.
     * Source: database/seeders/ActorRoleTableSeeder.php (DBA, DBRW, DBRO, CLI)
     */
    private const DATABASE_USER_ROLES = ['DBA', 'DBRW', 'DBRO', 'CLI'];

    /**
     * @test
     */
    public function actor_role_enum_values_match_database_seeder(): void
    {
        $enumValues = array_map(fn (ActorRole $role) => $role->value, ActorRole::cases());

        foreach ($enumValues as $value) {
            $this->assertContains(
                $value,
                self::DATABASE_ACTOR_ROLES,
                "ActorRole enum value '{$value}' is not in database seeder. ".
                'This may cause database constraint violations.'
            );
        }
    }

    /**
     * @test
     */
    public function user_role_enum_values_match_database_seeder(): void
    {
        $enumValues = array_map(fn (UserRole $role) => $role->value, UserRole::cases());

        foreach ($enumValues as $value) {
            $this->assertContains(
                $value,
                self::DATABASE_USER_ROLES,
                "UserRole enum value '{$value}' is not in database seeder. ".
                'This may cause authentication failures.'
            );
        }
    }

    /**
     * @test
     */
    public function critical_actor_roles_exist_in_enum(): void
    {
        // These roles are used throughout the application and must exist
        $criticalRoles = ['CLI', 'APP', 'OWN', 'AGT', 'INV', 'DEL', 'CNT', 'PAY', 'ANN'];

        foreach ($criticalRoles as $code) {
            $found = false;
            foreach (ActorRole::cases() as $role) {
                if ($role->value === $code) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Critical actor role '{$code}' is missing from ActorRole enum");
        }
    }

    /**
     * @test
     */
    public function all_user_role_codes_are_valid_actor_roles(): void
    {
        // User roles must be valid actor_role codes since users view is based on actor table
        foreach (UserRole::cases() as $userRole) {
            $this->assertContains(
                $userRole->value,
                self::DATABASE_ACTOR_ROLES,
                "UserRole::{$userRole->name} value '{$userRole->value}' must exist in actor_role table"
            );
        }
    }

    /**
     * @test
     */
    public function event_code_enum_has_valid_values(): void
    {
        // Verify that event codes follow expected pattern (2-6 uppercase alphanumeric characters)
        foreach (EventCode::cases() as $eventCode) {
            $this->assertMatchesRegularExpression(
                '/^[A-Z0-9]{2,6}$/',
                $eventCode->value,
                "EventCode::{$eventCode->name} has invalid format: '{$eventCode->value}'"
            );
        }
    }

    /**
     * @test
     */
    public function category_code_enum_has_valid_values(): void
    {
        // Category codes should be short uppercase strings
        foreach (CategoryCode::cases() as $categoryCode) {
            $this->assertMatchesRegularExpression(
                '/^[A-Z]{2,5}$/',
                $categoryCode->value,
                "CategoryCode::{$categoryCode->name} has invalid format: '{$categoryCode->value}'"
            );
        }
    }

    /**
     * @test
     */
    public function classifier_type_enum_has_valid_values(): void
    {
        // Classifier type codes should be uppercase strings
        foreach (ClassifierType::cases() as $classifierType) {
            $this->assertMatchesRegularExpression(
                '/^[A-Z0-9_]{2,10}$/',
                $classifierType->value,
                "ClassifierType::{$classifierType->name} has invalid format: '{$classifierType->value}'"
            );
        }
    }

    /**
     * @test
     */
    public function enum_values_are_immutable(): void
    {
        // These are the canonical values that must never change
        // Add assertions for any values that are stored in the database

        // ActorRole - critical values
        $this->assertSame('CLI', ActorRole::CLIENT->value);
        $this->assertSame('APP', ActorRole::APPLICANT->value);
        $this->assertSame('OWN', ActorRole::OWNER->value);
        $this->assertSame('AGT', ActorRole::AGENT->value);
        $this->assertSame('INV', ActorRole::INVENTOR->value);

        // UserRole - authentication values
        $this->assertSame('DBA', UserRole::ADMIN->value);
        $this->assertSame('DBRW', UserRole::READ_WRITE->value);
        $this->assertSame('DBRO', UserRole::READ_ONLY->value);
        $this->assertSame('CLI', UserRole::CLIENT->value);

        // EventCode - core lifecycle values
        $this->assertSame('FIL', EventCode::FILING->value);
        $this->assertSame('PUB', EventCode::PUBLICATION->value);
        $this->assertSame('GRT', EventCode::GRANT->value);
        $this->assertSame('REG', EventCode::REGISTRATION->value);
        $this->assertSame('PRI', EventCode::PRIORITY->value);

        // CategoryCode - main categories
        $this->assertSame('PAT', CategoryCode::PATENT->value);
        $this->assertSame('TM', CategoryCode::TRADEMARK->value);
    }
}
