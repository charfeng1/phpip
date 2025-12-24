<?php

namespace Tests\Unit\Services;

use App\Enums\EventCode;
use App\Repositories\TaskRepository;
use App\Services\RenewalFeeCalculatorService;
use App\Services\RenewalLogService;
use App\Services\RenewalNotificationService;
use PHPUnit\Framework\TestCase;

class RenewalNotificationServiceTest extends TestCase
{
    protected RenewalNotificationService $service;

    protected RenewalFeeCalculatorService $feeCalculator;

    protected RenewalLogService $logService;

    protected TaskRepository $taskRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->feeCalculator = new RenewalFeeCalculatorService(145.0, 1.0);
        $this->logService = new RenewalLogService('testuser');
        $this->taskRepository = $this->createMock(TaskRepository::class);

        // Pass all config values through constructor for testing without Laravel
        $this->service = new RenewalNotificationService(
            $this->feeCalculator,
            $this->logService,
            $this->taskRepository,
            0.2, // vatRate
            ['before' => 60, 'before_last' => 30, 'instruct_before' => 45], // validityConfig
            'client' // mailRecipient
        );
    }

    public function test_send_notifications_returns_error_for_empty_ids(): void
    {
        $result = $this->service->sendNotifications([], ['first'], false);

        $this->assertEquals('No renewal selected.', $result);
    }

    public function test_prepare_renewal_data_with_french_language(): void
    {
        $renewal = $this->createRenewalObject([
            'language' => 'fr',
            'due_date' => '2025-06-15',
            'country_FR' => 'France',
            'country_EN' => 'France',
            'detail' => '5',
        ]);

        $result = $this->service->prepareRenewalData($renewal, 0);

        $this->assertEquals('fr', $result['language']);
        $this->assertEquals('France', $result['country']);
        $this->assertEquals(5, $result['annuity']);
        $this->assertEquals('2025-06-15', $result['due_date']);
    }

    public function test_prepare_renewal_data_with_english_language(): void
    {
        $renewal = $this->createRenewalObject([
            'language' => 'en',
            'due_date' => '2025-06-15',
            'country_EN' => 'United States',
            'detail' => '3',
        ]);

        $result = $this->service->prepareRenewalData($renewal, 0);

        $this->assertEquals('en', $result['language']);
        $this->assertEquals('United States', $result['country']);
        $this->assertEquals(3, $result['annuity']);
    }

    public function test_prepare_renewal_data_with_german_language(): void
    {
        $renewal = $this->createRenewalObject([
            'language' => 'de',
            'due_date' => '2025-06-15',
            'country_DE' => 'Deutschland',
            'detail' => '7',
        ]);

        $result = $this->service->prepareRenewalData($renewal, 0);

        $this->assertEquals('de', $result['language']);
        $this->assertEquals('Deutschland', $result['country']);
    }

    public function test_prepare_renewal_data_defaults_to_french(): void
    {
        $renewal = $this->createRenewalObject([
            'language' => null,
            'due_date' => '2025-06-15',
            'country_FR' => 'France',
        ]);

        $result = $this->service->prepareRenewalData($renewal, 0);

        $this->assertEquals('fr', $result['language']);
    }

    public function test_prepare_renewal_data_grace_period_adds_six_months(): void
    {
        $renewal = $this->createRenewalObject([
            'due_date' => '2025-06-15',
        ]);

        $result = $this->service->prepareRenewalData($renewal, 1);

        $this->assertEquals('2025-12-15', $result['due_date']);
    }

    public function test_prepare_renewal_data_calculates_fees(): void
    {
        $renewal = $this->createRenewalObject([
            'table_fee' => true,
            'grace_period' => false,
            'sme_status' => false,
            'cost' => 100.00,
            'fee' => 200.00,
            'discount' => 0,
        ]);

        $result = $this->service->prepareRenewalData($renewal, 0);

        $this->assertEquals('100,00', $result['cost']);
        $this->assertEquals('200,00', $result['fee']);
        $this->assertEquals('300,00', $result['total_ht']);
        // total = fee * (1 + vat_rate) + cost = 200 * 1.2 + 100 = 340
        $this->assertEquals('340,00', $result['total']);
        $this->assertEquals(20.0, $result['vat_rate']);
    }

    public function test_prepare_renewal_data_includes_description(): void
    {
        $renewal = $this->createRenewalObject([
            'uid' => 'PAT-001',
            'number' => 'EP123456',
            'event_name' => EventCode::FILING->value,
            'event_date' => '2020-06-15',
            'client_ref' => 'REF-001',
            'title' => 'Test Patent Title',
        ]);

        $result = $this->service->prepareRenewalData($renewal, 0);

        $this->assertArrayHasKey('desc', $result);
        $this->assertIsString($result['desc']);
        // Description should contain UID and number
        $this->assertStringContainsString('PAT-001', $result['desc']);
        $this->assertStringContainsString('EP123456', $result['desc']);
    }

    public function test_prepare_renewal_data_includes_matter_id(): void
    {
        $renewal = $this->createRenewalObject([
            'matter_id' => 123,
            'caseref' => 'CASE-001',
        ]);

        $result = $this->service->prepareRenewalData($renewal, 0);

        $this->assertEquals(123, $result['matter_id']);
        $this->assertEquals('CASE-001', $result['caseref']);
    }

    public function test_prepare_renewal_data_with_filing_event(): void
    {
        $renewal = $this->createRenewalObject([
            'event_name' => EventCode::FILING->value,
        ]);

        $result = $this->service->prepareRenewalData($renewal, 0);

        $this->assertStringContainsString('filed', $result['desc']);
    }

    public function test_prepare_renewal_data_with_grant_event(): void
    {
        $renewal = $this->createRenewalObject([
            'event_name' => EventCode::GRANT->value,
        ]);

        $result = $this->service->prepareRenewalData($renewal, 0);

        $this->assertStringContainsString('granted', $result['desc']);
    }

    public function test_prepare_renewal_data_with_client_ref(): void
    {
        $renewal = $this->createRenewalObject([
            'client_ref' => 'CLIENT-REF-123',
        ]);

        $result = $this->service->prepareRenewalData($renewal, 0);

        $this->assertStringContainsString('CLIENT-REF-123', $result['desc']);
    }

    public function test_prepare_renewal_data_with_title(): void
    {
        $renewal = $this->createRenewalObject([
            'title' => 'Invention for Testing',
        ]);

        $result = $this->service->prepareRenewalData($renewal, 0);

        $this->assertStringContainsString('Invention for Testing', $result['desc']);
    }

    public function test_prepare_renewal_data_with_sme_discount(): void
    {
        $renewal = $this->createRenewalObject([
            'table_fee' => true,
            'grace_period' => false,
            'sme_status' => true,
            'cost' => 100.00,
            'fee' => 200.00,
            'cost_reduced' => 50.00,
            'fee_reduced' => 100.00,
            'discount' => 0,
        ]);

        $result = $this->service->prepareRenewalData($renewal, 0);

        // SME status should use reduced fees
        $this->assertEquals('50,00', $result['cost']);
        $this->assertEquals('100,00', $result['fee']);
    }

    /**
     * Create a renewal object for testing.
     */
    protected function createRenewalObject(array $overrides = []): object
    {
        $defaults = [
            'id' => 1,
            'matter_id' => 100,
            'caseref' => 'CASE-001',
            'uid' => 'PAT-001',
            'number' => 'EP123456',
            'language' => 'fr',
            'due_date' => '2025-06-15',
            'done_date' => null,
            'event_name' => EventCode::FILING->value,
            'event_date' => '2020-06-15',
            'country_FR' => 'France',
            'country_EN' => 'France',
            'country_DE' => 'Frankreich',
            'client_ref' => '',
            'title' => '',
            'short_title' => '',
            'detail' => '5',
            'table_fee' => true,
            'grace_period' => false,
            'sme_status' => false,
            'cost' => 100.00,
            'fee' => 200.00,
            'cost_reduced' => 80.00,
            'fee_reduced' => 160.00,
            'cost_sup' => 150.00,
            'fee_sup' => 300.00,
            'cost_sup_reduced' => 120.00,
            'fee_sup_reduced' => 240.00,
            'discount' => 0,
            'step' => 0,
            'client_id' => 1,
        ];

        return (object) array_merge($defaults, $overrides);
    }
}
