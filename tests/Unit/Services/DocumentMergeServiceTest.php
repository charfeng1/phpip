<?php

namespace Tests\Unit\Services;

use App\Models\Event;
use App\Models\Matter;
use App\Services\DocumentMergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentMergeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentMergeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->service = new DocumentMergeService();
    }

    /** @test */
    public function it_can_set_matter()
    {
        $matter = Matter::factory()->create();

        $result = $this->service->setMatter($matter);

        $this->assertInstanceOf(DocumentMergeService::class, $result);
    }

    /** @test */
    public function it_returns_itself_for_chaining()
    {
        $matter = Matter::factory()->create();

        $result = $this->service->setMatter($matter);

        $this->assertSame($this->service, $result);
    }

    /** @test */
    public function collect_data_includes_matter_reference()
    {
        $matter = Matter::factory()->create([
            'caseref' => 'TEST001',
            'country' => 'US',
        ]);
        $matter->uid = 'TEST001/US';
        $matter->save();

        Event::factory()->filing()->forMatter($matter)->create();

        $this->service->setMatter($matter);

        // We need to use reflection to access private method or test via merge
        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('collectData');
        $method->setAccessible(true);

        $data = $method->invoke($this->service);

        $this->assertArrayHasKey('simple', $data);
        $this->assertArrayHasKey('complex', $data);
        $this->assertEquals('TEST001/US', $data['simple']['File_Ref']);
    }

    /** @test */
    public function collect_data_includes_country()
    {
        $matter = Matter::factory()->inCountry('US')->create();
        Event::factory()->filing()->forMatter($matter)->create();

        $this->service->setMatter($matter);

        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('collectData');
        $method->setAccessible(true);

        $data = $method->invoke($this->service);

        $this->assertEquals('US', $data['simple']['Country']);
    }

    /** @test */
    public function collect_data_includes_category()
    {
        $matter = Matter::factory()->patent()->create();
        Event::factory()->filing()->forMatter($matter)->create();

        $this->service->setMatter($matter);

        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('collectData');
        $method->setAccessible(true);

        $data = $method->invoke($this->service);

        $this->assertEquals('PAT', $data['simple']['File_Category']);
    }

    /** @test */
    public function collect_data_includes_filing_details()
    {
        $matter = Matter::factory()->create();
        Event::factory()->filing()->forMatter($matter)->create([
            'event_date' => '2023-01-15',
            'detail' => '12/345,678',
        ]);

        $this->service->setMatter($matter);

        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('collectData');
        $method->setAccessible(true);

        $data = $method->invoke($this->service);

        $this->assertArrayHasKey('Filing_Date', $data['simple']);
        $this->assertEquals('12/345,678', $data['simple']['Filing_Number']);
    }

    /** @test */
    public function collect_data_includes_publication_details()
    {
        $matter = Matter::factory()->create();
        Event::factory()->filing()->forMatter($matter)->create();
        Event::factory()->publication()->forMatter($matter)->create([
            'event_date' => '2024-06-15',
            'detail' => 'US2024123456',
        ]);

        $this->service->setMatter($matter);

        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('collectData');
        $method->setAccessible(true);

        $data = $method->invoke($this->service);

        $this->assertArrayHasKey('Pub_Date', $data['simple']);
        $this->assertEquals('US2024123456', $data['simple']['Pub_Number']);
    }

    /** @test */
    public function collect_data_includes_grant_details()
    {
        $matter = Matter::factory()->create();
        Event::factory()->filing()->forMatter($matter)->create();
        Event::factory()->grant()->forMatter($matter)->create([
            'event_date' => '2024-12-15',
            'detail' => 'US12,345,678',
        ]);

        $this->service->setMatter($matter);

        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('collectData');
        $method->setAccessible(true);

        $data = $method->invoke($this->service);

        $this->assertArrayHasKey('Grant_Date', $data['simple']);
        $this->assertEquals('US12,345,678', $data['simple']['Grant_Number']);
    }

    /** @test */
    public function collect_data_includes_alt_ref()
    {
        $matter = Matter::factory()->create(['alt_ref' => 'CLIENT-REF-001']);
        Event::factory()->filing()->forMatter($matter)->create();

        $this->service->setMatter($matter);

        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('collectData');
        $method->setAccessible(true);

        $data = $method->invoke($this->service);

        $this->assertEquals('CLIENT-REF-001', $data['simple']['Alt_Ref']);
    }

    /** @test */
    public function collect_data_includes_expiration_date()
    {
        $matter = Matter::factory()->create(['expire_date' => '2040-01-15']);
        Event::factory()->filing()->forMatter($matter)->create();

        $this->service->setMatter($matter);

        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('collectData');
        $method->setAccessible(true);

        $data = $method->invoke($this->service);

        $this->assertEquals('2040-01-15', $data['simple']['Expiration_Date']);
    }

    /** @test */
    public function complex_data_includes_billing_address()
    {
        $matter = Matter::factory()->create();
        Event::factory()->filing()->forMatter($matter)->create();

        $this->service->setMatter($matter);

        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('collectData');
        $method->setAccessible(true);

        $data = $method->invoke($this->service);

        $this->assertArrayHasKey('Billing_Address', $data['complex']);
    }

    /** @test */
    public function complex_data_includes_inventor_addresses()
    {
        $matter = Matter::factory()->create();
        Event::factory()->filing()->forMatter($matter)->create();

        $this->service->setMatter($matter);

        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('collectData');
        $method->setAccessible(true);

        $data = $method->invoke($this->service);

        $this->assertArrayHasKey('Inventor_Addresses', $data['complex']);
    }
}
