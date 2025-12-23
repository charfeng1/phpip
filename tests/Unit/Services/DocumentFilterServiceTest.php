<?php

namespace Tests\Unit\Services;

use App\Services\DocumentFilterService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DocumentFilterService.
 *
 * Tests the filtering logic for document template members including
 * category, language, name, summary, style, event name, and event/task context filters.
 *
 * Note: These are pure unit tests that don't require database access.
 * They verify the filter application logic without executing actual queries.
 */
class DocumentFilterServiceTest extends TestCase
{
    protected DocumentFilterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocumentFilterService;
    }

    public function test_category_filter_returns_correct_oldfilters(): void
    {
        $members = $this->createMockTemplateMember();

        $result = $this->service->filterTemplateMembers($members, ['Category' => 'PAT']);

        $this->assertEquals(['Category' => 'PAT'], $result['oldfilters']);
        $this->assertEquals('documents.select', $result['view']);
    }

    public function test_language_filter_returns_correct_oldfilters(): void
    {
        $members = $this->createMockTemplateMember();

        $result = $this->service->filterTemplateMembers($members, ['Language' => 'en']);

        $this->assertEquals(['Language' => 'en'], $result['oldfilters']);
    }

    public function test_name_filter_returns_correct_oldfilters(): void
    {
        $members = $this->createMockTemplateMember();

        $result = $this->service->filterTemplateMembers($members, ['Name' => 'Test']);

        $this->assertEquals(['Name' => 'Test'], $result['oldfilters']);
    }

    public function test_summary_filter_maps_to_name_in_oldfilters(): void
    {
        $members = $this->createMockTemplateMember();

        $result = $this->service->filterTemplateMembers($members, ['Summary' => 'test']);

        // Note: Original code has a quirk where Summary filter uses 'Name' key
        $this->assertEquals(['Name' => 'test'], $result['oldfilters']);
    }

    public function test_style_filter_returns_correct_oldfilters(): void
    {
        $members = $this->createMockTemplateMember();

        $result = $this->service->filterTemplateMembers($members, ['Style' => 'formal']);

        $this->assertEquals(['Style' => 'formal'], $result['oldfilters']);
    }

    public function test_event_name_filter_changes_view_to_select2(): void
    {
        $members = $this->createMockTemplateMember();

        $result = $this->service->filterTemplateMembers($members, ['EventName' => 'FIL']);

        $this->assertEquals(['EventName' => 'FIL'], $result['oldfilters']);
        $this->assertEquals('documents.select2', $result['view']);
    }

    public function test_empty_filters_returns_default_state(): void
    {
        $members = $this->createMockTemplateMember();

        $result = $this->service->filterTemplateMembers($members, []);

        $this->assertEquals('documents.select', $result['view']);
        $this->assertEmpty($result['oldfilters']);
        $this->assertNull($result['event']);
        $this->assertNull($result['task']);
    }

    public function test_null_and_empty_values_are_ignored(): void
    {
        $members = $this->createMockTemplateMember();

        $result = $this->service->filterTemplateMembers($members, [
            'Category' => '',
            'Language' => null,
            'Name' => '  ',
        ]);

        $this->assertEmpty($result['oldfilters']);
    }

    public function test_unknown_filter_keys_are_ignored_for_security(): void
    {
        $members = $this->createMockTemplateMember();

        $result = $this->service->filterTemplateMembers($members, [
            'malicious_key' => "'; DROP TABLE members; --",
        ]);

        $this->assertEmpty($result['oldfilters']);
    }

    public function test_multiple_filters_are_merged(): void
    {
        $members = $this->createMockTemplateMember();

        $result = $this->service->filterTemplateMembers($members, [
            'Category' => 'PAT',
            'Language' => 'en',
        ]);

        $this->assertEquals([
            'Category' => 'PAT',
            'Language' => 'en',
        ], $result['oldfilters']);
    }

    public function test_event_name_filter_overrides_default_view(): void
    {
        $members = $this->createMockTemplateMember();

        $result = $this->service->filterTemplateMembers($members, [
            'Category' => 'PAT',
            'EventName' => 'FIL',
        ]);

        // EventName should trigger select2 view
        $this->assertEquals('documents.select2', $result['view']);
        $this->assertEquals([
            'Category' => 'PAT',
            'EventName' => 'FIL',
        ], $result['oldfilters']);
    }

    /**
     * Create a mock TemplateMember for testing.
     * We use a mock because we're testing the filter application logic,
     * not the actual database queries.
     */
    protected function createMockTemplateMember()
    {
        return new class {
            public function whereLike($column, $value)
            {
                return $this;
            }

            public function whereHas($relation, $callback)
            {
                return $this;
            }

            public function whereNotExists($callback)
            {
                return $this;
            }

            public function orderBy($column)
            {
                return $this;
            }

            public function get()
            {
                return collect();
            }
        };
    }
}
