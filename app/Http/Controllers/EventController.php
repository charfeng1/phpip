<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Traits\HandlesAuditFields;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Controller for managing events in matters.
 *
 * Handles CRUD operations for events such as filing, publication, grant, priority claims,
 * and other milestone dates associated with IP matters.
 */
class EventController extends Controller
{
    use HandlesAuditFields;

    /**
     * Display a listing of events.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $this->authorize('viewAny', Event::class);

        $events = Event::with('info')->paginate();

        return response()->json($events);
    }

    /**
     * Display the specified event.
     *
     * @param  Event  $event  The event to display.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Event $event)
    {
        $this->authorize('view', $event);

        return response()->json($event);
    }

    /**
     * Store a new event in the database.
     *
     * @param  Request  $request  The HTTP request containing event data.
     * @return Event The newly created event model.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Event::class);

        $this->validate($request, [
            'code' => 'required',
            'eventName' => 'required',
            'matter_id' => 'required|numeric',
            'event_date' => 'required_without:alt_matter_id',
        ]);
        if ($request->filled('event_date')) {
            try {
                $request->merge(['event_date' => Carbon::createFromLocaleIsoFormat('L', app()->getLocale(), $request->event_date)]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid date format'], 422);
            }
        }
        $this->mergeCreator($request);

        $event = Event::create($this->getFilteredData($request, ['eventName']));

        return response()->json($event, 201);
    }

    /**
     * Update an event in the database.
     *
     * @param  Request  $request  The HTTP request containing updated event data.
     * @param  Event  $event  The event to update.
     * @return Event The updated event model.
     */
    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $this->validate($request, [
            'alt_matter_id' => 'nullable|numeric',
            'event_date' => 'sometimes|required_without:alt_matter_id',
        ]);
        if ($request->filled('event_date')) {
            try {
                $request->merge(['event_date' => Carbon::createFromLocaleIsoFormat('L', app()->getLocale(), $request->event_date)]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid date format'], 422);
            }
        }
        $this->mergeUpdater($request);
        $event->update($this->getFilteredData($request));

        return $event;
    }

    /**
     * Remove an event from the database.
     *
     * @param  Event  $event  The event to delete.
     * @return Event The deleted event model.
     */
    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        $event->delete();

        return $event;
    }
}
