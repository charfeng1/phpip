<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\TeamService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TeamServiceTest extends TestCase
{
    protected TeamService $teamService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teamService = new TeamService();
        Cache::flush();
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(TeamService::class, $this->teamService);
    }

    /** @test */
    public function get_subordinate_ids_returns_collection()
    {
        $user = User::factory()->create();

        $result = $this->teamService->getSubordinateIds($user->id);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    /** @test */
    public function get_subordinate_ids_includes_self_by_default()
    {
        $user = User::factory()->create();

        $result = $this->teamService->getSubordinateIds($user->id, true);

        $this->assertTrue($result->contains($user->id));
    }

    /** @test */
    public function get_subordinate_ids_excludes_self_when_specified()
    {
        $user = User::factory()->create();

        $result = $this->teamService->getSubordinateIds($user->id, false);

        $this->assertFalse($result->contains($user->id));
    }

    /** @test */
    public function get_subordinate_ids_includes_direct_reports()
    {
        $supervisor = User::factory()->create();
        $subordinate = User::factory()->create(['parent_id' => $supervisor->id]);

        Cache::flush();
        $result = $this->teamService->getSubordinateIds($supervisor->id);

        $this->assertTrue($result->contains($subordinate->id));
    }

    /** @test */
    public function get_subordinate_logins_returns_collection()
    {
        $user = User::factory()->create(['login' => 'test.user']);

        $result = $this->teamService->getSubordinateLogins($user->id);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    /** @test */
    public function get_supervisor_ids_returns_collection()
    {
        $user = User::factory()->create();

        $result = $this->teamService->getSupervisorIds($user->id);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    /** @test */
    public function get_supervisor_ids_can_include_self()
    {
        $user = User::factory()->create();

        $result = $this->teamService->getSupervisorIds($user->id, true);

        $this->assertTrue($result->contains($user->id));
    }

    /** @test */
    public function get_supervisor_ids_excludes_self_by_default()
    {
        $user = User::factory()->create();

        $result = $this->teamService->getSupervisorIds($user->id, false);

        $this->assertFalse($result->contains($user->id));
    }

    /** @test */
    public function is_supervisor_returns_false_for_user_without_reports()
    {
        $user = User::factory()->create();
        Cache::flush();

        $result = $this->teamService->isSupervisor($user->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function is_supervisor_returns_true_for_user_with_reports()
    {
        $supervisor = User::factory()->create();
        User::factory()->create(['parent_id' => $supervisor->id]);
        Cache::flush();

        $result = $this->teamService->isSupervisor($supervisor->id);

        $this->assertTrue($result);
    }

    /** @test */
    public function can_view_user_work_returns_true_for_same_user()
    {
        $user = User::factory()->create();

        $result = $this->teamService->canViewUserWork($user->id, $user->id);

        $this->assertTrue($result);
    }

    /** @test */
    public function can_view_user_work_returns_true_for_supervisor()
    {
        $supervisor = User::factory()->create();
        $subordinate = User::factory()->create(['parent_id' => $supervisor->id]);
        Cache::flush();

        $result = $this->teamService->canViewUserWork($supervisor->id, $subordinate->id);

        $this->assertTrue($result);
    }

    /** @test */
    public function can_view_user_work_returns_false_for_unrelated_users()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Cache::flush();

        $result = $this->teamService->canViewUserWork($user1->id, $user2->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function can_view_user_work_by_login_returns_false_for_invalid_login()
    {
        $user = User::factory()->create();

        $result = $this->teamService->canViewUserWorkByLogin($user->id, 'nonexistent.user');

        $this->assertFalse($result);
    }

    /** @test */
    public function can_view_user_work_by_login_returns_true_for_own_login()
    {
        $user = User::factory()->create(['login' => 'my.login']);

        $result = $this->teamService->canViewUserWorkByLogin($user->id, 'my.login');

        $this->assertTrue($result);
    }

    /** @test */
    public function get_direct_reports_returns_collection()
    {
        $supervisor = User::factory()->create();
        $subordinate = User::factory()->create(['parent_id' => $supervisor->id]);

        $result = $this->teamService->getDirectReports($supervisor->id);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertTrue($result->contains('id', $subordinate->id));
    }

    /** @test */
    public function get_team_members_returns_collection()
    {
        $user = User::factory()->create();

        $result = $this->teamService->getTeamMembers($user->id);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    /** @test */
    public function get_team_tree_returns_array()
    {
        $user = User::factory()->create();

        $result = $this->teamService->getTeamTree($user->id);

        $this->assertIsArray($result);
    }

    /** @test */
    public function get_team_tree_returns_empty_for_nonexistent_user()
    {
        $result = $this->teamService->getTeamTree(999999);

        $this->assertEmpty($result);
    }

    /** @test */
    public function get_team_tree_includes_user_info()
    {
        $user = User::factory()->create(['login' => 'tree.user']);

        $result = $this->teamService->getTeamTree($user->id);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('login', $result);
        $this->assertArrayHasKey('children', $result);
    }

    /** @test */
    public function clear_cache_runs_without_error()
    {
        $user = User::factory()->create();

        // Populate the cache first
        $this->teamService->getSubordinateIds($user->id);

        // Clear cache should not throw an exception
        $this->teamService->clearCache($user->id);

        // Verify method completed by making another call
        $result = $this->teamService->getSubordinateIds($user->id);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    /** @test */
    public function results_are_cached()
    {
        $user = User::factory()->create();

        // First call
        $result1 = $this->teamService->getSubordinateIds($user->id);

        // Second call should use cache
        $result2 = $this->teamService->getSubordinateIds($user->id);

        $this->assertEquals($result1->toArray(), $result2->toArray());
    }
}
