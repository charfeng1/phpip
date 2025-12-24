<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActorRequest;
use App\Http\Requests\UpdateActorRequest;
use App\Models\Actor;
use App\Traits\Filterable;
use App\Traits\HandlesAuditFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Controller for managing actors (individuals and organizations).
 *
 * Handles CRUD operations for actors such as clients, agents, inventors,
 * applicants, and other parties involved in IP matters.
 */
class ActorController extends Controller
{
    use Filterable;
    use HandlesAuditFields;

    /**
     * Selector filter mappings.
     */
    private const SELECTOR_FILTERS = [
        'phy_p' => ['phy_person', 1],
        'leg_p' => ['phy_person', 0],
        'warn' => ['warn', 1],
    ];

    /**
     * Display a paginated list of actors with optional filtering.
     *
     * @param  Request  $request  The HTTP request containing filter parameters.
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse The view or JSON response with filtered actors.
     */
    public function index(Request $request)
    {
        Gate::authorize('readonly');

        $query = Actor::query();

        if ($request->filled('Name')) {
            $query->where('name', 'like', $request->Name.'%');
        }

        // Apply selector filter using lookup map instead of switch
        if ($request->filled('selector') && isset(self::SELECTOR_FILTERS[$request->selector])) {
            [$column, $value] = self::SELECTOR_FILTERS[$request->selector];
            $query->where($column, $value);
        }

        $query->with('company')->orderby('name');

        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        $actorslist = $query->paginate(config('pagination.actors', 21));
        $actorslist->appends($request->input())->links();

        return view('actor.index', compact('actorslist'));
    }

    /**
     * Show the form for creating a new actor.
     *
     * @return \Illuminate\Http\Response The view for creating a new actor.
     */
    public function create()
    {
        Gate::authorize('readwrite');

        $actor = new Actor;
        $actorComments = $actor->getTableComments();

        return view('actor.create', compact('actorComments'));
    }

    /**
     * Store a new actor in the database.
     *
     * @param  StoreActorRequest  $request  The validated HTTP request containing actor data.
     * @return Actor The newly created actor model.
     */
    public function store(StoreActorRequest $request)
    {
        $this->mergeCreator($request);

        return Actor::create($this->getFilteredData($request));
    }

    /**
     * Display detailed information for a specific actor.
     *
     * @param  Actor  $actor  The actor to display.
     * @return \Illuminate\Http\Response The view with actor details.
     */
    public function show(Actor $actor)
    {
        Gate::authorize('readonly');

        $actorInfo = $actor->load([
            'company:id,name',
            'parent:id,name',
            'site:id,name',
            'droleInfo',
            'countryInfo:iso,name',
            'country_mailingInfo:iso,name',
            'country_billingInfo:iso,name',
            'nationalityInfo:iso,name',
        ]);
        $actorComments = $actor->getTableComments();

        return view('actor.show', compact('actorInfo', 'actorComments'));
    }

    /**
     * Update an actor in the database.
     *
     * @param  UpdateActorRequest  $request  The validated HTTP request containing updated actor data.
     * @param  Actor  $actor  The actor to update.
     * @return Actor The updated actor model.
     */
    public function update(UpdateActorRequest $request, Actor $actor)
    {
        $this->mergeUpdater($request);
        $actor->update($this->getFilteredData($request));

        return $actor;
    }

    /**
     * Remove an actor from the database.
     *
     * @param  Actor  $actor  The actor to delete.
     * @return Actor The deleted actor model.
     */
    public function destroy(Actor $actor)
    {
        Gate::authorize('readwrite');

        $actor->delete();

        return $actor;
    }
}
