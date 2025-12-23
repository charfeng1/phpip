<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMatterTypeRequest;
use App\Http\Requests\UpdateMatterTypeRequest;
use App\Models\MatterType;
use App\Traits\Filterable;
use App\Traits\HandlesAuditFields;
use Illuminate\Http\Request;

/**
 * Manages matter type definitions.
 *
 * Matter types provide sub-classification within categories
 * (e.g., Utility Patent, Plant Patent, Word Mark, etc.).
 */
class MatterTypeController extends Controller
{
    use Filterable, HandlesAuditFields;

    /**
     * Filter rules for index method.
     */
    protected array $filterRules = [];

    public function __construct()
    {
        $this->filterRules = [
            'Code' => fn ($q, $v) => $q->whereLike('code', "$v%"),
            'Type' => fn ($q, $v) => $q->whereJsonLike('type', $v),
        ];
    }

    /**
     * Display a list of matter types with filtering.
     *
     * @param Request $request Filter parameters
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = MatterType::query();
        $this->applyFilters($query, $request);
        $matter_types = $query->get();

        if ($request->wantsJson()) {
            return response()->json($matter_types);
        }

        return view('type.index', compact('matter_types'));
    }

    /**
     * Show the form for creating a new matter type.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('type.create');
    }

    /**
     * Store a newly created matter type.
     *
     * @param StoreMatterTypeRequest $request Validated matter type data
     * @return MatterType The created matter type
     */
    public function store(StoreMatterTypeRequest $request)
    {
        $this->mergeCreator($request);

        return MatterType::create($this->getFilteredData($request));
    }

    /**
     * Display the specified matter type.
     *
     * @param MatterType $type The matter type to display
     * @return \Illuminate\Http\Response
     */
    public function show(MatterType $type)
    {
        return view('type.show', compact('type'));
    }

    /**
     * Update the specified matter type.
     *
     * @param UpdateMatterTypeRequest $request Validated matter type data
     * @param MatterType $type The matter type to update
     * @return MatterType The updated matter type
     */
    public function update(UpdateMatterTypeRequest $request, MatterType $type)
    {
        $this->mergeUpdater($request);
        $type->update($this->getFilteredData($request));

        return $type;
    }

    /**
     * Remove the specified matter type from storage.
     *
     * @param MatterType $type The matter type to delete
     * @return MatterType The deleted matter type
     */
    public function destroy(MatterType $type)
    {
        $type->delete();

        return $type;
    }
}
