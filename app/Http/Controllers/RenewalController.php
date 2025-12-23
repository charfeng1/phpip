<?php

namespace App\Http\Controllers;

use App\Models\RenewalsLog;
use App\Models\Task;
use App\Repositories\TaskRepository;
use App\Services\DolibarrInvoiceService;
use App\Services\RenewalFeeCalculatorService;
use App\Services\RenewalLogFilterService;
use App\Services\RenewalNotificationService;
use App\Services\RenewalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Manages the renewal workflow for patent and trademark annuities.
 *
 * Handles the complete renewal lifecycle including notifications to clients,
 * payment tracking, invoice generation (with Dolibarr integration), and
 * receipt management. Supports multi-step workflow with logging.
 */
class RenewalController extends Controller
{
    public function __construct(
        protected RenewalFeeCalculatorService $feeCalculator,
        protected RenewalNotificationService $notificationService,
        protected RenewalWorkflowService $workflowService,
        protected DolibarrInvoiceService $dolibarrService,
        protected TaskRepository $taskRepository,
        protected RenewalLogFilterService $logFilterService,
    ) {}
    /**
     * Display a paginated list of renewals with filtering.
     *
     * @param Request $request Filter parameters for renewals
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Task::class);

        // Build filters array for repository
        $step = $request->step;
        $invoice_step = $request->invoice_step;

        $filters = $request->except(['page']);
        if ($step == 0) {
            $filters['dead'] = 0;
        }

        // Get list of active renewals using repository
        $renewals = $this->taskRepository->renewals($filters);

        // Only display pending renewals at the beginning of the pipeline
        if ($this->taskRepository->shouldShowOnlyPending($filters)) {
            $renewals->where('done', 0);
        }

        // Order by most recent renewals first in the "Closed" and "Invoice paid" steps
        $sortDirection = $this->taskRepository->getSortDirection($step, $invoice_step);
        if ($sortDirection) {
            $renewals->orderByDesc('due_date');
        }

        if ($request->wantsJson()) {
            $renewals = $renewals->get();
            $renewals->transform(function ($ren) {
                $fees = $this->feeCalculator->calculate($ren);
                $ren->cost = $fees['cost'];
                $ren->fee = $fees['fee'];

                return $ren;
            });

            return response()->json($renewals);
        }

        $renewals = $renewals->simplePaginate(config('renewal.general.paginate', 25));

        // Adjust the cost and fee of each renewal based on customized settings
        $renewals->transform(function ($ren) {
            $fees = $this->feeCalculator->calculate($ren);
            $ren->cost = $fees['cost'];
            $ren->fee = $fees['fee'];

            return $ren;
        });

        $renewals->appends($request->input())->links(); // Keep URL parameters in the paginator links

        return view('renewals.index', compact('renewals', 'step', 'invoice_step'));
    }

    /**
     * Send first call notifications to clients for selected renewals.
     *
     * @param Request $request Contains task_ids array of renewal task IDs
     * @param int $send Whether to send emails (1) or just create calls (0)
     * @return \Illuminate\Http\JsonResponse
     */
    public function firstcall(Request $request, int $send)
    {
        $this->authorize('create', Task::class);

        $rep = count($request->task_ids ?? []);
        if ($send == 1) {
            $rep = $this->notificationService->sendNotifications($request->task_ids ?? [], ['first'], false);
        }
        if (is_numeric($rep)) {
            $this->workflowService->markFirstCall($request->task_ids ?? []);

            return response()->json(['success' => 'Calls created for '.$rep.' renewals']);
        } else {
            return response()->json(['error' => $rep], 501);
        }
    }

    /**
     * Send reminder call notifications for renewals.
     *
     * @param Request $request Contains task_ids array of renewal task IDs
     * @return \Illuminate\Http\JsonResponse
     */
    public function remindercall(Request $request)
    {
        $this->authorize('create', Task::class);

        $rep = $this->notificationService->sendNotifications($request->task_ids ?? [], ['first', 'warn'], true);
        if (is_numeric($rep)) {
            return response()->json(['success' => 'Calls sent for '.$rep.' renewals']);
        } else {
            return response()->json(['error' => $rep], 501);
        }
    }

