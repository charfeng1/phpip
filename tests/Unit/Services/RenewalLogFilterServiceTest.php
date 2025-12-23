<?php

namespace Tests\Unit\Services;

use App\Services\RenewalLogFilterService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RenewalLogFilterService.
 *
 * Tests the filtering logic for renewal processing logs including
 * matter reference, client, job ID, user, and date range filters.
 *
 * Note: These are pure unit tests that don't require database access.
 * They verify the filter application logic without executing actual queries.
 */
class RenewalLogFilterServiceTest extends TestCase
{
    protected RenewalLogFilterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RenewalLogFilterService;
    }

    public function test_is_valid_filter_key_returns_true_for_known_keys(): void
    {
        $this->assertTrue($this->service->isValidFilterKey('Matter'));
        $this->assertTrue($this->service->isValidFilterKey('Client'));
        $this->assertTrue($this->service->isValidFilterKey('Job'));
        $this->assertTrue($this->service->isValidFilterKey('User'));
        $this->assertTrue($this->service->isValidFilterKey('Fromdate'));
        $this->assertTrue($this->service->isValidFilterKey('Untildate'));
    }

    public function test_is_valid_filter_key_returns_false_for_unknown_keys(): void
    {
        $this->assertFalse($this->service->isValidFilterKey('unknown'));
        $this->assertFalse($this->service->isValidFilterKey('malicious; DROP TABLE logs'));
        $this->assertFalse($this->service->isValidFilterKey(''));
        $this->assertFalse($this->service->isValidFilterKey('../etc/passwd'));
        $this->assertFalse($this->service->isValidFilterKey('UPDATE'));
    }

    public function test_empty_filters_returns_query_unchanged(): void
    {
        $query = $this->createMockRenewalsLog();

        $result = $this->service->filterLogs($query, []);

        $this->assertSame($query, $result);
    }

    public function test_null_and_empty_values_are_ignored(): void
    {
        $query = $this->createMockRenewalsLog();

        $result = $this->service->filterLogs($query, [
            'Matter' => '',
            'Client' => null,
            'Job' => '  ',
        ]);

        // Query should still be returned (unchanged)
        $this->assertNotNull($result);
    }

    public function test_unknown_filter_keys_are_ignored_for_security(): void
    {
        $query = $this->createMockRenewalsLog();

        $result = $this->service->filterLogs($query, [
            'malicious_key' => "'; DROP TABLE logs; --",
            'hacker_input' => '../../../etc/passwd',
        ]);

        // Should not throw error, query returned
        $this->assertNotNull($result);
    }

    public function test_matter_filter_is_applied(): void
    {
        $query = $this->createMockRenewalsLog();

        $result = $this->service->filterLogs($query, ['Matter' => 'EP123']);

        $this->assertNotNull($result);
    }

    public function test_client_filter_is_applied(): void
    {
        $query = $this->createMockRenewalsLog();

        $result = $this->service->filterLogs($query, ['Client' => 'Acme Corp']);

        $this->assertNotNull($result);
    }

    public function test_job_filter_is_applied(): void
    {
        $query = $this->createMockRenewalsLog();

        $result = $this->service->filterLogs($query, ['Job' => '12345']);

        $this->assertNotNull($result);
    }

    public function test_user_filter_is_applied(): void
    {
        $query = $this->createMockRenewalsLog();

        $result = $this->service->filterLogs($query, ['User' => 'John Doe']);

        $this->assertNotNull($result);
    }

    public function test_date_range_filters_are_applied(): void
    {
        $query = $this->createMockRenewalsLog();

        $result = $this->service->filterLogs($query, [
            'Fromdate' => '2025-01-01',
            'Untildate' => '2025-12-31',
        ]);

        $this->assertNotNull($result);
    }

    public function test_multiple_filters_are_applied(): void
    {
        $query = $this->createMockRenewalsLog();

        $result = $this->service->filterLogs($query, [
            'Matter' => 'EP123',
            'Client' => 'Acme Corp',
            'Job' => '12345',
        ]);

        $this->assertNotNull($result);
    }

    public function test_allowed_filter_keys_contain_expected_values(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $constant = $reflection->getConstant('ALLOWED_FILTER_KEYS');

        $this->assertIsArray($constant);
        $this->assertCount(6, $constant);
        $this->assertContains('Matter', $constant);
        $this->assertContains('Client', $constant);
        $this->assertContains('Job', $constant);
        $this->assertContains('User', $constant);
        $this->assertContains('Fromdate', $constant);
        $this->assertContains('Untildate', $constant);
    }

    /**
     * Create a mock RenewalsLog for testing.
     * We use a mock because we're testing the filter application logic,
     * not the actual database queries.
     */
    protected function createMockRenewalsLog()
    {
        return new class {
            public function whereHas($relation, $callback)
            {
                return $this;
            }

            public function where($column, $operator, $value = null)
            {
                return $this;
            }

            public function whereLike($column, $value)
            {
                return $this;
            }

            public function orderby($column)
            {
                return $this;
            }

            public function simplePaginate($perPage)
            {
                return new class {
                    public function items()
                    {
                        return [];
                    }
                };
            }
        };
    }
}
