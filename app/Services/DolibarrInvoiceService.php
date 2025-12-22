<?php

namespace App\Services;

use App\Enums\EventCode;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Handles invoice creation in the Dolibarr ERP system.
 *
 * This service encapsulates all Dolibarr API interactions for creating
 * invoices from patent/trademark renewal data.
 */
class DolibarrInvoiceService
{
    protected ?string $apiKey;

    protected ?string $baseUrl;

    protected ?int $fkAccount;

    protected RenewalFeeCalculatorService $feeCalculator;

    public function __construct(
        ?RenewalFeeCalculatorService $feeCalculator = null,
        ?string $apiKey = null,
        ?string $baseUrl = null,
        ?int $fkAccount = null
    ) {
        $this->apiKey = $apiKey ?? config('renewal.api.DOLAPIKEY');
        $this->baseUrl = $baseUrl ?? config('renewal.api.dolibarr_url');
        $this->fkAccount = $fkAccount ?? config('renewal.api.fk_account');
        $this->feeCalculator = $feeCalculator ?? new RenewalFeeCalculatorService;
    }

    /**
     * Check if Dolibarr API is configured.
     */
    public function isConfigured(): bool
    {
        return $this->apiKey !== null && $this->baseUrl !== null;
    }

    /**
     * Check if Dolibarr backend is enabled.
     */
    public function isEnabled(): bool
    {
        return config('renewal.invoice.backend') === 'dolibarr';
    }

    /**
     * Search for a client in Dolibarr by name.
     *
     * @param string $clientName The client name to search for
     * @return array|null Client data or null if not found
     */
    public function findClient(string $clientName): ?array
    {
        $curl = curl_init();
        $httpheader = ['DOLAPIKEY: '.$this->apiKey];
        $data = ['sqlfilters' => '(t.nom:like:"'.$clientName.'%")'];
        $url = $this->baseUrl.'/thirdparties?'.http_build_query($data);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);
        $result = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($result, true);

        if (isset($response['error']) && $response['error']['code'] >= 404) {
            return null;
        }