    /**
     * Send final call notifications for renewals entering grace period.
     *
     * @param Request $request Contains task_ids array of renewal task IDs
     * @return \Illuminate\Http\JsonResponse
     */
    public function lastcall(Request $request)
    {
        $this->authorize('create', Task::class);

        $rep = $this->notificationService->sendNotifications($request->task_ids ?? [], ['last'], true);
        if (is_numeric($rep)) {
            $this->workflowService->markGracePeriod($request->task_ids ?? []);

            return response()->json(['success' => 'Calls sent for '.$rep.' renewals']);
        } else {
            return response()->json(['error' => $rep], 501);
        }
    }

    /**
     * Mark selected renewals as ready to pay.
     *
     * Moves renewals to step 4 and invoice_step 1 in the workflow.
     *
     * @param Request $request Contains task_ids array of renewal task IDs
     * @return \Illuminate\Http\JsonResponse
     */
    public function topay(Request $request)
    {
        $this->authorize('create', Task::class);

        if (! isset($request->task_ids)) {
            return response()->json(['error' => 'No renewal selected.']);
        }

        $this->workflowService->markToPay($request->task_ids);

        return response()->json(['success' => 'Marked as to pay']);
    }

    /**
     * Generate invoices for selected renewals.
     *
     * Optionally integrates with Dolibarr to create invoices. Updates renewal
     * status to invoice_step 2.
     *
     * @param Request $request Contains task_ids array of renewal task IDs
     * @param int $toinvoice Whether to create invoices in Dolibarr (1) or just update status (0)
     * @return \Illuminate\Http\JsonResponse
     */
    public function invoice(Request $request, int $toinvoice)
    {
        $this->authorize('create', Task::class);

        if (! isset($request->task_ids)) {
            return response()->json(['error' => 'No renewal selected.']);
        }

        $num = 0;
        if ($this->dolibarrService->isEnabled() && $toinvoice) {
            $renewals = $this->taskRepository->renewalsByIds($request->task_ids)
                ->orderBy('client_name')
                ->get();

            $result = $this->dolibarrService->createInvoicesForRenewals($renewals);

            if (! $result['success']) {
                return response()->json(['error' => $result['error']]);
            }

            $num = $renewals->count();
        }

        $this->workflowService->markInvoiced($request->task_ids);

        return response()->json(['success' => "Invoices created for $num renewals"]);
    }

    /**
     * Mark invoices as paid for selected renewals.
     *
     * @param Request $request Contains task_ids array of renewal task IDs
     * @return \Illuminate\Http\JsonResponse
     */
    public function paid(Request $request)
    {
        $this->authorize('create', Task::class);

        if (! isset($request->task_ids)) {
            return response()->json(['error' => 'No renewal selected.']);
        }

        $num = $this->workflowService->markPaid($request->task_ids);

        return response()->json(['success' => "$num invoices paid"]);
    }

    /**
     * Export renewals ready to pay as CSV file.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response CSV download response
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Task::class);

        $export = $this->taskRepository->renewalsForExport()->get();
        $export = $export->map(function ($ren) {
            $fees = $this->feeCalculator->calculate($ren);
            $ren->cost = $fees['cost'];
            $ren->fee = $fees['fee'];

            return $ren->getAttributes();
        });

        $captions = config('renewal.invoice.captions');
        $export_csv = fopen('php://memory', 'w');
        fputcsv($export_csv, $captions, ';');
        foreach ($export->toArray() as $row) {
            fputcsv($export_csv, array_map('utf8_decode', $row), ';');
        }
        rewind($export_csv);
        $filename = now()->format('YmdHis').'_invoicing.csv';

        return response()->stream(
            function () use ($export_csv) {
                fpassthru($export_csv);
            },
            200,
            ['Content-Type' => 'application/csv', 'Content-disposition' => 'attachment; filename='.$filename]
        );
    }

    /**
     * Mark selected renewals as done (cleared).
     *
     * Sets done_date and moves renewals to step 6.
     *
     * @param Request $request Contains task_ids array of renewal task IDs
     * @return \Illuminate\Http\JsonResponse
     */
    public function done(Request $request)
    {
        $this->authorize('create', Task::class);

        if (! isset($request->task_ids)) {
            return response()->json(['error' => 'No renewal selected.']);
        }

        $updated = $this->workflowService->markDone($request->task_ids);

        return response()->json(['success' => strval($updated).' renewals cleared']);
    }

