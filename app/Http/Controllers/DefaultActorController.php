<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDefaultActorRequest;
use App\Http\Requests\UpdateDefaultActorRequest;
use App\Models\DefaultActor;
use App\Traits\Filterable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'Actor' => function ($q, $v) {
                // Escape LIKE wildcards to prevent SQL wildcard injection
                $escapedValue = str_replace(['%', '_'], ['\\%', '\\_'], $v);

                return $q->whereHas('actor', fn ($aq) => $aq->where('name', 'like', $escapedValue.'%'));
            },
            'Role' => function ($q, $v) {
                // Escape LIKE wildcards to prevent SQL wildcard injection
                $escapedValue = str_replace(['%', '_'], ['\\%', '\\_'], $v);

                return $q->whereHas('roleInfo', function ($rq) use ($escapedValue) {
                    $driver = DB::connection()->getDriverName();
                    if ($driver === 'pgsql') {
                        $rq->where(fn ($sub) => $sub->whereRaw("name ->> 'en' ILIKE ?", [$escapedValue.'%'])
                            ->orWhereRaw("name ->> 'fr' ILIKE ?", [$escapedValue.'%'])
                            ->orWhereRaw("name ->> 'de' ILIKE ?", [$escapedValue.'%']));
                    } else {
                        $rq->where(fn ($sub) => $sub->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE LOWER(?)", [$escapedValue.'%'])
                            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.fr'))) LIKE LOWER(?)", [$escapedValue.'%'])
                            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.de'))) LIKE LOWER(?)", [$escapedValue.'%']));
                    }
                });
            },
            'Country' => function ($q, $v) {
                // Escape LIKE wildcards to prevent SQL wildcard injection
                $escapedValue = str_replace(['%', '_'], ['\\%', '\\_'], $v);

                return $q->whereHas('country', function ($cq) use ($escapedValue) {
                    $driver = DB::connection()->getDriverName();
                    if ($driver === 'pgsql') {
                        $cq->where(fn ($sub) => $sub->whereRaw("name ->> 'en' ILIKE ?", [$escapedValue.'%'])
                            ->orWhereRaw("name ->> 'fr' ILIKE ?", [$escapedValue.'%'])
                            ->orWhereRaw("name ->> 'de' ILIKE ?", [$escapedValue.'%']));
                    } else {
                        $cq->where(fn ($sub) => $sub->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE LOWER(?)", [$escapedValue.'%'])
                            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.fr'))) LIKE LOWER(?)", [$escapedValue.'%'])
                            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.de'))) LIKE LOWER(?)", [$escapedValue.'%']));
                    }
                });
            },
            'Category' => function ($q, $v) {
                // Escape LIKE wildcards to prevent SQL wildcard injection
                $escapedValue = str_replace(['%', '_'], ['\\%', '\\_'], $v);

                return $q->whereHas('category', function ($cq) use ($escapedValue) {
                    $driver = DB::connection()->getDriverName();
                    if ($driver === 'pgsql') {
                        $cq->where(fn ($sub) => $sub->whereRaw("category ->> 'en' ILIKE ?", [$escapedValue.'%'])
                            ->orWhereRaw("category ->> 'fr' ILIKE ?", [$escapedValue.'%'])
                            ->orWhereRaw("category ->> 'de' ILIKE ?", [$escapedValue.'%']));
                    } else {
                        $cq->where(fn ($sub) => $sub->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(category, '$.en'))) LIKE LOWER(?)", [$escapedValue.'%'])
                            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(category, '$.fr'))) LIKE LOWER(?)", [$escapedValue.'%'])
                            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(category, '$.de'))) LIKE LOWER(?)", [$escapedValue.'%']));
                    }
                });
            },
            'Client' => function ($q, $v) {
                // Escape LIKE wildcards to prevent SQL wildcard injection
                $escapedValue = str_replace(['%', '_'], ['\\%', '\\_'], $v);

                return $q->whereHas('client', fn ($cq) => $cq->where('name', 'like', $escapedValue.'%'));
            },
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
        // Validate input
        $request->validate([
            'Actor' => 'nullable|string|max:255',
            'Role' => 'nullable|string|max:255',
            'Country' => 'nullable|string|max:255',
            'Category' => 'nullable|string|max:255',
            'Client' => 'nullable|string|max:255',
        ]);

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
