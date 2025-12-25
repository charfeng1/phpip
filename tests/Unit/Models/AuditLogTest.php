<?php

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use App\Models\Matter;
use App\Models\User;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    /** @test */
    public function it_can_create_an_audit_log()
    {
        $matter = Matter::factory()->create();
        $user = User::factory()->create(['login' => 'audit.user']);

        $auditLog = AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'created',
            'user_login' => 'audit.user',
            'user_name' => 'Audit User',
            'new_values' => ['caseref' => 'TEST001'],
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $matter->id,
            'action' => 'created',
        ]);
    }

    /** @test */
    public function it_has_morphto_auditable_relationship()
    {
        $matter = Matter::factory()->create();

        $auditLog = AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'updated',
            'created_at' => now(),
        ]);

        $this->assertInstanceOf(Matter::class, $auditLog->auditable);
        $this->assertEquals($matter->id, $auditLog->auditable->id);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create(['login' => 'test.user']);
        $matter = Matter::factory()->create();

        $auditLog = AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'updated',
            'user_login' => 'test.user',
            'created_at' => now(),
        ]);

        $auditUser = $auditLog->user;

        $this->assertInstanceOf(User::class, $auditUser);
        $this->assertEquals('test.user', $auditUser->login);
    }

    /** @test */
    public function it_casts_old_values_to_array()
    {
        $matter = Matter::factory()->create();

        $auditLog = AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'updated',
            'old_values' => ['caseref' => 'OLD001'],
            'new_values' => ['caseref' => 'NEW001'],
            'created_at' => now(),
        ]);

        $this->assertIsArray($auditLog->old_values);
        $this->assertEquals('OLD001', $auditLog->old_values['caseref']);
    }

    /** @test */
    public function it_casts_new_values_to_array()
    {
        $matter = Matter::factory()->create();

        $auditLog = AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'created',
            'new_values' => ['field1' => 'value1', 'field2' => 'value2'],
            'created_at' => now(),
        ]);

        $this->assertIsArray($auditLog->new_values);
        $this->assertCount(2, $auditLog->new_values);
    }

    /** @test */
    public function it_returns_model_name_attribute()
    {
        $matter = Matter::factory()->create();

        $auditLog = AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'created',
            'created_at' => now(),
        ]);

        $this->assertEquals('Matter', $auditLog->model_name);
    }

    /** @test */
    public function it_returns_changed_fields_for_created_action()
    {
        $matter = Matter::factory()->create();

        $auditLog = AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'created',
            'new_values' => ['field1' => 'value1', 'field2' => 'value2'],
            'created_at' => now(),
        ]);

        $changedFields = $auditLog->changed_fields;

        $this->assertContains('field1', $changedFields);
        $this->assertContains('field2', $changedFields);
    }

    /** @test */
    public function it_returns_changed_fields_for_deleted_action()
    {
        $matter = Matter::factory()->create();

        $auditLog = AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'deleted',
            'old_values' => ['field1' => 'value1'],
            'created_at' => now(),
        ]);

        $changedFields = $auditLog->changed_fields;

        $this->assertContains('field1', $changedFields);
    }

    /** @test */
    public function it_returns_changed_fields_for_updated_action()
    {
        $matter = Matter::factory()->create();

        $auditLog = AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'updated',
            'old_values' => ['field1' => 'old_value'],
            'new_values' => ['field1' => 'new_value'],
            'created_at' => now(),
        ]);

        $changedFields = $auditLog->changed_fields;

        $this->assertContains('field1', $changedFields);
    }

    /** @test */
    public function it_returns_change_summary()
    {
        $matter = Matter::factory()->create();

        $auditLog = AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'updated',
            'old_values' => ['a' => '1', 'b' => '2', 'c' => '3', 'd' => '4', 'e' => '5'],
            'new_values' => ['a' => 'x', 'b' => 'y', 'c' => 'z', 'd' => 'w', 'e' => 'v'],
            'created_at' => now(),
        ]);

        $summary = $auditLog->change_summary;

        $this->assertStringContainsString('(+', $summary);
        $this->assertStringContainsString('more)', $summary);
    }

    /** @test */
    public function it_returns_no_changes_for_empty_updates()
    {
        $matter = Matter::factory()->create();

        $auditLog = AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'updated',
            'old_values' => [],
            'new_values' => [],
            'created_at' => now(),
        ]);

        $this->assertEquals('No changes', $auditLog->change_summary);
    }

    /** @test */
    public function it_has_for_model_scope()
    {
        $matter = Matter::factory()->create();

        AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'created',
            'created_at' => now(),
        ]);

        $logs = AuditLog::forModel(Matter::class)->get();

        $this->assertGreaterThanOrEqual(1, $logs->count());
    }

    /** @test */
    public function it_has_by_user_scope()
    {
        $matter = Matter::factory()->create();

        AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'created',
            'user_login' => 'scope.test',
            'created_at' => now(),
        ]);

        $logs = AuditLog::byUser('scope.test')->get();

        $this->assertGreaterThanOrEqual(1, $logs->count());
    }

    /** @test */
    public function it_has_by_action_scope()
    {
        $matter = Matter::factory()->create();

        AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'deleted',
            'created_at' => now(),
        ]);

        $logs = AuditLog::byAction('deleted')->get();

        $this->assertGreaterThanOrEqual(1, $logs->count());
    }

    /** @test */
    public function it_has_date_range_scope()
    {
        $matter = Matter::factory()->create();
        $yesterday = now()->subDay();
        $tomorrow = now()->addDay();

        AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'created',
            'created_at' => now(),
        ]);

        $logs = AuditLog::dateRange($yesterday->toDateString(), $tomorrow->toDateString())->get();

        $this->assertGreaterThanOrEqual(1, $logs->count());
    }

    /** @test */
    public function it_has_for_auditable_static_method()
    {
        $matter = Matter::factory()->create();

        AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'created',
            'created_at' => now(),
        ]);

        $logs = AuditLog::forAuditable($matter)->get();

        $this->assertGreaterThanOrEqual(1, $logs->count());
        $this->assertEquals($matter->id, $logs->first()->auditable_id);
    }

    /** @test */
    public function it_does_not_use_timestamps()
    {
        $auditLog = new AuditLog();

        $this->assertFalse($auditLog->usesTimestamps());
    }

    /** @test */
    public function it_casts_created_at_to_datetime()
    {
        $matter = Matter::factory()->create();

        $auditLog = AuditLog::create([
            'auditable_type' => Matter::class,
            'auditable_id' => $matter->id,
            'action' => 'created',
            'created_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $auditLog->created_at);
    }
}
