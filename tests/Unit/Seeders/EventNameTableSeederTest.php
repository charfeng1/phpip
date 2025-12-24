<?php

namespace Tests\Unit\Seeders;

use Database\Seeders\CountryTableSeeder;
use Database\Seeders\EventNameTableSeeder;
use Database\Seeders\MatterCategoryTableSeeder;
use Database\Seeders\MatterTypeTableSeeder;
use Database\Seeders\TaskRulesTableSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Test EventNameTableSeeder.
 *
 * Verifies that all event codes referenced by TaskRulesTableSeeder
 * exist in the event_name table to prevent foreign key violations.
 *
 * Note: These tests require a database with the event_name table already created.
 * They are designed to run against a test database with the base schema loaded.
 */
class EventNameTableSeederTest extends TestCase
{
    protected static bool $seeded = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('event_name') || ! Schema::hasTable('task_rules')) {
            $this->markTestSkipped('Requires base schema with event_name and task_rules tables.');
        }

        if (! self::$seeded) {
            $this->seed(CountryTableSeeder::class);
            $this->seed(MatterCategoryTableSeeder::class);
            $this->seed(MatterTypeTableSeeder::class);
            $this->seed(EventNameTableSeeder::class);
            $this->seed(TaskRulesTableSeeder::class);
            self::$seeded = true;
        }
    }

    /**
     * Test that all required event codes for task rules exist.
     *
     * These codes are referenced as trigger_event in TaskRulesTableSeeder
     * and must exist to avoid foreign key violations.
     *
     * @group seeder
     * @group database
     */
    public function test_required_event_codes_for_task_rules_exist(): void
    {
        $requiredCodes = ['IPER', 'ORI', 'REJF', 'REST', 'SUO', 'WO'];

        foreach ($requiredCodes as $code) {
            $exists = DB::table('event_name')
                ->where('code', trim($code))
                ->exists();

            $this->assertTrue(
                $exists,
                "Event code '{$code}' should exist in event_name table for TaskRulesTableSeeder foreign key constraint"
            );
        }
    }

    /**
     * Test that event codes have required fields.
     *
     * @group seeder
     * @group database
     */
    public function test_event_codes_have_required_fields(): void
    {
        $requiredCodes = ['IPER', 'ORI', 'REJF', 'REST', 'SUO', 'WO'];

        foreach ($requiredCodes as $code) {
            $event = DB::table('event_name')
                ->where('code', trim($code))
                ->first();

            $this->assertNotNull($event, "Event code '{$code}' should exist");
            $this->assertNotEmpty($event->name, "Event code '{$code}' should have a name");
        }
    }

    /**
     * Test IPER event has correct properties.
     *
     * @group seeder
     * @group database
     */
    public function test_iper_event_has_correct_properties(): void
    {
        $iper = DB::table('event_name')
            ->where('code', 'IPER')
            ->first();

        $this->assertEquals('PAT', $iper->category);
        $this->assertEquals('WO', $iper->country);
        $this->assertEquals(0, $iper->is_task);
        $this->assertEquals(1, $iper->unique);
    }

    /**
     * Test REJF event has correct properties.
     *
     * @group seeder
     * @group database
     */
    public function test_rejf_event_has_correct_properties(): void
    {
        $rejf = DB::table('event_name')
            ->where('code', 'REJF')
            ->first();

        $this->assertEquals('PAT', $rejf->category);
        $this->assertEquals('US', $rejf->country);
        $this->assertEquals(1, $rejf->killer);
    }

    /**
     * Test WO event has correct properties.
     *
     * @group seeder
     * @group database
     */
    public function test_wo_event_has_correct_properties(): void
    {
        $wo = DB::table('event_name')
            ->where('code', 'WO')
            ->first();

        $this->assertEquals('PAT', $wo->category);
        $this->assertEquals(1, $wo->unique);
    }

    /**
     * Test that all trigger_event codes in TaskRulesTableSeeder have corresponding entries.
     *
     * @group seeder
     * @group database
     */
    public function test_all_task_rule_trigger_events_exist(): void
    {
        // Get all distinct trigger_event values from task rules
        $triggerEvents = DB::table('task_rules')
            ->select('trigger_event')
            ->whereNotNull('trigger_event')
            ->distinct()
            ->pluck('trigger_event')
            ->map(fn($code) => trim($code))
            ->toArray();

        // Get all event codes from event_name
        $existingCodes = DB::table('event_name')
            ->pluck('code')
            ->map(fn($code) => trim($code))
            ->toArray();

        // Find any missing codes
        $missingCodes = array_diff($triggerEvents, $existingCodes);

        $this->assertEmpty(
            $missingCodes,
            'All trigger_event codes in task_rules should exist in event_name table. Missing: ' . implode(', ', $missingCodes)
        );
    }
}
