<?php

namespace Tests\Feature;

use App\Models\TemplateClass;
use App\Models\User;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    /** @test */
    public function template_classes_can_be_filtered_by_name()
    {
        $admin = User::factory()->admin()->create();

        TemplateClass::create(['name' => 'Alpha Template', 'notes' => 'First']);
        TemplateClass::create(['name' => 'Beta Template', 'notes' => 'Second']);

        $response = $this->actingAs($admin)->get('/document?Name=Alpha');

        $response->assertStatus(200);
        $response->assertViewHas('template_classes', function ($templateClasses) {
            return $templateClasses->count() === 1
                && $templateClasses->first()->name === 'Alpha Template';
        });
    }
}
