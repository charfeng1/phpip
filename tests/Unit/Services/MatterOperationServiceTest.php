<?php

namespace Tests\Unit\Services;

use App\Services\MatterOperationService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MatterOperationService.
 *
 * Tests the special matter creation operations:
 * - descendant: Create descendant with priority/entry events
 * - clone: Clone matter with actors and classifiers
 * - new: Create simple matter with received event
 *
 * Note: These are pure unit tests that don't require database access.
 * They verify the operation validation logic without executing actual database queries.
 */
class MatterOperationServiceTest extends TestCase
{
    protected MatterOperationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatterOperationService;
    }

    public function test_is_valid_operation_returns_true_for_known_operations(): void
    {
        $this->assertTrue($this->service->isValidOperation('descendant'));
        $this->assertTrue($this->service->isValidOperation('clone'));
        $this->assertTrue($this->service->isValidOperation('new'));
    }

    public function test_is_valid_operation_returns_false_for_unknown_operations(): void
    {
        $this->assertFalse($this->service->isValidOperation('unknown'));
        $this->assertFalse($this->service->isValidOperation('delete'));
        $this->assertFalse($this->service->isValidOperation(''));
        $this->assertFalse($this->service->isValidOperation('malicious; DROP TABLE matters'));
        $this->assertFalse($this->service->isValidOperation('UPDATE'));
        $this->assertFalse($this->service->isValidOperation('../etc/passwd'));
    }

    public function test_allowed_operations_contain_expected_values(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $constant = $reflection->getConstant('ALLOWED_OPERATIONS');

        $this->assertIsArray($constant);
        $this->assertCount(3, $constant);
        $this->assertContains('descendant', $constant);
        $this->assertContains('clone', $constant);
        $this->assertContains('new', $constant);
    }

    public function test_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(MatterOperationService::class, $this->service);
    }
}