        return $response[0] ?? null;
    }

    /**
     * Create an invoice in Dolibarr.
     *
     * @param array $invoiceData Invoice properties
     * @return array{success: bool, data: mixed, error: ?string}
     */
    public function createInvoice(array $invoiceData): array
    {
        $curl = curl_init();
        $url = $this->baseUrl.'/invoices';

        $httpheader = [
            'DOLAPIKEY: '.$this->apiKey,
            'Content-Type:application/json',
        ];

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($invoiceData));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);

        $result = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        $response = json_decode($result, true);

        if (isset($response['error'])) {
            return [
                'success' => false,
                'data' => null,
                'error' => $response['error'],
            ];
        }

        if ($status === 0) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Invoice API is not reachable',
            ];
        }

        return [
            'success' => true,
            'data' => $response,
            'error' => null,
        ];
    }

    /**
     * Determine VAT rate based on client's TVA intra code.
     *
     * @param string|null $tvaIntra The client's TVA intra code
     * @return float VAT rate (0.2 for French, 0.0 for EU)
     */
    public function determineVatRate(?string $tvaIntra): float
    {
        if ($tvaIntra === null || $tvaIntra === '' || str_starts_with($tvaIntra, 'FR')) {
            return 0.2;
        }

        return 0.0;
    }

    /**
     * Build invoice line description for a renewal.
     *
     * @param object $renewal The renewal data
     * @return string The formatted description
     */
    public function buildLineDescription(object $renewal): string
    {
        $desc = "$renewal->uid : Annuité pour l'année $renewal->detail du titre $renewal->number";

        if ($renewal->event_name === EventCode::FILING->value) {
            $desc .= ' déposé le ';
        }

        if ($renewal->event_name === EventCode::GRANT->value || $renewal->event_name === EventCode::PRIORITY_CLAIM->value) {
            $desc .= ' délivré le ';
        }

        $desc .= Carbon::parse($renewal->event_date)->isoFormat('LL');
        $desc .= ' en '.$renewal->country_FR;

        if ($renewal->title != '') {
            $desc .= "\nSujet : $renewal->title";
        }

        if ($renewal->client_ref != '') {
            $desc .= " ($renewal->client_ref)";
        }

        $desc .= "\nÉchéance le ".Carbon::parse($renewal->due_date)->isoFormat('LL');

        return $desc;
    }

    /**
     * Build invoice lines for a set of renewals.
     *
     * @param Collection $renewals The renewals to invoice
     * @param float $vatRate The VAT rate to apply
     * @return array The invoice lines
     */
    public function buildInvoiceLines(Collection $renewals, float $vatRate): array
    {
        $lines = [];

        foreach ($renewals as $renewal) {
            $fees = $this->feeCalculator->calculate($renewal);
            $cost = $fees['cost'];
            $fee = $fees['fee'];

            $desc = $this->buildLineDescription($renewal);

            if ($cost != 0) {
                $desc .= "\nHonoraires pour la surveillance et le paiement";
            } else {
                $desc .= "\nHonoraires et taxe";
            }

            // Fee line (with VAT)
            $lines[] = [
                'desc' => $desc,
                'product_type' => 1,
                'tva_tx' => $vatRate * 100,
                'remise_percent' => 0,
                'qty' => 1,
                'subprice' => $fee,
                'total_tva' => $fee * $vatRate,
                'total_ttc' => $fee * (1.0 + $vatRate),
            ];

            // Cost line (no VAT) - separate line for official fees
            if ($cost != 0) {
                $lines[] = [
                    'product_type' => 1,
                    'desc' => 'Taxe',
                    'tva_tx' => 0.0,
                    'remise_percent' => 0,
                    'qty' => 1,
                    'subprice' => $cost,
                    'total_tva' => 0,
                    'total_ttc' => $cost,
                ];
            }
        }

        return $lines;
    }

    /**
     * Build complete invoice data structure.
     *
     * @param int $socId The Dolibarr client/society ID
     * @param array $lines The invoice lines
     * @return array The complete invoice data
     */
    public function buildInvoiceData(int $socId, array $lines): array
    {
        return [
            'socid' => $socId,
            'date' => time(),
            'cond_reglement_id' => 1,
            'mode_reglement_id' => 2,
            'lines' => $lines,
            'fk_account' => $this->fkAccount,
        ];
    }

    /**
     * Create invoices for a collection of renewals grouped by client.
     *
     * @param Collection $renewals Renewals ordered by client_name
     * @return array{success: bool, count: int, error: ?string}
     */
    public function createInvoicesForRenewals(Collection $renewals): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'count' => 0,
                'error' => 'API is not configured',
            ];
        }

        if ($renewals->isEmpty()) {
            return [
                'success' => false,
                'count' => 0,
                'error' => 'No renewal selected.',
            ];
        }

        $grouped = $renewals->groupBy('client_name');
        $invoiceCount = 0;

        foreach ($grouped as $clientName => $clientRenewals) {
            $client = $this->findClient($clientName);

            if ($client === null) {
                return [
                    'success' => false,
                    'count' => $invoiceCount,
                    'error' => "$clientName not found in Dolibarr.",
                ];
            }

            $vatRate = $this->determineVatRate($client['tva_intra'] ?? null);
            $lines = $this->buildInvoiceLines(collect($clientRenewals), $vatRate);
            $invoiceData = $this->buildInvoiceData($client['id'], $lines);

            $result = $this->createInvoice($invoiceData);

            if (! $result['success']) {
                return [
                    'success' => false,
                    'count' => $invoiceCount,
                    'error' => $result['error'],
                ];
            }

            $invoiceCount++;
        }

        return [
            'success' => true,
            'count' => $renewals->count(),
            'error' => null,
        ];
    }
}
