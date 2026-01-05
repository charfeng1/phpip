<?php

namespace Tests\Unit\Traits;

use App\Models\AuditLog;
use App\Models\Classifier;
use App\Models\ClassifierType;
use App\Models\Matter;
use App\Models\User;
use Tests\TestCase;

class AuditableTest extends TestCase
{
    /** @test */
    public function it_logs_model_creation()
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $matter = Matter::factory()->create();

        // Create a classifier which uses the Auditable trait
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test Auditable Title',
        ]);

        $auditLog = AuditLog::where('auditable_type', Classifier::class)
            ->where('auditable_id', $classifier->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('created', $auditLog->action);
    }

    /** @test */
    public function it_logs_model_updates()
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Original Title',
        ]);

        $classifier->update(['value' => 'Updated Title']);

        $auditLog = AuditLog::where('auditable_type', Classifier::class)
            ->where('auditable_id', $classifier->id)
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('updated', $auditLog->action);
    }

    /** @test */
    public function it_logs_model_deletion()
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'To Delete',
        ]);

        $classifierId = $classifier->id;
        $classifier->delete();

        $auditLog = AuditLog::where('auditable_type', Classifier::class)
            ->where('auditable_id', $classifierId)
            ->where('action', 'deleted')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('deleted', $auditLog->action);
    }

    /** @test */
    public function it_stores_user_info_in_audit_log()
    {
        $user = User::factory()->admin()->create(['login' => 'audit.test.user']);
        $this->actingAs($user);

        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test With User',
        ]);

        $auditLog = AuditLog::where('auditable_type', Classifier::class)
            ->where('auditable_id', $classifier->id)
            ->first();

        $this->assertEquals('audit.test.user', $auditLog->user_login);
    }

    /** @test */
    public function auditable_model_has_audit_logs_relationship()
    {
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Relationship Test',
        ]);

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\MorphMany::class,
            $classifier->auditLogs()
        );
    }

    /** @test */
    public function it_excludes_password_from_audit()
    {
        $classifier = new Classifier;
        $reflection = new \ReflectionClass($classifier);
        $method = $reflection->getMethod('filterAuditableAttributes');
        $method->setAccessible(true);

        $attributes = ['name' => 'Test', 'password' => 'secret123'];
        $result = $method->invoke($classifier, $attributes);

        $this->assertArrayNotHasKey('password', $result);
    }

    /** @test */
    public function it_excludes_remember_token_from_audit()
    {
        $classifier = new Classifier;
        $reflection = new \ReflectionClass($classifier);
        $method = $reflection->getMethod('filterAuditableAttributes');
        $method->setAccessible(true);

        $attributes = ['name' => 'Test', 'remember_token' => 'token123'];
        $result = $method->invoke($classifier, $attributes);

        $this->assertArrayNotHasKey('remember_token', $result);
    }

    /** @test */
    public function it_can_disable_auditing_temporarily()
    {
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $classifier = new Classifier([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'No Audit Test',
        ]);

        $result = $classifier->withoutAuditing();

        // Method should return the model instance for chaining
        $this->assertInstanceOf(Classifier::class, $result);
    }

    /** @test */
    public function it_can_re_enable_auditing()
    {
        $classifier = new Classifier;

        $result = $classifier->withoutAuditing()->withAuditing();

        $this->assertInstanceOf(Classifier::class, $result);
    }

    /** @test */
    public function get_latest_audit_log_returns_most_recent()
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'First Value',
        ]);

        $classifier->update(['value' => 'Second Value']);

        $latestLog = $classifier->getLatestAuditLog();

        $this->assertNotNull($latestLog, 'Latest audit log should exist after update');
        $this->assertEquals('updated', $latestLog->action);
    }

    /** @test */
    public function get_audit_history_returns_ordered_collection()
    {
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'History Test',
        ]);

        $history = $classifier->getAuditHistory();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $history);
    }

    /** @test */
    public function get_audit_history_respects_limit()
    {
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Limit Test',
        ]);

        $classifier->update(['value' => 'Update 1']);
        $classifier->update(['value' => 'Update 2']);
        $classifier->update(['value' => 'Update 3']);

        $history = $classifier->getAuditHistory(2);

        $this->assertLessThanOrEqual(2, $history->count());
    }

    /** @test */
    public function it_logs_updates_for_string_pk_models()
    {
        // Test that Auditable works with models using string primary keys
        // This verifies the audit_logs.auditable_id VARCHAR(255) column works
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        // ClassifierType uses string 'code' as primary key (max 5 chars)
        $code = 'AT'.substr(time(), -2); // e.g., AT45
        $classifierType = ClassifierType::create([
            'code' => $code,
            'type' => ['en' => 'Audit Test Type'],
        ]);

        // Update the model to trigger audit logging
        $classifierType->update(['type' => ['en' => 'Updated Audit Test Type']]);

        // Verify audit log was created with string PK
        $auditLog = AuditLog::where('auditable_type', ClassifierType::class)
            ->where('auditable_id', $classifierType->code) // String PK
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($auditLog, 'Audit log should be created for string PK model');
        $this->assertEquals($classifierType->code, $auditLog->auditable_id);
        $this->assertEquals('updated', $auditLog->action);

        // Cleanup
        $classifierType->delete();
    }

    /** @test */
    public function it_stores_string_pk_correctly_in_audit_log()
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        // Create a ClassifierType with a known string code (max 5 chars)
        $code = 'SPKT';
        $classifierType = ClassifierType::create([
            'code' => $code,
            'type' => ['en' => 'String PK Test'],
        ]);

        // Verify the audit log stores the string PK correctly
        $auditLog = AuditLog::where('auditable_type', ClassifierType::class)
            ->where('action', 'created')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($auditLog);
        // The auditable_id should be exactly the string code, not cast to integer
        $this->assertIsString($auditLog->auditable_id);
        $this->assertEquals($code, $auditLog->auditable_id);

        // Cleanup
        $classifierType->delete();
    }

    /** @test */
    public function it_excludes_configured_attributes_from_audit()
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        // Classifier excludes created_at and updated_at via $auditExclude
        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Exclusion Test',
        ]);

        $auditLog = AuditLog::where('auditable_type', Classifier::class)
            ->where('auditable_id', $classifier->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($auditLog);
        // Timestamps should be excluded from new_values
        $newValues = $auditLog->new_values;
        $this->assertArrayNotHasKey('created_at', $newValues);
        $this->assertArrayNotHasKey('updated_at', $newValues);
    }
}
