<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class UserControllerTest extends TestCase
{

    /** @test */
    public function users_can_be_filtered_by_name_prefix()
    {
        $admin = User::factory()->admin()->create(['name' => 'Zed Admin']);
        User::factory()->create(['name' => 'Alice Adams']);
        User::factory()->create(['name' => 'Bob Baker']);

        $response = $this->actingAs($admin)->get('/user?Name=Alice');

        $response->assertStatus(200);
        $response->assertViewHas('userslist', function ($users) {
            return $users->count() === 1
                && $users->first()->name === 'Alice Adams';
        });
    }
}
