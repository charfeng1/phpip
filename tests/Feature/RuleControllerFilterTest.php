<?php

namespace Tests\Feature;

use App\Models\EventName;
use App\Models\Rule;
use App\Models\User;
use Tests\TestCase;

class RuleControllerFilterTest extends TestCase
{

    /** @test */
    public function rules_can_be_filtered_by_task_name()
    {
        $admin = User::factory()->admin()->create();

        // REN and FIL event_names already exist from seeded data
        // Create a unique test task event
        EventName::factory()->create([
            'code' => 'ZZZ',
            'name' => json_encode(['en' => 'Test Task ZZZ']),
            'is_task' => true,
        ]);

        Rule::factory()->create([
            'task' => 'REN',
            'trigger_event' => 'FIL',
        ]);
        Rule::factory()->create([
            'task' => 'ZZZ',
            'trigger_event' => 'FIL',
        ]);

        $response = $this->actingAs($admin)->get('/rule?Task=Ren');

        $response->assertStatus(200);
        $response->assertViewHas('ruleslist', function ($rules) {
            return $rules->count() >= 1
                && $rules->contains('task', 'REN');
        });
    }
}
