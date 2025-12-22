<?php

namespace Tests\Unit\Services;

use App\Services\RenewalFeeCalculatorService;
use PHPUnit\Framework\TestCase;

class RenewalFeeCalculatorServiceTest extends TestCase
{
    protected RenewalFeeCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Pass default values directly to avoid needing Laravel's config()
        $this->service = new RenewalFeeCalculatorService(145.0, 1.0);
    }

    public function test_calculate_from_table_normal_period_standard_rate(): void
    {
        $renewal = (object) [
            'table_fee' => true,
            'grace_period' => false,
            'sme_status' => false,
            'cost' => 100.00,
            'fee' => 200.00,
            'discount' => 0,
            'done_date' => null,
            'due_date' => '2025-12-31',
        ];

        $result = $this->service->calculate($renewal);

        $this->assertEquals(100.00, $result['cost']);
        $this->assertEquals(200.00, $result['fee']);
    }

    public function test_calculate_from_table_normal_period_sme_rate(): void
    {
        $renewal = (object) [
            'table_fee' => true,
            'grace_period' => false,
            'sme_status' => true,
            'cost' => 100.00,
            'fee' => 200.00,
            'cost_reduced' => 80.00,
            'fee_reduced' => 160.00,
            'discount' => 0,
            'done_date' => null,
            'due_date' => '2025-12-31',
        ];

        $result = $this->service->calculate($renewal);

        $this->assertEquals(80.00, $result['cost']);
        $this->assertEquals(160.00, $result['fee']);
    }

    public function test_calculate_from_table_grace_period_standard_rate(): void
    {
        $renewal = (object) [
            'table_fee' => true,
            'grace_period' => true,
            'sme_status' => false,
            'cost_sup' => 150.00,
            'fee_sup' => 250.00,
            'discount' => 0,
            'done_date' => null,
            'due_date' => '2025-12-31',
        ];

        $result = $this->service->calculate($renewal);

        $this->assertEquals(150.00, $result['cost']);
        $this->assertEquals(250.00, $result['fee']);
    }

    public function test_calculate_from_table_grace_period_sme_rate(): void
    {
        $renewal = (object) [
            'table_fee' => true,
            'grace_period' => true,
            'sme_status' => true,
            'cost_sup_reduced' => 120.00,
            'fee_sup_reduced' => 200.00,
            'discount' => 0,
            'done_date' => null,
            'due_date' => '2025-12-31',
        ];

        $result = $this->service->calculate($renewal);

        $this->assertEquals(120.00, $result['cost']);
        $this->assertEquals(200.00, $result['fee']);
    }

    public function test_calculate_from_table_with_percentage_discount(): void
    {
        $renewal = (object) [
            'table_fee' => true,
            'grace_period' => false,
            'sme_status' => false,
            'cost' => 100.00,
            'fee' => 200.00,
            'discount' => 0.1, // 10% discount
            'done_date' => null,
            'due_date' => '2025-12-31',
        ];

        $result = $this->service->calculate($renewal);

        $this->assertEquals(100.00, $result['cost']);
        $this->assertEquals(180.00, $result['fee']); // 200 * 0.9
    }

    public function test_calculate_from_table_with_absolute_discount(): void
    {
        $renewal = (object) [
            'table_fee' => true,
            'grace_period' => false,
            'sme_status' => false,
            'cost' => 100.00,
            'fee' => 200.00,
            'discount' => 150.00, // Absolute override
            'done_date' => null,
            'due_date' => '2025-12-31',
        ];

        $result = $this->service->calculate($renewal);

        $this->assertEquals(100.00, $result['cost']);
        $this->assertEquals(150.00, $result['fee']); // Override to 150
    }

    public function test_calculate_from_task_when_no_table_fee(): void
    {
        $renewal = (object) [
            'table_fee' => false,
            'grace_period' => false,
            'cost' => 50.00,
            'fee' => 200.00,
            'discount' => 0,
            'done_date' => null,
            'due_date' => '2025-12-31',
        ];

        $result = $this->service->calculate($renewal);

        $this->assertEquals(50.00, $result['cost']);
        // fee = (200 - 145) + (1.0 - 0) * 145 = 55 + 145 = 200
        $this->assertEquals(200.00, $result['fee']);
    }

    public function test_calculate_from_task_with_percentage_discount(): void
    {
        $renewal = (object) [
            'table_fee' => false,
            'grace_period' => false,
            'cost' => 50.00,
            'fee' => 200.00,
            'discount' => 0.2, // 20% discount
            'done_date' => null,
            'due_date' => '2025-12-31',
        ];

        $result = $this->service->calculate($renewal);

        $this->assertEquals(50.00, $result['cost']);
        // fee = (200 - 145) + (1.0 - 0.2) * 145 = 55 + 116 = 171
        $this->assertEquals(171.00, $result['fee']);
    }

    public function test_apply_discount_percentage(): void
    {
        $fee = 200.00;
        $discount = 0.1; // 10%

        $result = $this->service->applyDiscount($fee, $discount);

        $this->assertEquals(180.00, $result);
    }

    public function test_apply_discount_absolute_override(): void
    {
        $fee = 200.00;
        $discount = 50.00; // Absolute override (>1)

        $result = $this->service->applyDiscount($fee, $discount);

        $this->assertEquals(50.00, $result);
    }

    public function test_apply_discount_zero(): void
    {
        $fee = 200.00;
        $discount = 0;

        $result = $this->service->applyDiscount($fee, $discount);

        $this->assertEquals(200.00, $result);
    }

    public function test_get_grace_period_factor_returns_one_when_not_in_grace(): void
    {
        $renewal = (object) [
            'grace_period' => false,
            'done_date' => null,
            'due_date' => '2025-12-31',
        ];

        $factor = $this->service->getGracePeriodFactor($renewal);

        $this->assertEquals(1.0, $factor);
    }

    public function test_get_default_fee(): void
    {
        $defaultFee = $this->service->getDefaultFee();

        $this->assertEquals(145.0, $defaultFee);
    }
}
