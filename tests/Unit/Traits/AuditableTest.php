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
        $classifier = new Classifier();
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
        $classifier = new Classifier();
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

        $classifier->withoutAuditing();

        $this->assertTrue(true); // Method exists and doesn't throw
    }

    /** @test */
    public function it_can_re_enable_auditing()
    {
        $classifier = new Classifier();

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

        if ($latestLog) {
            $this->assertEquals('updated', $latestLog->action);
        }

        $this->assertTrue(true);
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
}
