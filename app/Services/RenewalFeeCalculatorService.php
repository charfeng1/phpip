<?php

namespace App\Services;

/**
 * Calculates renewal fees based on fee tables, grace periods, SME status, and discounts.
 *
 * This service centralizes all fee calculation logic for patent/trademark renewals,
 * supporting both table-based fees (from fee schedules) and task-based fees (defaults).
 */
class RenewalFeeCalculatorService
{
    /**
     * Default fee when no fee table entry exists.
     */
    protected float $defaultFee;

    /**
     * Fee multiplier for late payments (grace period).
     */
    protected float $gracePeriodFactor;

    public function __construct(?float $defaultFee = null, ?float $gracePeriodFactor = null)
    {
        $this->defaultFee = $defaultFee ?? (float) config('renewal.invoice.default_fee', 145);
        $this->gracePeriodFactor = $gracePeriodFactor ?? (float) config('renewal.validity.fee_factor', 1.0);
    }

    /**
     * Calculate the cost and fee for a renewal.
     *
     * @param  object  $renewal  The renewal task object with fee-related properties
     * @return array{cost: float, fee: float} Calculated cost and fee
     */
    public function calculate(object $renewal): array
    {
        $feeFactor = $this->getGracePeriodFactor($renewal);

        if ($renewal->table_fee) {
            $result = $this->calculateFromTable($renewal);
        } else {
            $result = $this->calculateFromTask($renewal);
        }

        // Apply grace period factor to fee
        $result['fee'] *= $feeFactor;

        return $result;
    }

    /**
     * Calculate fees from the fee table based on grace period and SME status.
     *
     * @param  object  $renewal  The renewal task object
     * @return array{cost: float, fee: float} Calculated cost and fee
     */
    public function calculateFromTable(object $renewal): array
    {
        if ($renewal->grace_period) {
            $cost = $renewal->sme_status ? $renewal->cost_sup_reduced : $renewal->cost_sup;
            $fee = $renewal->sme_status ? $renewal->fee_sup_reduced : $renewal->fee_sup;
        } else {
            $cost = $renewal->sme_status ? $renewal->cost_reduced : $renewal->cost;
            $fee = $renewal->sme_status ? $renewal->fee_reduced : $renewal->fee;
        }

        $fee = $this->applyDiscount((float) $fee, (float) ($renewal->discount ?? 0));

        return [
            'cost' => (float) $cost,
            'fee' => $fee,
        ];
    }

    /**
     * Calculate fees from task data when no fee table entry exists.
     *
     * @param  object  $renewal  The renewal task object
     * @return array{cost: float, fee: float} Calculated cost and fee
     */
    public function calculateFromTask(object $renewal): array
    {
        $cost = (float) $renewal->cost;
        $fee = (float) $renewal->fee - $this->defaultFee;

        // Apply discount differently for task-based fees
        $discount = (float) ($renewal->discount ?? 0);
        if ($discount > 1) {
            $fee += $discount;
        } else {
            $fee += (1.0 - $discount) * $this->defaultFee;
        }

        return [
            'cost' => $cost,
            'fee' => $fee,
        ];
    }

    /**
     * Apply discount to a fee amount.
     *
     * Discount > 1 is treated as an absolute override amount.
     * Discount <= 1 is treated as a percentage discount (e.g., 0.1 = 10% off).
     *
     * @param  float  $fee  The base fee amount
     * @param  float  $discount  The discount value
     * @return float The fee after discount
     */
    public function applyDiscount(float $fee, float $discount): float
    {
        if ($discount > 1) {
            // Absolute amount override
            return $discount;
        }

        // Percentage discount
        return $fee * (1.0 - $discount);
    }

    /**
     * Get the fee factor for grace period adjustments.
     *
     * Returns the configured grace period factor if the renewal is in grace period
     * and was completed after the due date.
     *
     * @param  object  $renewal  The renewal task object
     * @return float The fee multiplier (1.0 for normal, higher for late)
     */
    public function getGracePeriodFactor(object $renewal): float
    {
        if ($renewal->grace_period && $renewal->done_date !== null
            && strtotime($renewal->done_date) > strtotime($renewal->due_date)) {
            return $this->gracePeriodFactor;
        }

        return 1.0;
    }

    /**
     * Get the default fee amount.
     *
     * @return float The default fee
     */
    public function getDefaultFee(): float
    {
        return $this->defaultFee;
    }
}
