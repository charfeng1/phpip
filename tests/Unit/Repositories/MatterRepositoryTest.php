<?php

namespace Tests\Unit\Repositories;

use App\Repositories\MatterRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for MatterRepository.
 *
 * Tests the repository's helper methods for SQL expression building.
 * Query building tests require integration tests with a database.
 */
class MatterRepositoryTest extends TestCase
{
    protected MatterRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MatterRepository;
    }

    /**
     * Helper method to call protected methods.
     */
    protected function callProtectedMethod(string $method, array $args = [])
    {
        $reflection = new ReflectionClass($this->repository);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($this->repository, $args);
    }

    /** @test */
    public function build_aggregation_expressions_returns_mysql_syntax_for_mysql(): void
    {
        $result = $this->callProtectedMethod('buildAggregationExpressions', [false, 'en']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('client', $result);
        $this->assertArrayHasKey('clRef', $result);
        $this->assertArrayHasKey('applicant', $result);
        $this->assertArrayHasKey('agent', $result);
        $this->assertArrayHasKey('agtRef', $result);
    }

    /** @test */
    public function build_aggregation_expressions_uses_group_concat_for_mysql(): void
    {
        $result = $this->callProtectedMethod('buildAggregationExpressions', [false, 'en']);

        $this->assertStringContainsString('GROUP_CONCAT', $result['status']);
        $this->assertStringContainsString('GROUP_CONCAT', $result['client']);
        $this->assertStringContainsString('JSON_UNQUOTE', $result['status']);
    }

    /** @test */
    public function build_aggregation_expressions_uses_string_agg_for_postgres(): void
    {
        $result = $this->callProtectedMethod('buildAggregationExpressions', [true, 'en']);

        $this->assertStringContainsString('STRING_AGG', $result['status']);
        $this->assertStringContainsString('STRING_AGG', $result['client']);
        $this->assertStringNotContainsString('GROUP_CONCAT', $result['status']);
    }

    /** @test */
    public function build_aggregation_expressions_includes_locale_in_status(): void
    {
        $result = $this->callProtectedMethod('buildAggregationExpressions', [false, 'fr']);

        $this->assertStringContainsString('fr', $result['status']);
    }

    /** @test */
    public function build_aggregation_expressions_includes_locale_in_postgres_status(): void
    {
        $result = $this->callProtectedMethod('buildAggregationExpressions', [true, 'de']);

        $this->assertStringContainsString('de', $result['status']);
    }

    /** @test */
    public function build_aggregation_expressions_has_correct_column_aliases(): void
    {
        $result = $this->callProtectedMethod('buildAggregationExpressions', [false, 'en']);

        $this->assertStringContainsString('AS Status', $result['status']);
        $this->assertStringContainsString('AS Client', $result['client']);
        $this->assertStringContainsString('AS ClRef', $result['clRef']);
        $this->assertStringContainsString('AS Applicant', $result['applicant']);
        $this->assertStringContainsString('AS AgentName', $result['agent']);
        $this->assertStringContainsString('AS AgtRef', $result['agtRef']);
    }

    /** @test */
    public function build_aggregation_expressions_uses_coalesce_for_display_names(): void
    {
        $result = $this->callProtectedMethod('buildAggregationExpressions', [false, 'en']);

        $this->assertStringContainsString('COALESCE', $result['client']);
        $this->assertStringContainsString('display_name', $result['client']);
    }

    /** @test */
    public function build_aggregation_expressions_postgres_uses_jsonb_arrow_operator(): void
    {
        $result = $this->callProtectedMethod('buildAggregationExpressions', [true, 'en']);

        $this->assertStringContainsString("event_name.name ->> 'en'", $result['status']);
    }

    /** @test */
    public function build_aggregation_expressions_mysql_uses_json_extract(): void
    {
        $result = $this->callProtectedMethod('buildAggregationExpressions', [false, 'en']);

        $this->assertStringContainsString('JSON_EXTRACT', $result['status']);
        $this->assertStringContainsString('$.', $result['status']);
    }

    /** @test */
    public function build_aggregation_expressions_uses_semicolon_separator_for_mysql(): void
    {
        $result = $this->callProtectedMethod('buildAggregationExpressions', [false, 'en']);

        $this->assertStringContainsString("; '", $result['client']);
    }

    /** @test */
    public function build_aggregation_expressions_uses_semicolon_separator_for_postgres(): void
    {
        $result = $this->callProtectedMethod('buildAggregationExpressions', [true, 'en']);

        $this->assertStringContainsString("'; '", $result['client']);
    }
}
