<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActorPivotRequest;
use App\Http\Requests\UpdateActorPivotRequest;
use App\Models\Actor;
use App\Models\ActorPivot;
use App\Traits\HandlesAuditFields;
use Illuminate\Support\Facades\DB;

/**
 * Manages actor-matter relationships (pivot table).
 *
 * Handles assignment of actors to matters in various roles with display
 * ordering. Maintains referential integrity and provides dependency tracking.
 */
class ActorPivotController extends Controller
{
    use HandlesAuditFields;
    /**
     * Assign an actor to a matter in a specific role.
     *
     * Automatically maintains display_order sequence within role groups
     * and inherits company_id from the actor.
     *
     * @param StoreActorPivotRequest $request Validated actor-matter relationship data
     * @return ActorPivot The created actor-matter relationship
     */
    public function store(StoreActorPivotRequest $request)
    {
        // Fix display order indexes if wrong
        $roleGroup = ActorPivot::where('matter_id', $request->matter_id)->where('role', $request->role);
        $max = $roleGroup->max('display_order');
        $count = $roleGroup->count();
        if ($count < $max) {
            $i = 0;
            $actors = $roleGroup->orderBy('display_order')->get();
            foreach ($actors as $actor) {
                $i++;
                $actor->display_order = $i;
                $actor->save();
            }
            $max = $i;
        }

        $addedActor = Actor::find($request->actor_id);

        $this->mergeCreator($request);
        $request->merge([
            'display_order' => $max + 1,
            'company_id' => $addedActor->company_id,
            'date' => Now(),
        ]);

        return ActorPivot::create($this->getFilteredData($request));
    }

    /**
     * Update the specified actor-matter relationship.
     *
     * @param UpdateActorPivotRequest $request Validated relationship data
     * @param ActorPivot $actorPivot The actor-matter relationship to update
     * @return ActorPivot The updated relationship
     */
    public function update(UpdateActorPivotRequest $request, ActorPivot $actorPivot)
    {
        $this->mergeUpdater($request);
        $actorPivot->update($this->getFilteredData($request));

        return $actorPivot;
    }

    /**
     * Remove an actor from a matter.
     *
     * Automatically renumbers display_order for remaining actors in the same role.
     *
     * @param ActorPivot $actorPivot The actor-matter relationship to delete
     * @return ActorPivot The deleted relationship
     */
    public function destroy(ActorPivot $actorPivot)
    {
        $matter_id = $actorPivot->matter_id;
        $role = $actorPivot->role;

        $actorPivot->delete();

        // Reorganize remaining items in role
        $actors = ActorPivot::where('matter_id', $matter_id)->where('role', $role)->orderBy('display_order')->get();
        $i = 0;
        foreach ($actors as $actor) {
            $i++;
            $actor->display_order = $i;
            $actor->save();
        }

        return $actorPivot;
    }

    /**
     * Show matters and actors dependent on a specific actor.
     *
     * Lists up to 50 matters where the actor is assigned, plus up to 30 other
     * actors that reference this actor as parent, company, or site.
     *
     * @param int $actor The actor ID to check dependencies for
     * @return \Illuminate\Http\Response
     */
    public function usedIn(int $actor)
    {
        $actorpivot = new ActorPivot;
        $matter_dependencies = $actorpivot->with('matter', 'role')->where('actor_id', $actor)->get()->take(50);
        $actor_model = new Actor;
        $other_dependencies = $actor_model->select('id', DB::Raw("concat_ws(' ', name, first_name) as Actor"), DB::Raw("(
          case $actor
            when parent_id then 'Parent'
            when company_id then 'Company'
            when site_id then 'Site'
          end) as Dependency"))->where('parent_id', $actor)->orWhere('company_id', $actor)->orWhere('site_id', $actor)->get()->take(30);

        return view('actor.usedin', compact(['matter_dependencies', 'other_dependencies']));
    }
}
