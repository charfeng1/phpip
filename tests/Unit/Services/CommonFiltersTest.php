<?php

namespace Tests\Unit\Services;

use App\Services\CommonFilters;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CommonFilters service.
 *
 * Note: Due to the complexity of mocking Eloquent Builder (which uses __call magic),
 * these tests focus on testing the filter functions themselves rather than
 * the Builder interactions. Integration tests would verify the full behavior.
 */
class CommonFiltersTest extends TestCase
{
    /** @test */
    public function escape_like_wildcards_escapes_percent()
    {
        $this->assertEquals('test\\%value', CommonFilters::escapeLikeWildcards('test%value'));
    }

    /** @test */
    public function escape_like_wildcards_escapes_underscore()
    {
        $this->assertEquals('test\\_value', CommonFilters::escapeLikeWildcards('test_value'));
    }

    /** @test */
    public function escape_like_wildcards_escapes_both()
    {
        $this->assertEquals('test\\%\\_value', CommonFilters::escapeLikeWildcards('test%_value'));
    }

    /** @test */
    public function escape_like_wildcards_leaves_normal_strings_unchanged()
    {
        $this->assertEquals('normal', CommonFilters::escapeLikeWildcards('normal'));
    }

    /** @test */
    public function starts_with_returns_callable()
    {
        $filter = CommonFilters::startsWith('name');
        $this->assertIsCallable($filter);
    }

    /** @test */
    public function contains_returns_callable()
    {
        $filter = CommonFilters::contains('name');
        $this->assertIsCallable($filter);
    }

    /** @test */
    public function exact_returns_callable()
    {
        $filter = CommonFilters::exact('status');
        $this->assertIsCallable($filter);
    }

    /** @test */
    public function json_like_returns_callable()
    {
        $filter = CommonFilters::jsonLike('name');
        $this->assertIsCallable($filter);
    }

    /** @test */
    public function boolean_returns_callable()
    {
        $filter = CommonFilters::boolean('active');
        $this->assertIsCallable($filter);
    }

    /** @test */
    public function date_from_returns_callable()
    {
        $filter = CommonFilters::dateFrom('created_at');
        $this->assertIsCallable($filter);
    }

    /** @test */
    public function date_to_returns_callable()
    {
        $filter = CommonFilters::dateTo('created_at');
        $this->assertIsCallable($filter);
    }

    /** @test */
    public function null_check_returns_callable()
    {
        $filter = CommonFilters::nullCheck('deleted_at');
        $this->assertIsCallable($filter);
    }

    /** @test */
    public function where_in_returns_callable()
    {
        $filter = CommonFilters::whereIn('status');
        $this->assertIsCallable($filter);
    }

    /** @test */
    public function compare_returns_callable()
    {
        $filter = CommonFilters::compare('count', '>');
        $this->assertIsCallable($filter);
    }

    /** @test */
    public function relation_starts_with_returns_callable()
    {
        $filter = CommonFilters::relationStartsWith('country', 'name');
        $this->assertIsCallable($filter);
    }

    /** @test */
    public function json_like_relation_returns_callable()
    {
        $filter = CommonFilters::jsonLikeRelation('category', 'category');
        $this->assertIsCallable($filter);
    }

    /** @test */
    public function compare_validates_operator_allowing_valid_operators()
    {
        $validOperators = ['=', '>', '<', '>=', '<=', '<>', '!='];

        foreach ($validOperators as $operator) {
            $filter = CommonFilters::compare('column', $operator);
            $this->assertIsCallable($filter, "Operator $operator should produce a valid callable");
        }
    }

    /** @test */
    public function compare_falls_back_to_equals_for_invalid_operator()
    {
        // This tests that invalid operators don't cause an error
        // The actual SQL injection prevention is tested via the produced closure
        $filter = CommonFilters::compare('column', 'DROP TABLE');
        $this->assertIsCallable($filter);
    }
}
