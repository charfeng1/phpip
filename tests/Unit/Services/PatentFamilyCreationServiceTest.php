<?php

namespace Tests\Unit\Services;

use App\Enums\ActorRole;
use App\Services\OPSService;
use App\Services\PatentFamilyCreationService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PatentFamilyCreationService.
 *
 * These tests focus on testing logic that doesn't require database interaction.
 * Full integration tests should be added as Feature tests using Laravel's test framework.
 */
class PatentFamilyCreationServiceTest extends TestCase
{
    protected PatentFamilyCreationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $mockOpsService = $this->createMock(OPSService::class);
        $this->service = new PatentFamilyCreationService($mockOpsService, 'testuser');
    }

    public function test_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(PatentFamilyCreationService::class, $this->service);
    }

    public function test_service_uses_default_ops_service_when_not_provided(): void
    {
        $service = new PatentFamilyCreationService(null, 'testuser');
        $this->assertInstanceOf(PatentFamilyCreationService::class, $service);
    }

    public function test_clean_name_removes_trailing_comma(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('cleanName');
        $method->setAccessible(true);

        $this->assertEquals('Smith John', $method->invoke($this->service, 'Smith John,'));
        $this->assertEquals('Smith John', $method->invoke($this->service, 'Smith John'));
        $this->assertEquals('', $method->invoke($this->service, ','));
    }

    public function test_build_matter_data_sets_basic_fields(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildMatterData');
        $method->setAccessible(true);

        $app = [
            'app' => ['country' => 'EP', 'kind' => 'A', 'number' => '12345678'],
            'pct' => null,
            'div' => null,
            'cnt' => null,
        ];

        // Pass existingCount=0 to bypass database query
        $result = $method->invoke($this->service, $app, 'TEST001', 'PAT', 0);

        $this->assertEquals('TEST001', $result['caseref']);
        $this->assertEquals('EP', $result['country']);
        $this->assertEquals('PAT', $result['category_code']);
        $this->assertEquals('testuser', $result['creator']);
        $this->assertNull($result['idx']);
    }

    public function test_build_matter_data_sets_pro_type_for_provisional(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildMatterData');
        $method->setAccessible(true);

        $app = [
            'app' => ['country' => 'US', 'kind' => 'P', 'number' => '123456'],
            'pct' => null,
            'div' => null,
            'cnt' => null,
        ];

        $result = $method->invoke($this->service, $app, 'TEST001', 'PAT', 0);

        $this->assertEquals('PRO', $result['type_code']);
    }

    public function test_build_matter_data_sets_origin_for_pct(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildMatterData');
        $method->setAccessible(true);

        $app = [
            'app' => ['country' => 'EP', 'kind' => 'A', 'number' => '12345678'],
            'pct' => 'WO2023123456',
            'div' => null,
            'cnt' => null,
        ];

        $result = $method->invoke($this->service, $app, 'TEST001', 'PAT', 0);

        $this->assertEquals('WO', $result['origin']);
    }

    public function test_build_matter_data_sets_div_type_for_divisional(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildMatterData');
        $method->setAccessible(true);

        $app = [
            'app' => ['country' => 'EP', 'kind' => 'A', 'number' => '12345679'],
            'pct' => null,
            'div' => '12345678',
            'cnt' => null,
        ];

        $result = $method->invoke($this->service, $app, 'TEST001', 'PAT', 0);

        $this->assertEquals('DIV', $result['type_code']);
    }

    public function test_build_matter_data_sets_cnt_type_for_continuation(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildMatterData');
        $method->setAccessible(true);

        $app = [
            'app' => ['country' => 'US', 'kind' => 'A', 'number' => '654321'],
            'pct' => null,
            'div' => null,
            'cnt' => '123456',
        ];

        $result = $method->invoke($this->service, $app, 'TEST001', 'PAT', 0);

        $this->assertEquals('CNT', $result['type_code']);
    }

    public function test_build_matter_data_sets_idx_when_existing_count_positive(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildMatterData');
        $method->setAccessible(true);

        $app = [
            'app' => ['country' => 'EP', 'kind' => 'A', 'number' => '12345678'],
            'pct' => null,
            'div' => null,
            'cnt' => null,
        ];

        // Pass existingCount=2 to simulate 2 existing matters with same UID
        $result = $method->invoke($this->service, $app, 'TEST001', 'PAT', 2);

        $this->assertEquals(3, $result['idx']);
    }

    public function test_create_from_ops_returns_errors_when_ops_returns_errors(): void
    {
        $mockOpsService = $this->createMock(OPSService::class);
        $mockOpsService->method('getFamilyMembers')
            ->willReturn([
                'errors' => ['docnum' => ['Number not found']],
                'message' => 'Number not found in OPS Family',
            ]);

        $service = new PatentFamilyCreationService($mockOpsService, 'testuser');
        $result = $service->createFromOPS('INVALID123', 'TEST001', 'PAT', 1);

        $this->assertArrayHasKey('errors', $result);
        $this->assertEquals(['docnum' => ['Number not found']], $result['errors']);
    }

    public function test_create_from_ops_returns_exception_when_ops_returns_exception(): void
    {
        $mockOpsService = $this->createMock(OPSService::class);
        $mockOpsService->method('getFamilyMembers')
            ->willReturn([
                'exception' => 'OPS server error',
                'message' => 'OPS server error, try again',
            ]);

        $service = new PatentFamilyCreationService($mockOpsService, 'testuser');
        $result = $service->createFromOPS('EP12345678', 'TEST001', 'PAT', 1);

        $this->assertArrayHasKey('exception', $result);
        $this->assertEquals('OPS server error', $result['exception']);
    }

    public function test_actor_role_enum_values_are_correct(): void
    {
        $this->assertEquals('CLI', ActorRole::CLIENT->value);
        $this->assertEquals('APP', ActorRole::APPLICANT->value);
        $this->assertEquals('INV', ActorRole::INVENTOR->value);
    }
}
