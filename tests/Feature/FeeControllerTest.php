<?php

namespace Tests\Feature;

use App\Models\Fee;
use App\Models\User;
use Tests\TestCase;

class FeeControllerTest extends TestCase
{
    /** @test */
    public function guest_cannot_access_fees()
    {
        $response = $this->get(route('fee.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_can_access_fee_index()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('fee.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function read_only_user_can_access_fee_index()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->get(route('fee.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_access_fee_index()
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->get(route('fee.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_create_fee()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post(route('fee.store'), [
            'for_country' => 'US',
            'for_category' => 'PAT',
            'qt' => 10,
            'cost' => 200.00,
            'fee' => 1000.00,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fees', [
            'for_country' => 'US',
            'qt' => 10,
        ]);
    }

    /** @test */
    public function read_write_user_cannot_create_fee()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->post(route('fee.store'), [
            'for_country' => 'EP',
            'for_category' => 'PAT',
            'qt' => 11,
            'cost' => 300.00,
            'fee' => 1500.00,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_fee()
    {
        $user = User::factory()->admin()->create();
        $fee = Fee::create([
            'for_country' => 'DE',
            'for_category' => 'PAT',
            'qt' => 12,
            'cost' => 400.00,
            'fee' => 2000.00,
        ]);

        $response = $this->actingAs($user)->put(route('fee.update', $fee), [
            'cost' => 450.00,
            'fee' => 2250.00,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function read_write_user_cannot_update_fee()
    {
        $user = User::factory()->readWrite()->create();
        $fee = Fee::create([
            'for_country' => 'FR',
            'for_category' => 'PAT',
            'qt' => 13,
            'cost' => 500.00,
            'fee' => 2500.00,
        ]);

        $response = $this->actingAs($user)->put(route('fee.update', $fee), [
            'cost' => 550.00,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_fee()
    {
        $user = User::factory()->admin()->create();
        $fee = Fee::create([
            'for_country' => 'GB',
            'for_category' => 'PAT',
            'qt' => 14,
            'cost' => 600.00,
            'fee' => 3000.00,
        ]);

        $response = $this->actingAs($user)->delete(route('fee.destroy', $fee));

        $response->assertRedirect();
    }

    /** @test */
    public function fee_index_returns_fees_view()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('fee.index'));

        $response->assertViewIs('fee.index');
    }
}
