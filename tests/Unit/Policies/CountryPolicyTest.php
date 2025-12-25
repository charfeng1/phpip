<?php

namespace Tests\Unit\Policies;

use App\Models\Country;
use App\Models\User;
use App\Policies\CountryPolicy;
use Tests\TestCase;

class CountryPolicyTest extends TestCase
{
    protected CountryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CountryPolicy();
    }

    /** @test */
    public function admin_can_view_any_countries()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function read_write_user_can_view_any_countries()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_user_can_view_any_countries()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_cannot_view_any_countries()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($client));
    }

    /** @test */
    public function admin_can_view_country()
    {
        $admin = User::factory()->admin()->create();
        $country = Country::find('US') ?? new Country(['iso' => 'US', 'name' => 'United States']);

        $this->assertTrue($this->policy->view($admin, $country));
    }

    /** @test */
    public function read_only_user_can_view_country()
    {
        $user = User::factory()->readOnly()->create();
        $country = Country::find('US') ?? new Country(['iso' => 'US', 'name' => 'United States']);

        $this->assertTrue($this->policy->view($user, $country));
    }

    /** @test */
    public function client_cannot_view_country()
    {
        $client = User::factory()->client()->create();
        $country = Country::find('US') ?? new Country(['iso' => 'US', 'name' => 'United States']);

        $this->assertFalse($this->policy->view($client, $country));
    }

    /** @test */
    public function only_admin_can_create_country()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $readOnly = User::factory()->readOnly()->create();
        $client = User::factory()->client()->create();

        $this->assertTrue($this->policy->create($admin));
        $this->assertFalse($this->policy->create($readWrite));
        $this->assertFalse($this->policy->create($readOnly));
        $this->assertFalse($this->policy->create($client));
    }

    /** @test */
    public function only_admin_can_update_country()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $country = Country::find('US') ?? new Country(['iso' => 'US', 'name' => 'United States']);

        $this->assertTrue($this->policy->update($admin, $country));
        $this->assertFalse($this->policy->update($readWrite, $country));
    }

    /** @test */
    public function only_admin_can_delete_country()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $country = Country::find('US') ?? new Country(['iso' => 'US', 'name' => 'United States']);

        $this->assertTrue($this->policy->delete($admin, $country));
        $this->assertFalse($this->policy->delete($readWrite, $country));
    }
}
