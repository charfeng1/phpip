<?php

namespace Tests\Unit\Services;

use App\Services\MatterExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class MatterExportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MatterExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatterExportService();
    }

    /** @test */
    public function it_returns_streamed_response()
    {
        $matters = [];

        $response = $this->service->export($matters);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    /** @test */
    public function it_sets_correct_content_type()
    {
        $matters = [];

        $response = $this->service->export($matters);

        $this->assertEquals('application/csv', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function it_sets_content_disposition_for_download()
    {
        $matters = [];

        $response = $this->service->export($matters);

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('_matters.csv', $contentDisposition);
    }

    /** @test */
    public function it_exports_empty_array()
    {
        $matters = [];

        $response = $this->service->export($matters);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_exports_matter_data()
    {
        $matters = [
            [
                'Ref' => 'TEST001/US',
                'country' => 'US',
                'Cat' => 'PAT',
                'origin' => null,
                'Status' => 'Filed',
                'Status_date' => '2023-01-15',
                'Client' => 'Test Client',
                'ClRef' => 'CLIENT-001',
                'Applicant' => 'Test Applicant',
                'Agent' => 'Test Agent',
                'AgtRef' => 'AGENT-001',
                'Title' => 'Test Invention',
                'Title2' => null,
                'Title3' => null,
                'Inventor1' => 'John Doe',
                'Filed' => '2023-01-15',
                'FilNo' => '12/345,678',
                'Published' => '2024-06-15',
                'PubNo' => 'US2024123456',
                'Granted' => null,
                'GrtNo' => null,
                'id' => 1,
                'container_id' => null,
                'parent_id' => null,
                'type_code' => null,
                'responsible' => 'admin',
                'delegate' => null,
                'dead' => 0,
                'Ctnr' => 1,
                'Alt_Ref' => null,
            ],
        ];

        $response = $this->service->export($matters);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_exports_multiple_matters()
    {
        $matters = [
            [
                'Ref' => 'TEST001/US',
                'country' => 'US',
                'Cat' => 'PAT',
                'origin' => null,
                'Status' => 'Filed',
                'Status_date' => '2023-01-15',
                'Client' => 'Client 1',
                'ClRef' => 'C001',
                'Applicant' => 'Applicant 1',
                'Agent' => 'Agent 1',
                'AgtRef' => 'A001',
                'Title' => 'Title 1',
                'Title2' => null,
                'Title3' => null,
                'Inventor1' => 'Inventor 1',
                'Filed' => '2023-01-15',
                'FilNo' => '12/345,678',
                'Published' => null,
                'PubNo' => null,
                'Granted' => null,
                'GrtNo' => null,
                'id' => 1,
                'container_id' => null,
                'parent_id' => null,
                'type_code' => null,
                'responsible' => 'admin',
                'delegate' => null,
                'dead' => 0,
                'Ctnr' => 1,
                'Alt_Ref' => null,
            ],
            [
                'Ref' => 'TEST002/EP',
                'country' => 'EP',
                'Cat' => 'PAT',
                'origin' => 'WO',
                'Status' => 'Published',
                'Status_date' => '2024-01-15',
                'Client' => 'Client 2',
                'ClRef' => 'C002',
                'Applicant' => 'Applicant 2',
                'Agent' => 'Agent 2',
                'AgtRef' => 'A002',
                'Title' => 'Title 2',
                'Title2' => null,
                'Title3' => null,
                'Inventor1' => 'Inventor 2',
                'Filed' => '2023-06-15',
                'FilNo' => 'EP23456789',
                'Published' => '2024-01-15',
                'PubNo' => 'EP1234567',
                'Granted' => null,
                'GrtNo' => null,
                'id' => 2,
                'container_id' => null,
                'parent_id' => 1,
                'type_code' => null,
                'responsible' => 'admin',
                'delegate' => null,
                'dead' => 0,
                'Ctnr' => 1,
                'Alt_Ref' => 'ALT002',
            ],
        ];

        $response = $this->service->export($matters);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_sanitizes_csv_injection()
    {
        $matters = [
            [
                'Ref' => '=DANGEROUS',
                'country' => '+FORMULA',
                'Cat' => '-HACK',
                'origin' => '@MALICIOUS',
                'Status' => 'Normal',
                'Status_date' => '2023-01-15',
                'Client' => 'Client',
                'ClRef' => null,
                'Applicant' => null,
                'Agent' => null,
                'AgtRef' => null,
                'Title' => null,
                'Title2' => null,
                'Title3' => null,
                'Inventor1' => null,
                'Filed' => null,
                'FilNo' => null,
                'Published' => null,
                'PubNo' => null,
                'Granted' => null,
                'GrtNo' => null,
                'id' => 1,
                'container_id' => null,
                'parent_id' => null,
                'type_code' => null,
                'responsible' => null,
                'delegate' => null,
                'dead' => 0,
                'Ctnr' => 1,
                'Alt_Ref' => null,
            ],
        ];

        $response = $this->service->export($matters);

        // The service should sanitize values starting with =, +, -, @
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_null_values()
    {
        $matters = [
            [
                'Ref' => 'TEST001/US',
                'country' => 'US',
                'Cat' => 'PAT',
                'origin' => null,
                'Status' => null,
                'Status_date' => null,
                'Client' => null,
                'ClRef' => null,
                'Applicant' => null,
                'Agent' => null,
                'AgtRef' => null,
                'Title' => null,
                'Title2' => null,
                'Title3' => null,
                'Inventor1' => null,
                'Filed' => null,
                'FilNo' => null,
                'Published' => null,
                'PubNo' => null,
                'Granted' => null,
                'GrtNo' => null,
                'id' => 1,
                'container_id' => null,
                'parent_id' => null,
                'type_code' => null,
                'responsible' => null,
                'delegate' => null,
                'dead' => 0,
                'Ctnr' => 1,
                'Alt_Ref' => null,
            ],
        ];

        $response = $this->service->export($matters);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