    /**
     * Register official receipts for selected renewals.
     *
     * Moves renewals to step 8 indicating receipt received.
     *
     * @param Request $request Contains task_ids array of renewal task IDs
     * @return \Illuminate\Http\JsonResponse
     */
    public function receipt(Request $request)
    {
        $this->authorize('create', Task::class);

        if (! isset($request->task_ids)) {
            return response()->json(['error' => 'No renewal selected.']);
        }

        $updated = $this->workflowService->markReceipt($request->task_ids);

        return response()->json(['success' => strval($updated).' receipts registered']);
    }

    /**
     * Close selected renewals.
     *
     * Moves renewals to step 10 (or -1 if already done) to complete the workflow.
     *
     * @param Request $request Contains task_ids array of renewal task IDs
     * @return \Illuminate\Http\JsonResponse
     */
    public function closing(Request $request)
    {
        $this->authorize('create', Task::class);

        if (! isset($request->task_ids)) {
            return response()->json(['error' => 'No renewal selected.']);
        }

        $updated = $this->workflowService->markClosed($request->task_ids);

        return response()->json(['success' => strval($updated).' closed']);
    }

    /**
     * Mark renewals as abandoned by client.
     *
     * Moves renewals to step 12 and creates an ABA event on the matter.
     *
     * @param Request $request Contains task_ids array of renewal task IDs
     * @return \Illuminate\Http\JsonResponse
     */
    public function abandon(Request $request)
    {
        $this->authorize('create', Task::class);

        if (! isset($request->task_ids)) {
            return response()->json(['error' => 'No renewal selected.']);
        }

        $updated = $this->workflowService->markAbandoned($request->task_ids);

        return response()->json(['success' => strval($updated).' abandons registered']);
    }

    /**
     * Register lapse communications for selected renewals.
     *
     * Moves renewals to step 14 and creates a LAP event on the matter.
     *
     * @param Request $request Contains task_ids array of renewal task IDs
     * @return \Illuminate\Http\JsonResponse
     */
    public function lapsing(Request $request)
    {
        $this->authorize('create', Task::class);

        if (! isset($request->task_ids)) {
            return response()->json(['error' => 'No renewal selected.']);
        }

        $updated = $this->workflowService->markLapsed($request->task_ids);

        return response()->json(['success' => strval($updated).' communications registered']);
    }

