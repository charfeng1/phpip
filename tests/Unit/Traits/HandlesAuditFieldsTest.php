<?php

namespace Tests\Unit\Traits;

use App\Traits\HandlesAuditFields;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class HandlesAuditFieldsTest extends TestCase
{
    protected TestableAuditController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TestableAuditController;
    }

    /** @test */
    public function get_excluded_fields_returns_token_and_method()
    {
        $excludedFields = $this->controller->callGetExcludedFields();

        $this->assertContains('_token', $excludedFields);
        $this->assertContains('_method', $excludedFields);
    }

    /** @test */
    public function get_filtered_data_excludes_token_and_method()
    {
        $request = Request::create('/', 'POST', [
            '_token' => 'abc123',
            '_method' => 'PUT',
            'name' => 'Test',
            'code' => 'TST',
        ]);

        $filteredData = $this->controller->callGetFilteredData($request);

        $this->assertArrayNotHasKey('_token', $filteredData);
        $this->assertArrayNotHasKey('_method', $filteredData);
        $this->assertArrayHasKey('name', $filteredData);
        $this->assertArrayHasKey('code', $filteredData);
        $this->assertEquals('Test', $filteredData['name']);
        $this->assertEquals('TST', $filteredData['code']);
    }

    /** @test */
    public function get_filtered_data_excludes_additional_fields()
    {
        $request = Request::create('/', 'POST', [
            '_token' => 'abc123',
            'name' => 'Test',
            'eventName' => 'Filing',
            'code' => 'TST',
        ]);

        $filteredData = $this->controller->callGetFilteredData($request, ['eventName']);

        $this->assertArrayNotHasKey('_token', $filteredData);
        $this->assertArrayNotHasKey('eventName', $filteredData);
        $this->assertArrayHasKey('name', $filteredData);
        $this->assertArrayHasKey('code', $filteredData);
    }

    /** @test */
    public function get_filtered_data_with_multiple_additional_excludes()
    {
        $request = Request::create('/', 'POST', [
            '_token' => 'abc123',
            'name' => 'Test',
            'eventName' => 'Filing',
            'tempField' => 'temp',
            'code' => 'TST',
        ]);

        $filteredData = $this->controller->callGetFilteredData($request, ['eventName', 'tempField']);

        $this->assertArrayNotHasKey('_token', $filteredData);
        $this->assertArrayNotHasKey('eventName', $filteredData);
        $this->assertArrayNotHasKey('tempField', $filteredData);
        $this->assertArrayHasKey('name', $filteredData);
        $this->assertArrayHasKey('code', $filteredData);
    }

    // Note: Tests for mergeCreator/mergeUpdater require Laravel's Auth facade
    // and are covered in integration tests that run with the full app container.
}

/**
 * Testable controller class to expose protected trait methods.
 */
class TestableAuditController
{
    use HandlesAuditFields;

    public function callMergeCreator(Request $request): Request
    {
        return $this->mergeCreator($request);
    }

    public function callMergeUpdater(Request $request): Request
    {
        return $this->mergeUpdater($request);
    }

    public function callGetExcludedFields(): array
    {
        return $this->getExcludedFields();
    }

    public function callGetFilteredData(Request $request, array $additionalExcludes = []): array
    {
        return $this->getFilteredData($request, $additionalExcludes);
    }
}
