<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Matter;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Tests for codebase quality fixes.
 *
 * This test class covers:
 * - SQL injection prevention via locale validation (RuleController, AppServiceProvider)
 * - Matter expire_date casting
 * - Authorization on recreateTasks route
 */
class CodebaseQualityFixesTest extends TestCase
{
    // Note: No RefreshDatabase - we use DatabaseTransactions from base TestCase

    // ========================================================================
    // Locale Validation Tests (SQL Injection Prevention)
    // ========================================================================

    /** @test */
    public function locale_validation_accepts_valid_two_letter_locales()
    {
        $admin = $this->getOrCreateAdminUser();

        // Valid two-letter locales should work
        App::setLocale('en');
        $response = $this->actingAs($admin)->get('/rule');
        $response->assertStatus(200);

        App::setLocale('fr');
        $response = $this->actingAs($admin)->get('/rule');
        $response->assertStatus(200);

        App::setLocale('de');
        $response = $this->actingAs($admin)->get('/rule');
        $response->assertStatus(200);
    }

    /** @test */
    public function rule_controller_handles_locale_safely()
    {
        // This test verifies that RuleController's locale handling doesn't cause SQL errors
        // The locale validation happens at multiple levels (Symfony Translator + our code)
        $admin = $this->getOrCreateAdminUser();

        // Set a valid locale
        App::setLocale('en');

        $response = $this->actingAs($admin)->get('/rule');
        $response->assertStatus(200);

        // Verify no SQL errors occurred - the page loaded with rules
        $response->assertViewHas('ruleslist');
    }

    // ========================================================================
    // Matter expire_date Casting Tests
    // ========================================================================

    /** @test */
    public function matter_model_has_expire_date_cast_configured()
    {
        $matter = new Matter();
        $casts = $matter->getCasts();

        // Verify expire_date is cast to date with Y-m-d format
        $this->assertArrayHasKey('expire_date', $casts);
        $this->assertEquals('date:Y-m-d', $casts['expire_date']);
    }

    /** @test */
    public function matter_expire_date_is_cast_to_carbon_when_set()
    {
        // Create a matter with expire_date using raw SQL to avoid factory issues
        $matterId = DB::table('matter')->insertGetId([
            'caseref' => 'TEST' . rand(1000, 9999),
            'country' => 'US',
            'category_code' => 'PAT',
            'expire_date' => '2045-06-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $matter = Matter::find($matterId);

        // expire_date should be a Carbon instance
        $this->assertInstanceOf(Carbon::class, $matter->expire_date);
        $this->assertEquals('2045-06-15', $matter->expire_date->format('Y-m-d'));
    }

    /** @test */
    public function matter_expire_date_serializes_to_ymd_format()
    {
        // Create a matter with expire_date
        $matterId = DB::table('matter')->insertGetId([
            'caseref' => 'TEST' . rand(1000, 9999),
            'country' => 'US',
            'category_code' => 'PAT',
            'expire_date' => '2045-06-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $matter = Matter::find($matterId);
        $array = $matter->toArray();

        // The date should be serialized in Y-m-d format
        $this->assertEquals('2045-06-15', $array['expire_date']);
    }

    // ========================================================================
    // recreateTasks Route Authorization Tests
    // ========================================================================

    /** @test */
    public function recreate_tasks_route_requires_authentication()
    {
        // Create an event for testing
        $matterId = DB::table('matter')->insertGetId([
            'caseref' => 'TEST' . rand(1000, 9999),
            'country' => 'US',
            'category_code' => 'PAT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $eventId = DB::table('event')->insertGetId([
            'matter_id' => $matterId,
            'code' => 'FIL',
            'event_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Unauthenticated request should be redirected to login
        $response = $this->post("/event/{$eventId}/recreateTasks");
        $response->assertRedirect('/login');
    }

    /** @test */
    public function recreate_tasks_route_requires_readwrite_gate()
    {
        // Create an event for testing
        $matterId = DB::table('matter')->insertGetId([
            'caseref' => 'TEST' . rand(1000, 9999),
            'country' => 'US',
            'category_code' => 'PAT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $eventId = DB::table('event')->insertGetId([
            'matter_id' => $matterId,
            'code' => 'FIL',
            'event_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get or create a client user (who should NOT have readwrite access)
        $clientUser = $this->getOrCreateUserWithRole('CLI');

        $response = $this->actingAs($clientUser)->post("/event/{$eventId}/recreateTasks");

        // Should be forbidden (403)
        $response->assertStatus(403);
    }

    // ========================================================================
    // Gate Authorization Tests
    // ========================================================================

    /** @test */
    public function gate_readwrite_denies_client_role()
    {
        $clientUser = $this->getOrCreateUserWithRole('CLI');

        $this->actingAs($clientUser);

        $this->assertFalse(Gate::allows('readwrite'));
    }

    /** @test */
    public function gate_readwrite_denies_readonly_role()
    {
        $readonlyUser = $this->getOrCreateUserWithRole('DBRO');

        $this->actingAs($readonlyUser);

        $this->assertFalse(Gate::allows('readwrite'));
    }

    /** @test */
    public function gate_readwrite_allows_admin_role()
    {
        $adminUser = $this->getOrCreateUserWithRole('DBA');

        $this->actingAs($adminUser);

        $this->assertTrue(Gate::allows('readwrite'));
    }

    /** @test */
    public function gate_readwrite_allows_readwrite_role()
    {
        $rwUser = $this->getOrCreateUserWithRole('DBRW');

        $this->actingAs($rwUser);

        $this->assertTrue(Gate::allows('readwrite'));
    }

    // ========================================================================
    // Helper Methods
    // ========================================================================

    /**
     * Get or create an admin user for testing.
     */
    private function getOrCreateAdminUser(): User
    {
        return $this->getOrCreateUserWithRole('DBA');
    }

    /**
     * Get or create a user with the specified role.
     * Uses raw DB insert to avoid factory issues with the users VIEW.
     */
    private function getOrCreateUserWithRole(string $role): User
    {
        // Try to find existing user with this role
        $user = User::where('default_role', $role)->first();

        if ($user) {
            return $user;
        }

        // Create a new actor with login credentials (which creates a user via the VIEW)
        // Get the next available ID to avoid sequence conflicts
        $maxId = DB::table('actor')->max('id') ?? 0;
        $nextId = $maxId + 1;
        $login = 'test_' . strtolower($role) . '_' . $nextId;

        DB::table('actor')->insert([
            'id' => $nextId,
            'name' => "Test {$role} User",
            'login' => $login,
            'email' => "{$login}@example.com",
            'password' => bcrypt('password'),
            'default_role' => $role,
            'phy_person' => true,
            'language' => 'en',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update the sequence to avoid future conflicts
        DB::statement("SELECT setval('actor_id_seq', (SELECT MAX(id) FROM actor))");

        return User::where('login', $login)->first();
    }
}
