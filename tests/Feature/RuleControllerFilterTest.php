<?php

namespace Tests\Feature;

use App\Models\EventName;
use App\Models\Rule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuleControllerFilterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function rules_can_be_filtered_by_task_name()
    {
        $admin = User::factory()->admin()->create();

        EventName::factory()->renewal()->create();
        EventName::factory()->filing()->create();
        EventName::factory()->create([
            'code' => 'ABC',
            'name' => json_encode(['en' => 'Alpha Task']),
            'is_task' => true,
        ]);

        Rule::factory()->create([
            'task' => 'REN',
            'trigger_event' => 'FIL',
        ]);
        Rule::factory()->create([
            'task' => 'ABC',
            'trigger_event' => 'FIL',
        ]);

        $response = $this->actingAs($admin)->get('/rule?Task=Ren');

        $response->assertStatus(200);
        $response->assertViewHas('ruleslist', function ($rules) {
            return $rules->count() === 1
                && $rules->first()->task === 'REN';
        });
    }
}
