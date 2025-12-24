<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RuleControllerTest extends TestCase
{
    /**
     * Test rule index and related pages.
     */
    public function test_index()
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        // Main page with rules list
        $response = $this->get('/rule');
        $response->assertStatus(200)
            ->assertViewHas('ruleslist')
            ->assertSeeText('Draft By');
    }
}
