<?php

namespace App\Services;

use App\Enums\EventCode;
use App\Mail\sendCall;
use App\Models\Actor;
use App\Models\MatterActors;
use App\Models\Task;
use App\Repositories\TaskRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * Handles sending renewal notification emails to clients.
 *
 * This service manages the complete notification workflow including:
 * - Fetching and preparing renewal data for emails
 * - Grouping renewals by client
 * - Calculating totals and fees
 * - Sending emails with proper templates
 */
class RenewalNotificationService
{
    /**
     * Default VAT rate for fee calculations.
     */
    private const DEFAULT_VAT_RATE = 0.2;

    protected RenewalFeeCalculatorService $feeCalculator;

    protected RenewalLogService $logService;

    protected TaskRepository $taskRepository;

    protected float $vatRate;

    protected array $validityConfig;

    protected string $mailRecipient;

    public function __construct(
        ?RenewalFeeCalculatorService $feeCalculator = null,
        ?RenewalLogService $logService = null,
        ?TaskRepository $taskRepository = null,
        ?float $vatRate = null,
        ?array $validityConfig = null,
        ?string $mailRecipient = null
    ) {
        $this->feeCalculator = $feeCalculator ?? new RenewalFeeCalculatorService;
        $this->logService = $logService ?? new RenewalLogService;
        $this->taskRepository = $taskRepository ?? app(TaskRepository::class);
        $this->vatRate = $vatRate ?? (float) config('renewal.invoice.vat_rate', self::DEFAULT_VAT_RATE);
        $this->validityConfig = $validityConfig ?? [
            'before' => config('renewal.validity.before', 60),
            'before_last' => config('renewal.validity.before_last', 30),
            'instruct_before' => config('renewal.validity.instruct_before', 45),
        ];
        $this->mailRecipient = $mailRecipient ?? config('renewal.general.mail_recipient', 'client');
    }

    /**
     * Send notifications for selected renewals.
     *
     * @param  array  $taskIds  Array of renewal task IDs
     * @param  array  $notifyTypes  Types of notifications to send ('first', 'warn', 'last')
     * @param  bool  $isReminder  Whether this is a reminder notification
     * @return int|string Number of processed renewals or error message
     */
    public function sendNotifications(array $taskIds, array $notifyTypes, bool $isReminder): int|string
    {
        if (empty($taskIds)) {
            return 'No renewal selected.';
        }

        $sum = 0;
        $jobId = $this->logService->createJobId();

        for ($grace = 0; $grace < count($notifyTypes); $grace++) {
            $renewalsData = $this->processRenewals($taskIds, $grace, $notifyTypes[$grace]);

            if (empty($renewalsData['renewals'])) {
                continue;
            }

            // Create logs for the processed renewals
            $logs = $this->logService->buildNotificationLogs(
                collect($renewalsData['renewals']),
                $jobId,
                $notifyTypes[$grace]
            );

            if (! empty($logs)) {
                $this->logService->logBatch($logs);
            }

            // Send emails grouped by client
            $emailResult = $this->sendEmails($renewalsData, $notifyTypes[$grace], $isReminder);

            if (is_string($emailResult)) {
                return $emailResult;
            }

            $sum += count($renewalsData['renewals']);
        }

        return $sum;
    }

    /**
     * Process renewals for a specific grace period.
     *
     * @param  array  $taskIds  Array of renewal task IDs
     * @param  int  $gracePeriod  Grace period indicator (0 or 1)
     * @param  string  $notifyType  Type of notification
     * @return array{renewals: array, clientGroups: array, totals: array}
     */
    public function processRenewals(array $taskIds, int $gracePeriod, string $notifyType): array
    {
        $renewals = $this->taskRepository->renewalsByIds($taskIds)
            ->where('grace_period', $gracePeriod)
            ->orderBy('pa_cli.name')
            ->get();

        $processedRenewals = [];
        $clientGroups = [];
        $totals = [];

        foreach ($renewals as $renewal) {
            $processedRenewal = $this->prepareRenewalData($renewal, $gracePeriod);
            $processedRenewals[] = $renewal;

            // Group renewals by client for email sending
            $clientGroups[$renewal->client_id][] = $processedRenewal;

            // Calculate totals for each client group
            if (! isset($totals[$renewal->client_id])) {
                $totals[$renewal->client_id] = ['total' => 0.0, 'total_ht' => 0.0];
            }

            // Parse formatted numbers back to floats for totaling
            $total = (float) str_replace([' ', ','], ['', '.'], $processedRenewal['total']);
            $totalHt = (float) str_replace([' ', ','], ['', '.'], $processedRenewal['total_ht']);
            $totals[$renewal->client_id]['total'] += $total;
            $totals[$renewal->client_id]['total_ht'] += $totalHt;
        }

        return [
            'renewals' => $processedRenewals,
            'clientGroups' => $clientGroups,
            'totals' => $totals,
        ];
    }

