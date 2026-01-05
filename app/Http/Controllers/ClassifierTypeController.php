<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassifierTypeRequest;
use App\Http\Requests\UpdateClassifierTypeRequest;
use App\Models\ClassifierType;
use App\Traits\Filterable;
use App\Traits\HandlesAuditFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Manages classifier type definitions.
 *
 * Defines types of classifiers that can be attached to matters, such as
 * keywords, URLs, or image fields. Controls display behavior and categorization.
 */
class ClassifierTypeController extends Controller
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
     * Display a list of classifier types with filtering.
     *
     * @param  Request  $request  Filter parameters
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = ClassifierType::query();
        $this->applyFilters($query, $request);
        $types = $query->with(['category:code,category'])->get();

        if ($request->wantsJson()) {
            return response()->json($types);
        }

        return view('classifier_type.index', compact('types'));
    }

    /**
     * Show the form for creating a new classifier type.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('classifier_type.create');
    }

    /**
     * Store a newly created classifier type.
     *
     * @param  StoreClassifierTypeRequest  $request  Validated classifier type data
     * @return ClassifierType The created classifier type
     */
    public function store(StoreClassifierTypeRequest $request)
    {
        $this->mergeCreator($request);

        return ClassifierType::create($this->getFilteredData($request));
    }

    /**
     * Display the specified classifier type.
     *
     * @param  ClassifierType  $classifier_type  The classifier type to display
     * @return \Illuminate\Http\Response
     */
    public function show(ClassifierType $classifier_type)
    {
        $classifier_type->load(['category:code,category']);

        return view('classifier_type.show', compact('classifier_type'));
    }

    /**
     * Update the specified classifier type.
     *
     * @param  UpdateClassifierTypeRequest  $request  Validated classifier type data
     * @param  ClassifierType  $classifierType  The classifier type to update
     * @return ClassifierType The updated classifier type
     */
    public function update(UpdateClassifierTypeRequest $request, ClassifierType $classifierType)
    {
        $this->mergeUpdater($request);
        $classifierType->update($this->getFilteredData($request));

        return $classifierType;
    }

    /**
     * Remove the specified classifier type from storage.
     *
     * @param  ClassifierType  $classifierType  The classifier type to delete
     * @return ClassifierType The deleted classifier type
     */
    public function destroy(ClassifierType $classifierType)
    {
        Gate::authorize('admin');

        $classifierType->delete();

        return $classifierType;
    }
}
