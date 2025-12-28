<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventNameRequest;
use App\Http\Requests\UpdateEventNameRequest;
use App\Models\EventClassLnk;
use App\Models\EventName;
use App\Services\CommonFilters;
use Illuminate\Http\Request;

/**
 * Manages event name definitions.
 *
 * Event names represent procedural milestones in matter lifecycle (filing, grant,
 * publication, etc.). Used as triggers for task rules and workflow automation.
 */
class EventNameController extends BaseResourceController
{
    /**
     * Filter rules for index method.
     */
    protected array $filterRules = [];

    public function __construct()
    {
        $this->filterRules = [
            'Code' => CommonFilters::startsWith('code'),
            'Name' => CommonFilters::jsonLike('name'),
        ];
    }

    /**
     * Display a paginated list of event names with filtering.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', EventName::class);

        $query = EventName::query();
        $this->applyFilters($query, $request);

        if ($json = $this->jsonOrNull($request, $query)) {
            return $json;
        }

        $enameslist = $this->paginateWithQueryString($query, $request);

        return view('eventname.index', compact('enameslist'));
    }

    /**
     * Show the form for creating a new event name.
     */
    public function create()
    {
        $this->authorize('create', EventName::class);

        return view('eventname.create');
    }

    /**
     * Store a newly created event name.
     */
    public function store(StoreEventNameRequest $request)
    {
        $this->performStore($request, EventName::class);

        return $this->jsonRedirect(route('eventname.index'));
    }

    /**
     * Display the specified event name with template class links.
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
     */
    public function update(UpdateEventNameRequest $request, EventName $eventname)
    {
        return $this->performUpdate($request, $eventname);
    }

    /**
     * Remove the specified event name from storage.
     */
    public function destroy(EventName $eventname)
    {
        $this->authorize('delete', $eventname);

        return $this->performDestroy($eventname);
    }
}