    /**
     * Prepare renewal data for email notification.
     *
     * @param  object  $renewal  The renewal task object
     * @param  int  $gracePeriod  Grace period indicator
     * @return array Formatted renewal data for email template
     */
    public function prepareRenewalData(object $renewal, int $gracePeriod): array
    {
        $language = $renewal->language ?: 'fr';
        $configPrefix = 'renewal.description.'.$language;

        $dueDate = Carbon::parse($renewal->due_date)->locale($language);
        if ($gracePeriod) {
            $dueDate->addMonths(6);
        }

        $data = [
            'caseref' => $renewal->caseref,
            'matter_id' => $renewal->matter_id,
            'language' => $language,
            'due_date' => $dueDate->format('Y-m-d'),
            'due_date_formatted' => $dueDate->isoFormat('L'),
            'country' => $this->getCountryName($renewal, $language),
            'annuity' => (int) $renewal->detail,
        ];

        // Build description
        $data['desc'] = $this->buildDescription($renewal, $language, $configPrefix);

        // Calculate fees
        $fees = $this->feeCalculator->calculate($renewal);
        $cost = $fees['cost'];
        $fee = $fees['fee'];

        $data['vat_rate'] = $this->vatRate * 100;
        $data['cost'] = number_format($cost, 2, ',', ' ');
        $data['fee'] = number_format($fee, 2, ',', ' ');
        $data['tva'] = $fee * $this->vatRate;
        $data['total_ht'] = number_format($fee + $cost, 2, ',', ' ');
        $data['total'] = number_format($fee * (1 + $this->vatRate) + $cost, 2, ',', ' ');

        return $data;
    }

    /**
     * Get localized country name based on language.
     *
     * @param  object  $renewal  The renewal object
     * @param  string  $language  The target language
     * @return string The country name
     */
    protected function getCountryName(object $renewal, string $language): string
    {
        return match ($language) {
            'fr' => $renewal->country_FR,
            'de' => $renewal->country_DE,
            default => $renewal->country_EN,
        };
    }

    /**
     * Build the description text for a renewal notification.
     *
     * @param  object  $renewal  The renewal object
     * @param  string  $language  The language code
     * @param  string  $configPrefix  The config prefix for localized strings
     * @return string The formatted description
     */
    protected function buildDescription(object $renewal, string $language, string $configPrefix): string
    {
        $strings = $this->getDescriptionStrings($configPrefix);

        $desc = sprintf($strings['line1'], $renewal->uid, $renewal->number);

        if ($renewal->event_name === EventCode::FILING->value) {
            $desc .= $strings['filed'];
        }

        if ($renewal->event_name === EventCode::GRANT->value || $renewal->event_name === EventCode::PRIORITY_CLAIM->value) {
            $desc .= $strings['granted'];
        }

        $desc .= Carbon::parse($renewal->event_date)->locale($language)->isoFormat('LL');

        if ($renewal->client_ref !== '' && $renewal->client_ref !== null) {
            $desc .= '<BR>'.sprintf($strings['line2'], $renewal->client_ref);
        }

        if ($renewal->title !== '' && $renewal->title !== null) {
            $title = $renewal->title !== '' ? $renewal->title : $renewal->short_title;
            $desc .= '<BR>'.sprintf($strings['line3'], $title);
        }

        return $desc;
    }

    /**
     * Get localized description strings.
     *
     * This method can be overridden in tests or subclasses to provide
     * custom strings without requiring Laravel's config() helper.
     *
     * @param  string  $configPrefix  The config prefix for the language
     * @return array{line1: string, filed: string, granted: string, line2: string, line3: string}
     */
    protected function getDescriptionStrings(string $configPrefix): array
    {
        // When running outside Laravel context (tests), use fallbacks
        if (! function_exists('app') || app()->bound('config') === false) {
            return [
                'line1' => '%s - %s',
                'filed' => ' filed ',
                'granted' => ' granted ',
                'line2' => 'Ref: %s',
                'line3' => 'Title: %s',
            ];
        }

        return [
            'line1' => config($configPrefix.'.line1', '%s - %s'),
            'filed' => config($configPrefix.'.filed', ' filed '),
            'granted' => config($configPrefix.'.granted', ' granted '),
            'line2' => config($configPrefix.'.line2', 'Ref: %s'),
            'line3' => config($configPrefix.'.line3', 'Title: %s'),
        ];
    }

