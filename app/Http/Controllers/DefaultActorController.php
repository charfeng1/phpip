<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDefaultActorRequest;
use App\Http\Requests\UpdateDefaultActorRequest;
use App\Models\DefaultActor;
use App\Traits\Filterable;
use Illuminate\Http\Request;

/**
 * Manages default actor assignments for matters.
 *
 * Defines which actors should be automatically assigned to new matters
 * based on country, category, and client. Streamlines matter creation
 * by pre-populating common actor roles.
 */
class DefaultActorController extends Controller
{
    use Filterable;

    /**
     * Filter rules for index method.
     */
    protected array $filterRules = [];

    public function __construct()
    {
        $this->filterRules = [
            'Actor' => fn ($q, $v) => $q->whereHas('actor', fn ($aq) => $aq->where('name', 'like', $v.'%')),
            'Role' => fn ($q, $v) => $q->whereHas('roleInfo', fn ($rq) => $rq->where('name', 'like', $v.'%')),
            'Country' => fn ($q, $v) => $q->whereHas('country', fn ($cq) => $cq->where('name', 'like', $v.'%')),
            'Category' => fn ($q, $v) => $q->whereHas('category', fn ($cq) => $cq->where('category', 'like', $v.'%')),
            'Client' => fn ($q, $v) => $q->whereHas('client', fn ($cq) => $cq->where('name', 'like', $v.'%')),
        ];
    }
    /**
     * Display a list of default actors with filtering.
     *
     * @param Request $request Filter parameters including Actor, Role, Country, Category, Client
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = DefaultActor::query();
        $this->applyFilters($query, $request);
        $default_actors = $query->with(['roleInfo:code,name', 'actor:id,name', 'client:id,name', 'category:code,category', 'country:iso,name'])->get();

        if ($request->wantsJson()) {
            return response()->json($default_actors);
        }

        return view('default_actor.index', compact('default_actors'));
    }

    /**
     * Show the form for creating a new default actor.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $table = new DefaultActor;
        $tableComments = $table->getTableComments();

        return view('default_actor.create', compact('tableComments'));
    }

    /**
     * Store a newly created default actor.
     *
     * @param StoreDefaultActorRequest $request Default actor data including actor_id and role
     * @return DefaultActor The created default actor
     */
    public function store(StoreDefaultActorRequest $request)
    {
        return DefaultActor::create($request->validated());
    }

    /**
     * Display the specified default actor.
     *
     * @param DefaultActor $default_actor The default actor to display
     * @return \Illuminate\Http\Response
     */
    public function show(DefaultActor $default_actor)
    {
        $tableComments = $default_actor->getTableComments();
        $default_actor->with(['roleInfo:code,name', 'actor:id,name', 'client:id,name', 'category:code,category', 'country:iso,name'])->get();

        return view('default_actor.show', compact('default_actor', 'tableComments'));
    }

    /**
     * Update the specified default actor.
     *
     * @param UpdateDefaultActorRequest $request Updated default actor data
     * @param DefaultActor $default_actor The default actor to update
     * @return DefaultActor The updated default actor
     */
    public function update(UpdateDefaultActorRequest $request, DefaultActor $default_actor)
    {
        $default_actor->update($request->validated());

        return $default_actor;
    }

    /**
     * Remove the specified default actor from storage.
     *
     * @param DefaultActor $default_actor The default actor to delete
     * @return DefaultActor The deleted default actor
     */
    public function destroy(DefaultActor $default_actor)
    {
        $default_actor->delete();

        return $default_actor;
    }
}
