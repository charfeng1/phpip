<?php

namespace App\Traits\Matter;

use App\Enums\ActorRole;
use App\Models\Actor;
use App\Models\ActorPivot;
use App\Models\MatterActors;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Trait for Matter actor relationships.
 */
trait HasActors
{
    /**
     * Get all actors associated with this matter.
     *
     * Uses the MatterActors view which includes actors inherited from the container.
     * This relationship is read-only and should only be used for displaying data.
     */
    public function actors(): HasMany
    {
        // MatterActors refers to a view that also includes the actors inherited from the container. Can only be used to display data
        return $this->hasMany(MatterActors::class);
    }

    /**
     * This relation is very useful as it allows us, using the pivot model, to access the role of the actor in the matter and filter any actor following our needs
     * It doesn't replace the belongs to many relation, but allow us to return a relation with only one item instead of an Actor
     * By doing that, we can eager-load the relation
     */
    public function actorPivot(): HasMany
    {
        return $this->hasMany(ActorPivot::class);
    }

    /**
     * Get the client actor for this matter.
     *
     * Returns the client actor using the MatterActors view.
     * IMPORTANT: Used in MatterPolicy - do not modify without checking authorization logic.
     * Returns a default empty model if no client exists.
     */
    public function client(): HasOne
    {
        // Used in Policies - do not change without checking MatterPolicy
        return $this->hasOne(MatterActors::class)->whereRoleCode(ActorRole::CLIENT->value)->withDefault();
    }

    /**
     * We check for the client using our pivot table.
     * We use the HasActorsFromRole trait to avoid repeating the same code
     */
    public function clientFromLnk(): ?MatterActors
    {
        return $this->getActorFromRole(ActorRole::CLIENT->value);
    }

    /**
     * We check for the payor using our pivot table.
     * We use the HasActorsFromRole trait to avoid repeating the same code
     */
    public function payor(): ?MatterActors
    {
        return $this->getActorFromRole(ActorRole::PAYOR->value);
    }

    /**
     * Get the delegate actor(s) for this matter.
     *
     * Delegates are actors authorized to represent the client.
     */
    public function delegate(): HasMany
    {
        return $this->actors()->whereRoleCode(ActorRole::DELEGATE->value);
    }

    /**
     * Get the contact actor(s) for this matter.
     *
     * Contacts are designated communication points for the matter.
     */
    public function contact(): HasMany
    {
        return $this->actors()->whereRoleCode(ActorRole::CONTACT->value);
    }

    /**
     * Get the applicant actor(s) for this matter.
     *
     * Applicants are the entities applying for the IP right.
     */
    public function applicants(): HasMany
    {
        return $this->actors()->whereRoleCode(ActorRole::APPLICANT->value);
    }

    /**
     * This accessor returns a string of all applicant names, separated by a semicolon.
     *
     * @return string The concatenated names of all applicants.
     */
    public function getApplicantNameAttribute()
    {
        $names = $this->applicants->pluck('name')->toArray();

        return implode('; ', $names);
    }

    /**
     * This method returns a collection of actors matching the role 'APP' for the matter.
     * using the matter_actor_lnk table and filtering by the 'APP' role.
     *
     * @return Collection The belongsToMany relationship for owners.
     */
    public function applicantsFromLnk(): Collection
    {
        return $this->getActorsFromRole(ActorRole::APPLICANT->value);
    }

    /**
     * This method returns a collection of actors matching the role 'OWN' for the matter.
     * using the matter_actor_lnk table and filtering by the 'OWN' role.
     *
     * @return Collection The belongsToMany relationship for owners.
     */
    public function owners(): Collection
    {
        return $this->getActorsFromRole(ActorRole::OWNER->value);
    }

    /**
     * This method returns a string of all owner names, separated by a semicolon.
     *
     * @return string The concatenated names of all owners.
     */
    public function getOwnerNameAttribute()
    {
        $names = $this->owners()->pluck('name')->toArray();

        return implode('; ', $names);
    }

    /**
     * Get the inventor actor(s) for this matter.
     *
     * Inventors are the individuals who created the invention.
     */
    public function inventors(): HasMany
    {
        return $this->hasMany(MatterActors::class)
            ->whereRoleCode(ActorRole::INVENTOR->value);
    }

    /**
     * We check for the agent using our pivot table. Also known as Primary Agent
     * We use the HasActorsFromRole trait to avoid repeating the same code
     */
    public function agent(): ?MatterActors
    {
        return $this->getActorFromRole(ActorRole::AGENT->value);
    }

    /**
     * We check for the secondary agent using our pivot table.
     * We use the HasActorsFromRole trait to avoid repeating the same code
     */
    public function secondaryAgent(): ?MatterActors
    {
        return $this->getActorFromRole(ActorRole::SECONDARY_AGENT->value);
    }

    /**
     * We check for the writer using our pivot table.
     * We use the HasActorsFromRole trait to avoid repeating the same code
     */
    public function writer(): ?MatterActors
    {
        return $this->getActorFromRole(ActorRole::WRITER->value);
    }

    /**
     * Here, we check for the annuityAgent using our pivot table
     * We use the HasActorsFromRole trait to avoid repeating the same code
     */
    public function annuityAgent(): ?MatterActors
    {
        return $this->getActorFromRole(ActorRole::ANNUITY_AGENT->value);
    }

    /**
     * Get the responsible actor for the matter.
     * We must name the method "responsibleActor" to avoid conflicts with the "responsible" attribute.
     */
    public function responsibleActor(): HasOne
    {
        return $this->hasOne(Actor::class, 'login', 'responsible');
    }
}
