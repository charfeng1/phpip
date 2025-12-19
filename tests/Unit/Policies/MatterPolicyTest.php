<?php

namespace Tests\Unit\Policies;

use App\Models\Actor;
use App\Models\Matter;
use App\Models\User;
use App\Policies\MatterPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatterPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected MatterPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->policy = new MatterPolicy();
    }

    /** @test */
    public function admin_can_view_any_matter()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();

        $this->assertTrue($this->policy->view($user, $matter));
    }

    /** @test */
    public function read_write_user_can_view_any_matter()
    {
        $user = User::factory()->readWrite()->create();
        $matter = Matter::factory()->create();

        $this->assertTrue($this->policy->view($user, $matter));
    }

    /** @test */
    public function read_only_user_can_view_any_matter()
    {
        $user = User::factory()->readOnly()->create();
        $matter = Matter::factory()->create();

        $this->assertTrue($this->policy->view($user, $matter));
    }

    /** @test */
    public function client_user_cannot_view_unrelated_matter()
    {
        $clientUser = User::factory()->client()->create();
        $matter = Matter::factory()->create();

        // Client user without any actor link should not see the matter
        $this->assertFalse($this->policy->view($clientUser, $matter));
    }

    /** @test */
    public function user_with_no_role_cannot_view_unrelated_matter()
    {
        $user = User::factory()->create(['default_role' => null]);
        $matter = Matter::factory()->create();

        $this->assertFalse($this->policy->view($user, $matter));
    }

    /** @test */
    public function user_with_empty_role_cannot_view_unrelated_matter()
    {
        $user = User::factory()->create(['default_role' => '']);
        $matter = Matter::factory()->create();

        $this->assertFalse($this->policy->view($user, $matter));
    }

    /** @test */
    public function client_can_view_own_matter()
    {
        $clientUser = User::factory()->client()->create();
        $matter = Matter::factory()->create();

        // Create a matter-actor link with CLI role
        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $clientUser->id,
            'role' => 'CLI',
            'shared' => 0,
            'display_order' => 1,
        ]);

        $this->assertTrue($this->policy->view($clientUser, $matter));
    }

    /** @test */
    public function admin_role_is_dba()
    {
        $user = User::factory()->create(['default_role' => 'DBA']);
        $matter = Matter::factory()->create();

        $this->assertTrue($this->policy->view($user, $matter));
    }

    /** @test */
    public function read_write_role_is_dbrw()
    {
        $user = User::factory()->create(['default_role' => 'DBRW']);
        $matter = Matter::factory()->create();

        $this->assertTrue($this->policy->view($user, $matter));
    }

    /** @test */
    public function read_only_role_is_dbro()
    {
        $user = User::factory()->create(['default_role' => 'DBRO']);
        $matter = Matter::factory()->create();

        $this->assertTrue($this->policy->view($user, $matter));
    }

    /** @test */
    public function unknown_role_cannot_view()
    {
        $user = User::factory()->create(['default_role' => 'UNKNOWN']);
        $matter = Matter::factory()->create();

        $this->assertFalse($this->policy->view($user, $matter));
    }
}
