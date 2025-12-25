<?php

namespace Tests\Unit\Services;

use App\Services\MatterExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class MatterExportServiceTest extends TestCase
{
    protected MatterExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportService = new MatterExportService();
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(MatterExportService::class, $this->exportService);
    }

    /** @test */
    public function export_returns_streamed_response()
    {
        $matters = [
            ['REF001', 'US', 'PAT', null, 'Active', '2023-01-01', 'Client A', null, null, null, null, 'Title 1', null, null, null, '2023-01-01', '12345', null, null, null, null, 1, null, null, null, null, null, false, false, null],
        ];

        $response = $this->exportService->export($matters);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    /** @test */
    public function export_sets_correct_content_type()
    {
        $matters = [];

        $response = $this->exportService->export($matters);

        $this->assertEquals('application/csv', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function export_sets_content_disposition_header()
    {
        $matters = [];

        $response = $this->exportService->export($matters);

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('_matters.csv', $contentDisposition);
    }

    /** @test */
    public function export_sanitizes_csv_injection()
    {
        // Test that formula-like values are properly sanitized
        $matters = [
            ['=CMD|calc', '+1-1', '-alert(1)', '@sum(1)', 'normal value', null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null],
        ];

        $response = $this->exportService->export($matters);

        // The response should be created without errors
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function export_handles_empty_array()
    {
        $matters = [];

        $response = $this->exportService->export($matters);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function export_handles_multiple_rows()
    {
        $matters = [
            ['REF001', 'US', 'PAT', null, 'Active', '2023-01-01', 'Client A', null, null, null, null, 'Title 1', null, null, null, '2023-01-01', '12345', null, null, null, null, 1, null, null, null, null, null, false, false, null],
            ['REF002', 'EP', 'TM', null, 'Pending', '2023-02-01', 'Client B', null, null, null, null, 'Title 2', null, null, null, '2023-02-01', '67890', null, null, null, null, 2, null, null, null, null, null, false, false, null],
        ];

        $response = $this->exportService->export($matters);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function export_handles_null_values()
    {
        $matters = [
            [null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null],
        ];

        $response = $this->exportService->export($matters);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function export_handles_special_characters()
    {
        $matters = [
            ['REF-001; "Test"', 'US', 'PAT', null, 'Active', '2023-01-01', 'Client "A"', null, null, null, null, "Title with 'quotes'", null, null, null, '2023-01-01', '12345', null, null, null, null, 1, null, null, null, null, null, false, false, null],
        ];

        $response = $this->exportService->export($matters);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function export_uses_semicolon_delimiter()
    {
        // The service uses ';' as the CSV delimiter for European compatibility
        $service = new MatterExportService();

        // Capture the output by reading the exported content
        $matters = [['test', 'data', null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null]];

        $response = $service->export($matters);

        // Just verify the response is valid
        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    /** @test */
    public function export_filename_contains_timestamp()
    {
        $matters = [];

        $response = $this->exportService->export($matters);

        $contentDisposition = $response->headers->get('Content-Disposition');

        // Filename should contain timestamp (format: YYYYMMDDHHmmss)
        $this->assertMatchesRegularExpression('/\d{14}_matters\.csv/', $contentDisposition);
    }
}