    /**
     * Generate XML payment order for selected renewals.
     *
     * Creates an XML file for submitting payments to patent/trademark offices.
     * Optionally clears renewals after order generation.
     *
     * @param Request $request Contains task_ids and clear flag
     * @return \Illuminate\Http\Response XML download response
     */
    public function renewalOrder(Request $request)
    {
        $this->authorize('create', Task::class);

        $tids = $request->task_ids;
        $procedure = '';
        $prev_procedure = '';
        $clear = boolval($request->clear);
        $xml = new \SimpleXMLElement(config('renewal.xml.body'));
        if ($xml->header->sender->name == 'NAME') {
            $xml->header->sender->name = Auth::user()->name;
        }
        $xml->header->{'payment-reference-id'} = 'ANNUITY '.now()->format('Ymd');
        $total = 0;
        $first = true;
        $renewals = $this->taskRepository->renewalsByIds($tids)->get();
        foreach ($renewals as $renewal) {
            $procedure = $renewal->country;
            if ($first) {
                $prev_procedure = $procedure;
                $first = false;
            } else {
                if ($prev_procedure != $procedure) {
                    // The order can only be for once juridiction
                    return response()->json(['error' => 'More than one juridiction is selected'], 501);
                }
            }
            $country = $renewal->country;
            if ($country == 'EP') {
                // Use fee code from EPO
                $fee_code = '0'.strval(intval($renewal->detail) + 30);
            } else {
                $fee_code = $renewal->detail;
            }
            $fees = $this->feeCalculator->calculate($renewal);
            $cost = $fees['cost'];
            $total += $cost;
            if ($renewal->origin == 'EP') {
                $number = preg_replace('/[^0-9]/', '', $renewal->pub_num);
                $country = 'EP';
            } else {
                $number = preg_replace('/[^0-9]/', '', $renewal->fil_num);
            }
            $fees = $xml->detail->addChild('fees');
            $fees->addAttribute('procedure', $procedure);
            $docid = $fees->addChild('document-id');
            $docid->addChild('country', $country);
            $docid->addChild('doc-number', $number);
            $docid->addChild('date', Carbon::parse($renewal->event_date)->isoFormat('YMMDD'));
            $docid->addChild('kind', 'application');
            $fees->addChild('file-reference-id', $renewal->uid);
            $fees->addChild('owner', $procedure == 'FR' ? $renewal->uid : $renewal->applicant_name);
            $fee = $fees->addChild('fee');
            $fee->addChild('type-of-fee', $fee_code);
            $fee->addChild('fee-sub-amount', $renewal->cost);
            $fee->addChild('fee-factor', '1');
            $fee->addChild('fee-total-amount', $renewal->cost);
            // $fee->addChild('fee-date-due', Carbon::parse($renewal->due_date)->isoFormat('YMMDD'));
            /* Produced XML:
            <fees procedure="$procedure">
                <document-id>
                    <country>$country</country>
                    <doc-number>$number</doc-number>
                    <date>' . $fmt->format(strtotime($renewal->event_date)) . '</date>
                    <kind>application</kind>
                </document-id>
                <file-reference-id>$renewal->uid</file-reference-id>
                <owner>$renewal->applicant_name</owner>
                <fee>
                    <type-of-fee>$fee_code</type-of-fee>
                    <fee-sub-amount>$renewal->cost</fee-sub-amount>
                    <fee-factor>1</fee-factor>
                    <fee-total-amount>$renewal->cost</fee-total-amount>
                </fee>
            </fees>'
            */
        }

        // $header = config('renewal.xml.header');
        if ($procedure == 'EP') {
            // $header = str_replace('DEPOSIT', config('renewal.xml.EP_deposit'), $header);
            $xml->header->{'mode-of-payment'}->{'deposit-account'}->{'account-no'} = config('renewal.xml.EP_deposit');
        }
        if ($procedure == 'FR') {
            // $header = str_replace('DEPOSIT', config('renewal.xml.FR_deposit'), $header);
            $xml->header->{'mode-of-payment'}->{'deposit-account'}->{'account-no'} = config('renewal.xml.FR_deposit');
        }
        // $footer = str_replace('TOTAL', $total, config('renewal.xml.footer'));
        $xml->trailer->{'batch-pay-total-amount'} = $total;
        // $footer = str_replace('COUNT', count($tids), $footer);
        $xml->trailer->{'total-records'} = count($tids);
        // $xml .= $footer;
        // This indents the produced xml
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $fd = fopen('php://memory', 'w');
        fwrite($fd, $dom->saveXML());
        rewind($fd);
        if ($clear) {
            $this->workflowService->markDone($tids);
        }
        $filename = Now()->isoFormat('YMMDDHHmmss').'_payment_order.xml';

        return response()->stream(
            function () use ($fd) {
                fpassthru($fd);
            },
            200,
            ['Content-Type' => 'application/xml', 'Content-Disposition' => 'attachment; filename='.$filename]
        );
    }

    /**
     * Update a renewal task.
     *
     * @param Request $request Updated renewal data
     * @param Task $renewal The renewal task to update
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Task $renewal)
    {
        $this->authorize('update', $renewal);

        $this->validate($request, [
            'cost' => 'nullable|numeric',
            'fee' => 'nullable|numeric',
        ]);

        $renewal->update($request->except(['_token', '_method']));

        return response()->json(['success' => 'Renewal updated']);
    }

    /**
     * Display renewal processing logs with filtering.
     *
     * @param Request $request Filter parameters for logs
     * @return \Illuminate\Http\Response
     */
    public function logs(Request $request)
    {
        $this->authorize('viewAny', RenewalsLog::class);

        $filters = $request->except(['_token']);

        // Use RenewalLogFilterService to handle filtering logic
        $logs = $this->logFilterService->filterLogs(new RenewalsLog, $filters);

        $logs = $logs->orderby('job_id')->simplePaginate(config('renewal.general.paginate', 25));

        return view('renewals.logs', compact('logs'));
    }
}
