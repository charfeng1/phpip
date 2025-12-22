<?php

namespace Tests\Unit\Services;

use App\Enums\EventCode;
use App\Services\DolibarrInvoiceService;
use App\Services\RenewalFeeCalculatorService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class DolibarrInvoiceServiceTest extends TestCase
{
    protected DolibarrInvoiceService $service;

    protected RenewalFeeCalculatorService $feeCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        // Create fee calculator with default values for testing
        $this->feeCalculator = new RenewalFeeCalculatorService(145.0, 1.0);
        // Pass config values directly to avoid needing Laravel's config()
        $this->service = new DolibarrInvoiceService(
            $this->feeCalculator,
            'test-api-key',
            'https://dolibarr.example.com/api',
            1
        );
    }

    public function test_determine_vat_rate_french_client(): void
    {
        // French clients get 20% VAT
        $this->assertEquals(0.2, $this->service->determineVatRate('FR12345678901'));
        $this->assertEquals(0.2, $this->service->determineVatRate(''));
        $this->assertEquals(0.2, $this->service->determineVatRate(null));
    }

    public function test_determine_vat_rate_eu_client(): void
    {
        // EU clients outside France get 0% VAT
        $this->assertEquals(0.0, $this->service->determineVatRate('DE123456789'));
        $this->assertEquals(0.0, $this->service->determineVatRate('BE0123456789'));
        $this->assertEquals(0.0, $this->service->determineVatRate('NL123456789B01'));
    }

    public function test_build_line_description_with_filing_event(): void
    {
        $renewal = (object) [
            'uid' => 'PAT-001',
            'detail' => '5',
            'number' => 'EP123456',
            'event_name' => EventCode::FILING->value,
            'event_date' => '2020-06-15',
            'country_FR' => 'France',
            'title' => 'Test Patent',
            'client_ref' => 'REF-001',
            'due_date' => '2025-06-15',
        ];

        $desc = $this->service->buildLineDescription($renewal);

        $this->assertStringContainsString('PAT-001', $desc);
        $this->assertStringContainsString("l'année 5", $desc);
        $this->assertStringContainsString('EP123456', $desc);
        $this->assertStringContainsString('déposé le', $desc);
        $this->assertStringContainsString('France', $desc);
        $this->assertStringContainsString('Test Patent', $desc);
        $this->assertStringContainsString('REF-001', $desc);
    }

    public function test_build_line_description_with_grant_event(): void
    {
        $renewal = (object) [
            'uid' => 'PAT-002',
            'detail' => '10',
            'number' => 'EP789012',
            'event_name' => EventCode::GRANT->value,
            'event_date' => '2015-03-20',
            'country_FR' => 'Allemagne',
            'title' => '',
            'client_ref' => '',
            'due_date' => '2025-03-20',
        ];

        $desc = $this->service->buildLineDescription($renewal);

        $this->assertStringContainsString('délivré le', $desc);
        $this->assertStringNotContainsString('Sujet', $desc);
    }

    public function test_build_invoice_lines_with_cost_and_fee(): void
    {
        $renewals = new Collection([
            (object) [
                'uid' => 'PAT-001',
                'detail' => '5',
                'number' => 'EP123456',
                'event_name' => EventCode::FILING->value,
                'event_date' => '2020-06-15',
                'country_FR' => 'France',
                'title' => 'Test',
                'client_ref' => '',
                'due_date' => '2025-06-15',
                'table_fee' => true,
                'grace_period' => false,
                'sme_status' => false,
                'cost' => 100.00,
                'fee' => 200.00,
                'discount' => 0,
                'done_date' => null,
            ],
        ]);

        $lines = $this->service->buildInvoiceLines($renewals, 0.2);

        // Should have 2 lines: fee + cost
        $this->assertCount(2, $lines);

        // First line is the fee
        $this->assertEquals(200.00, $lines[0]['subprice']);
        $this->assertEquals(20.0, $lines[0]['tva_tx']); // 20% VAT
        $this->assertEquals(40.00, $lines[0]['total_tva']);
        $this->assertEquals(240.00, $lines[0]['total_ttc']);

        // Second line is the cost (no VAT)
        $this->assertEquals(100.00, $lines[1]['subprice']);
        $this->assertEquals(0.0, $lines[1]['tva_tx']);
        $this->assertEquals('Taxe', $lines[1]['desc']);
    }

    public function test_build_invoice_lines_without_cost(): void
    {
        $renewals = new Collection([
            (object) [
                'uid' => 'PAT-001',
                'detail' => '5',
                'number' => 'EP123456',
                'event_name' => EventCode::FILING->value,
                'event_date' => '2020-06-15',
                'country_FR' => 'France',
                'title' => '',
                'client_ref' => '',
                'due_date' => '2025-06-15',
                'table_fee' => true,
                'grace_period' => false,
                'sme_status' => false,
                'cost' => 0,
                'fee' => 200.00,
                'discount' => 0,
                'done_date' => null,
            ],
        ]);

        $lines = $this->service->buildInvoiceLines($renewals, 0.0);

        // Should have only 1 line when cost is 0
        $this->assertCount(1, $lines);
        $this->assertStringContainsString('Honoraires et taxe', $lines[0]['desc']);
    }

    public function test_build_invoice_data(): void
    {
        $lines = [
            ['desc' => 'Test line', 'subprice' => 100],
        ];

        $invoiceData = $this->service->buildInvoiceData(123, $lines);

        $this->assertEquals(123, $invoiceData['socid']);
        $this->assertEquals(1, $invoiceData['cond_reglement_id']);
        $this->assertEquals(2, $invoiceData['mode_reglement_id']);
        $this->assertEquals($lines, $invoiceData['lines']);
        $this->assertArrayHasKey('date', $invoiceData);
        $this->assertArrayHasKey('fk_account', $invoiceData);
    }

    public function test_create_invoices_for_renewals_empty_collection(): void
    {
        $renewals = new Collection([]);

        $result = $this->service->createInvoicesForRenewals($renewals);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['count']);
        $this->assertEquals('No renewal selected.', $result['error']);
    }
}
