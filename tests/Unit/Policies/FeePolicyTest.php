<?php

namespace Tests\Unit\Policies;

use App\Models\Fee;
use App\Models\User;
use App\Policies\FeePolicy;
use Tests\TestCase;

class FeePolicyTest extends TestCase
{
    protected FeePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new FeePolicy();
    }

    /** @test */
    public function admin_can_view_any_fees()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function read_write_user_can_view_any_fees()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_user_can_view_any_fees()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_cannot_view_any_fees()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($client));
    }

    /** @test */
    public function admin_can_view_fee()
    {
        $admin = User::factory()->admin()->create();
        $fee = Fee::create([
            'for_country' => 'US',
            'for_category' => 'PAT',
            'qt' => 1,
            'cost' => 100.00,
            'fee' => 500.00,
        ]);

        $this->assertTrue($this->policy->view($admin, $fee));
    }

    /** @test */
    public function read_only_user_can_view_fee()
    {
        $user = User::factory()->readOnly()->create();
        $fee = Fee::create([
            'for_country' => 'US',
            'for_category' => 'PAT',
            'qt' => 2,
            'cost' => 100.00,
            'fee' => 500.00,
        ]);

        $this->assertTrue($this->policy->view($user, $fee));
    }

    /** @test */
    public function client_cannot_view_fee()
    {
        $client = User::factory()->client()->create();
        $fee = Fee::create([
            'for_country' => 'US',
            'for_category' => 'PAT',
            'qt' => 3,
            'cost' => 100.00,
            'fee' => 500.00,
        ]);

        $this->assertFalse($this->policy->view($client, $fee));
    }

    /** @test */
    public function only_admin_can_create_fee()
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
    public function only_admin_can_update_fee()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $fee = Fee::create([
            'for_country' => 'EP',
            'for_category' => 'PAT',
            'qt' => 4,
            'cost' => 100.00,
            'fee' => 500.00,
        ]);

        $this->assertTrue($this->policy->update($admin, $fee));
        $this->assertFalse($this->policy->update($readWrite, $fee));
    }

    /** @test */
    public function only_admin_can_delete_fee()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $fee = Fee::create([
            'for_country' => 'DE',
            'for_category' => 'PAT',
            'qt' => 5,
            'cost' => 100.00,
            'fee' => 500.00,
        ]);

        $this->assertTrue($this->policy->delete($admin, $fee));
        $this->assertFalse($this->policy->delete($readWrite, $fee));
    }

    /** @test */
    public function read_only_user_cannot_modify_fees()
    {
        $user = User::factory()->readOnly()->create();
        $fee = Fee::create([
            'for_country' => 'FR',
            'for_category' => 'PAT',
            'qt' => 6,
            'cost' => 100.00,
            'fee' => 500.00,
        ]);

        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $fee));
        $this->assertFalse($this->policy->delete($user, $fee));
    }
}
