<?php

namespace Tests\Unit\Repositories;

use App\Repositories\TaskRepository;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TaskRepository.
 *
 * Tests the repository's filter logic and helper methods.
 * Query building tests require integration tests with a database.
 */
class TaskRepositoryTest extends TestCase
{
    protected TaskRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TaskRepository;
    }

    /** @test */
    public function should_show_only_pending_when_no_step_filters(): void
    {
        $filters = [];

        $result = $this->repository->shouldShowOnlyPending($filters);

        $this->assertTrue($result);
    }

    /** @test */
    public function should_show_only_pending_when_step_is_zero(): void
    {
        $filters = ['step' => 0];

        $result = $this->repository->shouldShowOnlyPending($filters);

        $this->assertTrue($result);
    }

    /** @test */
    public function should_show_only_pending_when_step_is_empty_string(): void
    {
        $filters = ['step' => ''];

        $result = $this->repository->shouldShowOnlyPending($filters);

        $this->assertTrue($result);
    }

    /** @test */
    public function should_not_show_only_pending_when_step_has_value(): void
    {
        $filters = ['step' => 2];

        $result = $this->repository->shouldShowOnlyPending($filters);

        $this->assertFalse($result);
    }

    /** @test */
    public function should_not_show_only_pending_when_invoice_step_has_value(): void
    {
        $filters = ['invoice_step' => 1];

        $result = $this->repository->shouldShowOnlyPending($filters);

        $this->assertFalse($result);
    }

    /** @test */
    public function should_not_show_only_pending_when_both_steps_have_values(): void
    {
        $filters = ['step' => 4, 'invoice_step' => 2];

        $result = $this->repository->shouldShowOnlyPending($filters);

        $this->assertFalse($result);
    }

    /** @test */
    public function should_show_only_pending_when_invoice_step_is_zero(): void
    {
        $filters = ['invoice_step' => 0];

        $result = $this->repository->shouldShowOnlyPending($filters);

        $this->assertTrue($result);
    }

    /** @test */
    public function get_sort_direction_returns_null_for_normal_steps(): void
    {
        $result = $this->repository->getSortDirection(2, 1);

        $this->assertNull($result);
    }

    /** @test */
    public function get_sort_direction_returns_null_for_null_values(): void
    {
        $result = $this->repository->getSortDirection(null, null);

        $this->assertNull($result);
    }

    /** @test */
    public function get_sort_direction_returns_desc_for_step_10(): void
    {
        $result = $this->repository->getSortDirection(10, null);

        $this->assertEquals('desc', $result);
    }

    /** @test */
    public function get_sort_direction_returns_desc_for_invoice_step_3(): void
    {
        $result = $this->repository->getSortDirection(null, 3);

        $this->assertEquals('desc', $result);
    }

    /** @test */
    public function get_sort_direction_returns_desc_when_both_are_closed(): void
    {
        $result = $this->repository->getSortDirection(10, 3);

        $this->assertEquals('desc', $result);
    }

    /** @test */
    public function get_sort_direction_returns_desc_for_step_10_with_other_invoice_step(): void
    {
        $result = $this->repository->getSortDirection(10, 1);

        $this->assertEquals('desc', $result);
    }
}
