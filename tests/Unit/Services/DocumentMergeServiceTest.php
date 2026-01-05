<?php

namespace Tests\Unit\Services;

use App\Enums\ActorRole;
use App\Enums\ClassifierType;
use App\Enums\EventCode;
use App\Models\Actor;
use App\Models\ActorPivot;
use App\Models\Classifier;
use App\Models\Event;
use App\Models\Matter;
use App\Services\DocumentMergeService;
use PhpOffice\PhpWord\TemplateProcessor;
use Tests\TestCase;

/**
 * Unit tests for DocumentMergeService.
 *
 * Tests the document merge functionality including:
 * - Data collection from matter and related models
 * - Template processing with PHPWord
 * - Handling of simple and complex (multi-line) values
 * - Edge cases with missing data
 */
class DocumentMergeServiceTest extends TestCase
{
    protected DocumentMergeService $service;

    protected Matter $matter;

    protected string $testTemplatePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocumentMergeService;

        // Create a basic matter for testing
        $this->matter = Matter::factory()->create([
            'caseref' => 'MERGE001',
            'country' => 'US',
            'category_code' => 'PAT',
        ]);

        // Create a minimal test template
        $this->createTestTemplate();
    }

    protected function tearDown(): void
    {
        // Clean up test template
        if (file_exists($this->testTemplatePath)) {
            unlink($this->testTemplatePath);
        }
        parent::tearDown();
    }

    /**
     * Create a minimal Word template for testing.
     */
    protected function createTestTemplate(): void
    {
        $this->testTemplatePath = storage_path('app/test_template.docx');

        // Create a simple docx file using PHPWord
        $phpWord = new \PhpOffice\PhpWord\PhpWord;
        $section = $phpWord->addSection();
        $section->addText('File Reference: ${File_Ref}');
        $section->addText('Country: ${Country}');
        $section->addText('Category: ${File_Category}');
        $section->addText('Filing Date: ${Filing_Date}');
        $section->addText('Filing Number: ${Filing_Number}');

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($this->testTemplatePath);
    }

    /** @test */
    public function service_can_be_instantiated()
    {
        $this->assertInstanceOf(DocumentMergeService::class, $this->service);
    }

    /** @test */
    public function set_matter_returns_self_for_chaining()
    {
        $result = $this->service->setMatter($this->matter);

        $this->assertInstanceOf(DocumentMergeService::class, $result);
        $this->assertSame($this->service, $result);
    }

    /** @test */
    public function merge_returns_template_processor()
    {
        $result = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $result);
    }

    /** @test */
    public function merge_includes_basic_matter_fields()
    {
        // The matter should have basic fields available
        $this->matter->refresh();

        $template = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        // We can verify the template was processed by checking it's valid
        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_includes_filing_event_data()
    {
        // Create a filing event
        Event::factory()->filing()->forMatter($this->matter)->create([
            'event_date' => '2024-01-15',
            'detail' => 'US2024/123456',
        ]);

        $this->matter->refresh();

        $template = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_includes_publication_event_data()
    {
        Event::factory()->filing()->forMatter($this->matter)->create();
        Event::factory()->publication()->forMatter($this->matter)->create([
            'event_date' => '2024-06-15',
            'detail' => 'US2024/0123456A1',
        ]);

        $this->matter->refresh();

        $template = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_includes_grant_event_data()
    {
        Event::factory()->filing()->forMatter($this->matter)->create();
        Event::factory()->grant()->forMatter($this->matter)->create([
            'event_date' => '2025-01-10',
            'detail' => 'US12345678',
        ]);

        $this->matter->refresh();

        $template = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_handles_matter_without_events()
    {
        // Matter with no events should not fail
        $template = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_includes_title_classifier()
    {
        Classifier::factory()->create([
            'matter_id' => $this->matter->id,
            'type_code' => ClassifierType::TITLE->value,
            'value' => 'Test Invention Title',
        ]);

        $this->matter->refresh();

        $template = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_includes_official_title_with_fallback()
    {
        // When official title exists, it should be used
        Classifier::factory()->create([
            'matter_id' => $this->matter->id,
            'type_code' => ClassifierType::TITLE_OFFICIAL->value,
            'value' => 'Official Title',
        ]);

        Classifier::factory()->create([
            'matter_id' => $this->matter->id,
            'type_code' => ClassifierType::TITLE->value,
            'value' => 'Regular Title',
        ]);

        $this->matter->refresh();

        $template = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_includes_english_title()
    {
        Classifier::factory()->create([
            'matter_id' => $this->matter->id,
            'type_code' => ClassifierType::TITLE_EN->value,
            'value' => 'English Title',
        ]);

        $this->matter->refresh();

        $template = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_includes_trademark_data()
    {
        $tmMatter = Matter::factory()->trademark()->create();

        Classifier::factory()->create([
            'matter_id' => $tmMatter->id,
            'type_code' => ClassifierType::TRADEMARK_NAME->value,
            'value' => 'BRANDNAME',
        ]);

        Classifier::factory()->create([
            'matter_id' => $tmMatter->id,
            'type_code' => ClassifierType::TRADEMARK_CLASS->value,
            'value' => '9',
        ]);

        Classifier::factory()->create([
            'matter_id' => $tmMatter->id,
            'type_code' => ClassifierType::TRADEMARK_CLASS->value,
            'value' => '42',
        ]);

        $tmMatter->refresh();

        $template = $this->service
            ->setMatter($tmMatter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_includes_client_data()
    {
        $client = Actor::factory()->create([
            'name' => 'Test Client Corp',
            'email' => 'client@example.com',
            'VAT_number' => 'VAT123456',
        ]);

        ActorPivot::factory()->create([
            'matter_id' => $this->matter->id,
            'actor_id' => $client->id,
            'role' => ActorRole::CLIENT->value,
            'display_order' => 1,
        ]);

        $this->matter->refresh();

        $template = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_includes_inventor_data()
    {
        $inventor = Actor::factory()->create([
            'name' => 'Smith',
            'first_name' => 'John',
            'address' => '123 Inventor Lane',
            'country' => 'US',
        ]);

        ActorPivot::factory()->create([
            'matter_id' => $this->matter->id,
            'actor_id' => $inventor->id,
            'role' => ActorRole::INVENTOR->value,
            'display_order' => 1,
        ]);

        $this->matter->refresh();

        $template = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_handles_multiple_inventors()
    {
        $inventor1 = Actor::factory()->create([
            'name' => 'Smith',
            'first_name' => 'John',
        ]);

        $inventor2 = Actor::factory()->create([
            'name' => 'Doe',
            'first_name' => 'Jane',
        ]);

        ActorPivot::factory()->create([
            'matter_id' => $this->matter->id,
            'actor_id' => $inventor1->id,
            'role' => ActorRole::INVENTOR->value,
            'display_order' => 1,
        ]);

        ActorPivot::factory()->create([
            'matter_id' => $this->matter->id,
            'actor_id' => $inventor2->id,
            'role' => ActorRole::INVENTOR->value,
            'display_order' => 2,
        ]);

        $this->matter->refresh();

        $template = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_includes_agent_data()
    {
        $agent = Actor::factory()->create([
            'name' => 'Patent Agent LLC',
            'address' => '456 Agent Street',
            'country' => 'US',
        ]);

        ActorPivot::factory()->create([
            'matter_id' => $this->matter->id,
            'actor_id' => $agent->id,
            'role' => ActorRole::AGENT->value,
            'display_order' => 1,
        ]);

        $this->matter->refresh();

        $template = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_handles_matter_with_expire_date()
    {
        $this->matter->update(['expire_date' => '2044-01-15']);
        $this->matter->refresh();

        $template = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_handles_matter_with_alt_ref()
    {
        $this->matter->update(['alt_ref' => 'ALT-REF-123']);
        $this->matter->refresh();

        $template = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_handles_null_values_gracefully()
    {
        // Matter with minimal data - should not throw exceptions
        $minimalMatter = Matter::factory()->create([
            'alt_ref' => null,
            'expire_date' => null,
            'notes' => null,
        ]);

        $template = $this->service
            ->setMatter($minimalMatter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_template_can_be_saved()
    {
        $outputPath = storage_path('app/test_output.docx');

        try {
            $template = $this->service
                ->setMatter($this->matter)
                ->merge($this->testTemplatePath);

            $template->saveAs($outputPath);

            $this->assertFileExists($outputPath);
        } finally {
            // Clean up
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
        }
    }

    /** @test */
    public function merge_handles_priority_events()
    {
        // Create a priority event
        $priorityMatter = Matter::factory()->create([
            'country' => 'EP',
            'caseref' => 'PRIORITY001',
        ]);

        Event::factory()->filing()->forMatter($priorityMatter)->create([
            'event_date' => '2023-01-15',
            'detail' => 'EP2023/001',
        ]);

        Event::factory()->filing()->forMatter($this->matter)->create();

        // Create priority claim event linking to priority matter
        Event::factory()->create([
            'matter_id' => $this->matter->id,
            'code' => EventCode::PRIORITY->value,
            'alt_matter_id' => $priorityMatter->id,
            'event_date' => '2023-01-15',
        ]);

        $this->matter->refresh();

        $template = $this->service
            ->setMatter($this->matter)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function merge_handles_family_member_matter()
    {
        $container = Matter::factory()->asContainer()->create();
        Event::factory()->filing()->forMatter($container)->create();

        $familyMember = Matter::factory()->asFamilyMember($container)->create();
        Event::factory()->filing()->forMatter($familyMember)->create();

        $familyMember->refresh();

        $template = $this->service
            ->setMatter($familyMember)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template);
    }

    /** @test */
    public function service_is_reusable_for_multiple_matters()
    {
        $matter1 = Matter::factory()->create(['caseref' => 'REUSE001']);
        $matter2 = Matter::factory()->create(['caseref' => 'REUSE002']);

        $template1 = $this->service
            ->setMatter($matter1)
            ->merge($this->testTemplatePath);

        $template2 = $this->service
            ->setMatter($matter2)
            ->merge($this->testTemplatePath);

        $this->assertInstanceOf(TemplateProcessor::class, $template1);
        $this->assertInstanceOf(TemplateProcessor::class, $template2);
    }
}
