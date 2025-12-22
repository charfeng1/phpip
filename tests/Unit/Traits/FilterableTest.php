<?php

namespace Tests\Unit\Traits;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class FilterableTest extends TestCase
{
    protected TestableFilterableController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TestableFilterableController();
    }

    /** @test */
    public function apply_filters_does_not_apply_filter_for_null_value()
    {
        $request = Request::create('/', 'GET', ['Name' => null]);
        $query = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Expect the callback never to be called
        $query->expects($this->never())->method('where');

        $this->controller->setFilterRules([
            'Name' => fn ($q, $v) => $q->where('name', $v),
        ]);

        $this->controller->callApplyFilters($query, $request);
    }

    /** @test */
    public function apply_filters_does_not_apply_filter_for_empty_string()
    {
        $request = Request::create('/', 'GET', ['Name' => '']);
        $query = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Expect the callback never to be called
        $query->expects($this->never())->method('where');

        $this->controller->setFilterRules([
            'Name' => fn ($q, $v) => $q->where('name', $v),
        ]);

        $this->controller->callApplyFilters($query, $request);
    }

    /** @test */
    public function apply_filters_applies_filter_for_zero_value()
    {
        $request = Request::create('/', 'GET', ['Status' => 0]);

        $callbackCalled = false;
        $this->controller->setFilterRules([
            'Status' => function ($q, $v) use (&$callbackCalled) {
                $callbackCalled = true;
                $this->assertEquals(0, $v);

                return $q;
            },
        ]);

        $query = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller->callApplyFilters($query, $request);

        $this->assertTrue($callbackCalled, 'Filter callback should be called for zero value');
    }

    /** @test */
    public function apply_filters_applies_filter_for_string_zero()
    {
        $request = Request::create('/', 'GET', ['Status' => '0']);

        $callbackCalled = false;
        $this->controller->setFilterRules([
            'Status' => function ($q, $v) use (&$callbackCalled) {
                $callbackCalled = true;
                $this->assertEquals('0', $v);

                return $q;
            },
        ]);

        $query = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller->callApplyFilters($query, $request);

        $this->assertTrue($callbackCalled, 'Filter callback should be called for string zero value');
    }

    /** @test */
    public function get_old_filters_returns_only_filled_values()
    {
        $request = Request::create('/', 'GET', [
            'Name' => 'test',
            'Status' => null,
            'Code' => '',
            'Active' => 0,
        ]);

        $this->controller->setFilterRules([
            'Name' => fn ($q, $v) => $q,
            'Status' => fn ($q, $v) => $q,
            'Code' => fn ($q, $v) => $q,
            'Active' => fn ($q, $v) => $q,
        ]);

        $oldFilters = $this->controller->callGetOldFilters($request);

        $this->assertArrayHasKey('Name', $oldFilters);
        $this->assertEquals('test', $oldFilters['Name']);
        $this->assertArrayNotHasKey('Status', $oldFilters);
        $this->assertArrayNotHasKey('Code', $oldFilters);
        $this->assertArrayHasKey('Active', $oldFilters);
        $this->assertEquals(0, $oldFilters['Active']);
    }
}

/**
 * Testable controller class to expose protected trait methods.
 */
class TestableFilterableController
{
    use Filterable;

    public function setFilterRules(array $rules): void
    {
        $this->filterRules = $rules;
    }

    public function callApplyFilters(Builder $query, Request $request): Builder
    {
        return $this->applyFilters($query, $request);
    }

    public function callGetOldFilters(Request $request): array
    {
        return $this->getOldFilters($request);
    }
}