    /**
     * Send renewal notification emails to clients.
     *
     * @param  array  $renewalsData  Processed renewal data with client groups and totals
     * @param  string  $notifyType  Type of notification ('first', 'warn', 'last')
     * @param  bool  $isReminder  Whether this is a reminder email
     * @return bool|string True on success, error message on failure
     */
    public function sendEmails(array $renewalsData, string $notifyType, bool $isReminder): bool|string
    {
        foreach ($renewalsData['clientGroups'] as $clientId => $renewals) {
            $dueDate = Carbon::parse($renewals[0]['due_date']);

            $validityDate = $this->calculateValidityDate($dueDate, $notifyType);
            $instructionDate = $this->calculateInstructionDate($dueDate, $notifyType);

            // Get contacts
            $contacts = $this->getContacts($renewals[0]['matter_id'], $clientId);

            if (is_string($contacts)) {
                return $contacts; // Error message
            }

            // Prepare and send email
            $emailData = $this->prepareEmailData($renewals, $contacts, $isReminder);

            Mail::to($emailData['recipient'])
                ->cc(Auth::user())
                ->send(new sendCall(
                    $notifyType,
                    array_values($renewals),
                    $validityDate,
                    $instructionDate,
                    number_format($renewalsData['totals'][$clientId]['total'], 2, ',', ' '),
                    number_format($renewalsData['totals'][$clientId]['total_ht'], 2, ',', ' '),
                    $emailData['reminderPrefix'],
                    $emailData['dest']
                ));
        }

        return true;
    }

    /**
     * Calculate the validity date for a notification.
     *
     * @param  Carbon  $dueDate  The renewal due date
     * @param  string  $notifyType  The notification type
     * @return string Formatted validity date
     */
    protected function calculateValidityDate(Carbon $dueDate, string $notifyType): string
    {
        $daysBefore = $notifyType === 'last'
            ? $this->validityConfig['before_last']
            : $this->validityConfig['before'];

        return $dueDate->copy()->subDays($daysBefore)->isoFormat('LL');
    }

    /**
     * Calculate the instruction date for a notification.
     *
     * @param  Carbon  $dueDate  The renewal due date
     * @param  string  $notifyType  The notification type
     * @return string|null Formatted instruction date or null for last calls
     */
    protected function calculateInstructionDate(Carbon $dueDate, string $notifyType): ?string
    {
        if ($notifyType === 'last') {
            return null;
        }

        return $dueDate->copy()->subDays($this->validityConfig['instruct_before'])->isoFormat('LL');
    }

    /**
     * Get contacts for a matter/client.
     *
     * @param  int  $matterId  The matter ID
     * @param  int  $clientId  The client actor ID
     * @return Collection|string Collection of contacts or error message
     */
    protected function getContacts(int $matterId, int $clientId): Collection|string
    {
        $contacts = MatterActors::select('email', 'name', 'first_name')
            ->where('matter_id', $matterId)
            ->where('role_code', 'CNT')
            ->get();

        if ($contacts->isEmpty()) {
            $contact = Actor::where('id', $clientId)->first();

            if ($contact === null) {
                return 'Client not found';
            }

            if ($contact->email === '' && $this->mailRecipient === 'client') {
                return 'No email address for '.$contact->name;
            }

            return collect([$contact]);
        }

        return $contacts;
    }

    /**
     * Prepare email data including recipient and greeting.
     *
     * @param  array  $renewals  The renewals for this client
     * @param  Collection  $contacts  The contact collection
     * @param  bool  $isReminder  Whether this is a reminder
     * @return array{recipient: mixed, dest: string, reminderPrefix: string}
     */
    protected function prepareEmailData(array $renewals, Collection $contacts, bool $isReminder): array
    {
        $language = $renewals[0]['language'] ?? 'fr';

        $recipient = $this->mailRecipient === 'client' ? $contacts : Auth::user();

        $dest = $this->mailRecipient === 'client'
            ? ($language === 'en' ? 'Dear Sirs, ' : 'Bonjour, ')
            : $contacts->pluck('email')->implode(', ');

        $reminderPrefix = $isReminder
            ? ($language === 'en' ? '[REMINDER] ' : '[RAPPEL] ')
            : '';

        return [
            'recipient' => $recipient,
            'dest' => $dest,
            'reminderPrefix' => $reminderPrefix,
        ];
    }
}
