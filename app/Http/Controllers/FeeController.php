<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeeRequest;
use App\Http\Requests\UpdateFeeRequest;
use App\Models\Fee;
use App\Traits\HandlesAuditFields;
use Illuminate\Http\Request;

/**
 * Manages official fee schedules for renewals.
 *
 * Fee tables define country-specific costs and fees for patent/trademark renewals
 * based on annuity year, category, origin, and validity periods. Supports SME
 * reductions and grace period surcharges.
 */
class FeeController extends Controller
{
    use HandlesAuditFields;
    /**
     * Display a paginated list of fees with filtering.
     *
     * @param Request $request Filter parameters
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Fee::class);

        $fees = new Fee;
        $filters = $request->except(['page']);
        if (! empty($filters)) {
            foreach ($filters as $key => $value) {
                if ($value != '') {
                    $fees = match ($key) {
                        'Origin' => $fees->where('for_origin', 'LIKE', "$value%"),
                        'Category' => $fees->where('for_category', 'LIKE', "$value%"),
                        'Qt' => $fees->where('qt', "$value%"),
                        'Country' => $fees->where('for_country', 'LIKE', "$value%"),
                        default => $fees->where($key, 'LIKE', "$value%"),
                    };
                }
            }
        }

        $query = $fees->orderBy('for_category')->orderBy('for_country')->orderBy('qt');

        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        $fees = $query->simplePaginate(config('renewal.general.paginate') == 0 ? 25 : intval(config('renewal.general.paginate')));

        return view('fee.index', compact('fees'));
    }

    /**
     * Show the form for creating a new fee entry.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', Fee::class);

        $table = new Fee;
        $tableComments = $table->getTableComments();

        return view('fee.create', compact('tableComments'));
    }

    /**
     * Store newly created fee entries.
     *
     * Can create multiple entries at once when a range is specified (from_qt to to_qt).
     *
     * @param StoreFeeRequest $request Validated fee data
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreFeeRequest $request)
    {
        $this->mergeCreator($request);

        // Extract base data once before the loop to avoid repeated filtering
        $baseData = $this->getFilteredData($request, ['from_qt', 'to_qt', 'qt']);

        if (is_null($request->input('to_qt'))) {
            Fee::create(array_merge($baseData, ['qt' => $request->input('from_qt')]));
        } else {
            for ($i = intval($request->input('from_qt')); $i <= intval($request->input('to_qt')); $i++) {
                Fee::create(array_merge($baseData, ['qt' => $i]));
            }
        }

        return response()->json(['success' => 'Fee created']);
    }

    /**
     * Display the specified fee entry.
     *
     * @param Fee $fee The fee to display
     * @return Fee
     */
    public function show(Fee $fee)
    {
        $this->authorize('view', $fee);

        return $fee;
    }

    /**
     * Update the specified fee entry.
     *
     * @param UpdateFeeRequest $request Validated fee data
     * @param Fee $fee The fee to update
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateFeeRequest $request, Fee $fee)
    {
        $this->mergeUpdater($request);
        $fee->update($this->getFilteredData($request));

        return response()->json(['success' => 'Fee updated']);
    }

    /**
     * Remove the specified fee entry from storage.
     *
     * @param Fee $fee The fee to delete
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Fee $fee)
    {
        $this->authorize('delete', $fee);

        $fee->delete();

        return response()->json(['success' => 'Fee deleted']);
    }
}
