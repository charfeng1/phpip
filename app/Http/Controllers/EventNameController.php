<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventNameRequest;
use App\Http\Requests\UpdateEventNameRequest;
use App\Models\EventClassLnk;
use App\Models\EventName;
use App\Traits\Filterable;
use App\Traits\HandlesAuditFields;
use Illuminate\Http\Request;

/**
 * Manages event name definitions.
 *
 * Event names represent procedural milestones in matter lifecycle (filing, grant,
 * publication, etc.). Used as triggers for task rules and workflow automation.
 */
class EventNameController extends Controller
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
            'Name' => fn ($q, $v) => $q->whereJsonLike('name', $v),
        ];
    }

    /**
     * Display a paginated list of event names with filtering.
     *
     * @param Request $request Filter parameters
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', EventName::class);

        $query = EventName::query();
        $this->applyFilters($query, $request);

        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        $enameslist = $this->filterAndPaginate($query, $request, config('pagination.default', 21));

        return view('eventname.index', compact('enameslist'));
    }

    /**
     * Show the form for creating a new event name.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', EventName::class);

        return view('eventname.create');
    }

    /**
     * Store a newly created event name.
     *
     * @param StoreEventNameRequest $request Validated event name data
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreEventNameRequest $request)
    {
        $this->mergeCreator($request);
        EventName::create($this->getFilteredData($request));

        return response()->json(['redirect' => route('eventname.index')]);
    }

    /**
     * Display the specified event name with template class links.
     *
     * @param EventName $eventname The event name to display
     * @return \Illuminate\Http\Response
     */
    public function show(EventName $eventname)
    {
        $this->authorize('view', $eventname);

        $eventname->load(['countryInfo:iso,name', 'categoryInfo:code,category', 'default_responsibleInfo:id,name']);
        $links = EventClassLnk::where('event_name_code', '=', $eventname->code)->get();

        return view('eventname.show', compact('eventname', 'links'));
    }

    /**
     * Update the specified event name.
     *
     * @param UpdateEventNameRequest $request Validated event name data
     * @param EventName $eventname The event name to update
     * @return EventName The updated event name
     */
    public function update(UpdateEventNameRequest $request, EventName $eventname)
    {
        $this->mergeUpdater($request);
        $eventname->update($this->getFilteredData($request));

        return $eventname;
    }

    /**
     * Remove the specified event name from storage.
     *
     * @param EventName $eventname The event name to delete
     * @return EventName The deleted event name
     */
    public function destroy(EventName $eventname)
    {
        $this->authorize('delete', $eventname);

        $eventname->delete();

        return $eventname;
    }
}
