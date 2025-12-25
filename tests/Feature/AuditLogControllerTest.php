<?php

namespace Tests\Feature;

use App\Models\Actor;
use App\Models\AuditLog;
use App\Models\User;
use Tests\TestCase;

class AuditLogControllerTest extends TestCase
{

    /** @test */
    public function audit_logs_can_be_filtered_by_record_id()
    {
        $admin = User::factory()->admin()->create();

        $actorOne = Actor::factory()->create(['name' => 'Audit Target One']);
        $actorTwo = Actor::factory()->create(['name' => 'Audit Target Two']);

        AuditLog::create([
            'auditable_type' => Actor::class,
            'auditable_id' => $actorOne->id,
            'action' => 'created',
            'user_login' => $admin->login,
            'user_name' => $admin->name,
            'old_values' => [],
            'new_values' => ['name' => 'Audit Target One'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'url' => '/actor/'.$actorOne->id,
            'created_at' => now(),
        ]);

        AuditLog::create([
            'auditable_type' => Actor::class,
            'auditable_id' => $actorTwo->id,
            'action' => 'updated',
            'user_login' => $admin->login,
            'user_name' => $admin->name,
            'old_values' => ['name' => 'Old Name'],
            'new_values' => ['name' => 'Audit Target Two'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'url' => '/actor/'.$actorTwo->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/audit?record_id='.$actorOne->id);

        $response->assertStatus(200);
        $response->assertViewHas('auditLogs', function ($auditLogs) use ($actorOne) {
            // Check that all returned logs match the filtered record_id
            return $auditLogs->count() >= 1
                && $auditLogs->every(fn ($log) => $log->auditable_id === $actorOne->id);
        });
    }
}
