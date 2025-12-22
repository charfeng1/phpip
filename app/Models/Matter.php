<?php

namespace App\Models;

use App\Enums\ActorRole;
use App\Enums\CategoryCode;
use App\Enums\ClassifierType;
use App\Enums\EventCode;
use App\Enums\UserRole;
use App\Services\TeamService;
use App\Traits\Auditable;
use App\Traits\DatabaseJsonHelper;
use App\Traits\HasActorsFromRole;
use App\Traits\TrimsCharColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Matter Model
 *
 * Represents an intellectual property matter (patent, trademark, design, etc.) in the phpIP system.
 * This is the core model of the application, representing cases and tracking their lifecycle from
 * filing through prosecution to grant/registration and maintenance.
 *
 * Database table: matter
 *
 * Key relationships:
 * - Belongs to a container (parent matter for family grouping)
 * - Has many family members (related matters with same caseref)
 * - Has many events (filing, publication, grant, etc.)
 * - Has many actors in various roles (client, agent, applicant, inventor, etc.)
 * - Has many tasks (reminders and deadlines)
 * - Has many classifiers (titles, classes, keywords)
 *
 * Business logic:
 * - Matters can be containers (families) or individual cases within a family
 * - Actor relationships can be inherited from container to family members
 * - Status is derived from the most recent status event
 * - Complex filtering system for matter lists with role-based access control
 */
class Matter extends Model
{
    use Auditable;
    use DatabaseJsonHelper;
    use HasActorsFromRole;
    use HasFactory;
    use TrimsCharColumns;

    /**
     * CHAR columns that should be automatically trimmed.
     *
     * PostgreSQL CHAR columns are fixed-length and pad with spaces.
     * These columns will be automatically trimmed when accessed.
     *
     * @var array<string>
     */
    protected $charColumns = [
        'category_code',
        'country',
        'origin',
        'type_code',
    ];

    /**
     * Attributes to exclude from audit logging.
     *
     * @var array<string>
     */
    protected $auditExclude = ['created_at', 'updated_at'];

    /**
     * The database table associated with the model.
     *
     * @var string
     */
    protected $table = 'matter';

    /**
     * Attributes that should be hidden from serialization.
     *
     * @var array<string>
     */
    protected $hidden = ['creator', 'created_at', 'updated_at', 'updater'];

    /**
     * Attributes that are not mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /*protected $casts = [
        'expire_date' => 'date:Y-m-d'
    ];*/

    /**
     * Get all family members of this matter.
     *
     * Family members are matters that share the same caseref (family identifier).
     * Results are ordered by origin, country, type, and index.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function family()
    {
        // Gets family members
        return $this->hasMany(Matter::class, 'caseref', 'caseref')
            ->orderBy('origin')
            ->orderBy('country')
            ->orderBy('type_code')
            ->orderBy('idx');
    }

    /**
     * Get the container (parent family) of this matter.
     *
     * A container is a matter that groups related cases together.
     * Returns a default empty model if no container exists.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function container()
    {
        return $this->belongsTo(Matter::class, 'container_id')->withDefault();
    }

    /**
     * Get the parent matter from which this matter was derived.
     *
     * Used for tracking priority relationships (e.g., PCT -> National phase).
     * Returns a default empty model if no parent exists.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Matter::class, 'parent_id')->withDefault();
    }

    /**
     * Get all descendants of this matter.
     *
     * Descendants are matters that were derived from this matter (e.g., national phases from PCT).
     * Results are ordered by origin, country, type, and index.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function descendants()
    {
        return $this->hasMany(Matter::class, 'parent_id')
            ->orderBy('origin')
            ->orderBy('country')
            ->orderBy('type_code')
            ->orderBy('idx');
    }

    /**
     * Get external matters claiming priority from this matter.
     *
     * Returns matters outside this family that have priority events linking to this matter.
     * Note: The where clause is ignored during eager loading.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function priorityTo()
    {
        // Gets external matters claiming priority on this one (where clause is ignored by eager loading)
        return $this->belongsToMany(Matter::class, 'event', 'alt_matter_id')
            ->where('caseref', '!=', $this->caseref)
            ->orderBy('caseref')
            ->orderBy('origin')
            ->orderBy('country')
            ->orderBy('type_code')
            ->orderBy('idx');
    }

    /**
     * Get all actors associated with this matter.
     *
     * Uses the MatterActors view which includes actors inherited from the container.
     * This relationship is read-only and should only be used for displaying data.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function actors(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        // MatterActors refers to a view that also includes the actors inherited from the container. Can only be used to display data
        return $this->hasMany(MatterActors::class);
    }

    /**
     * This relation is very useful as it allows us, using the pivot model, to access the role of the actor in the matter and filter any actor following our needs
     * It doesn't replace the belongs to many relation, but allow us to return a relation with only one item instead of an Actor
     * By doing that, we can eager-load the relation
     */
    public function actorPivot(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ActorPivot::class);
    }

    /**
     * Get the client actor for this matter.
     *
     * Returns the client actor using the MatterActors view.
     * IMPORTANT: Used in MatterPolicy - do not modify without checking authorization logic.
     * Returns a default empty model if no client exists.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function client()
    {
        // Used in Policies - do not change without checking MatterPolicy
        return $this->hasOne(MatterActors::class)->whereRoleCode(ActorRole::CLIENT->value)->withDefault();
    }

    /**
     * We check for the client using our pivot table.
     * We use the HasActorsFromRole trait to avoid repeating the same code
     *
     * @return \App\Models\Actor|null
     */
    public function clientFromLnk(): ?MatterActors
    {
        return $this->getActorFromRole(ActorRole::CLIENT->value);
    }

    /**
     * We check for the payor using our pivot table.
     * We use the HasActorsFromRole trait to avoid repeating the same code
     *
     * @return \App\Models\Actor|null
     */
    public function payor(): ?MatterActors
    {
        return $this->getActorFromRole(ActorRole::PAYOR->value);
    }

    /**
     * Get the delegate actor(s) for this matter.
     *
     * Delegates are actors authorized to represent the client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function delegate()
    {
        return $this->actors()->whereRoleCode(ActorRole::DELEGATE->value);
    }

    /**
     * Get the contact actor(s) for this matter.
     *
     * Contacts are designated communication points for the matter.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contact()
    {
        return $this->actors()->whereRoleCode(ActorRole::CONTACT->value);
    }

    /**
     * Get the applicant actor(s) for this matter.
     *
     * Applicants are the entities applying for the IP right.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function applicants()
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
     * @return \Illuminate\Database\Eloquent\Collection The belongsToMany relationship for owners.
     */
    public function applicantsFromLnk(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getActorsFromRole(ActorRole::APPLICANT->value);
    }

    /**
     * This method returns a collection of actors matching the role 'OWN' for the matter.
     * using the matter_actor_lnk table and filtering by the 'OWN' role.
     *
     * @return \Illuminate\Database\Eloquent\Collection The belongsToMany relationship for owners.
     */
    public function owners(): \Illuminate\Database\Eloquent\Collection
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
        $names = $this->owners->pluck('name')->toArray();

        return implode('; ', $names);
    }

    /**
     * Get the inventor actor(s) for this matter.
     *
     * Inventors are the individuals who created the invention.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inventors()
    {
        return $this->hasMany(MatterActors::class)
            ->whereRoleCode(ActorRole::INVENTOR->value);
    }

    /**
     * We check for the agent using our pivot table. Also known as Primary Agent
     * We use the HasActorsFromRole trait to avoid repeating the same code
     *
     * @return \App\Models\Actor|null
     */
    public function agent(): ?MatterActors
    {
        return $this->getActorFromRole(ActorRole::AGENT->value);
    }

    /**
     * We check for the secondary agent using our pivot table.
     * We use the HasActorsFromRole trait to avoid repeating the same code
     *
     * @return \App\Models\Actor|null
     */
    public function secondaryAgent(): ?MatterActors
    {
        return $this->getActorFromRole(ActorRole::SECONDARY_AGENT->value);
    }

    /**
     * We check for the writer using our pivot table.
     * We use the HasActorsFromRole trait to avoid repeating the same code
     *
     * @return \App\Models\Actor|null
     */
    public function writer(): ?MatterActors
    {
        return $this->getActorFromRole(ActorRole::WRITER->value);
    }

    /**
     * Here, we check for the annuityAgent using our pivot table
     * We use the HasActorsFromRole trait to avoid repeating the same code
     *
     * @return \App\Models\Actor|null
     */
    public function annuityAgent(): ?MatterActors
    {
        return $this->getActorFromRole(ActorRole::ANNUITY_AGENT->value);
    }

    /**
     * Get the responsible actor for the matter.
     * We must name the method "responsibleActor" to avoid conflicts with the "responsible" attribute.
     */
    public function responsibleActor(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Actor::class, 'login', 'responsible');
    }

    /**
     * Get all events for this matter.
     *
     * Events represent important dates and milestones in the matter's lifecycle.
     * Results are ordered chronologically by event date.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function events()
    {
        return $this->hasMany(Event::class)
            ->orderBy('event_date');
    }

    /**
     * Get the filing event for this matter.
     *
     * The filing event represents when the application was filed.
     * Returns a default empty model if no filing event exists.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function filing()
    {
        return $this->hasOne(Event::class)
            ->whereCode(EventCode::FILING->value)->withDefault();
    }

    /**
     * Get the parent filing event(s) for this matter.
     *
     * Parent filing events represent the filing dates of priority applications.
     * Returns a default empty model if no parent filing events exist.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function parentFiling()
    {
        return $this->hasMany(Event::class)
            ->whereCode(EventCode::PCT_FILING->value)->withDefault();
    }

    /**
     * Get the publication event for this matter.
     *
     * The publication event represents when the application was published.
     * Returns a default empty model if no publication event exists.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function publication()
    {
        return $this->hasOne(Event::class)
            ->whereCode(EventCode::PUBLICATION->value)->withDefault();
    }

    /**
     * Get the grant or registration event for this matter.
     *
     * Returns either a grant (for patents) or registration (for trademarks/designs) event.
     * Returns a default empty model if neither event exists.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function grant()
    {
        return $this->hasOne(Event::class)
            ->whereIn('code', [EventCode::GRANT->value, EventCode::REGISTRATION->value])->withDefault();
    }

    /**
     * Get the registration event for this matter.
     *
     * The registration event represents when a trademark or design was registered.
     * Returns a default empty model if no registration event exists.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function registration()
    {
        return $this->hasOne(Event::class)
            ->whereCode(EventCode::REGISTRATION->value)->withDefault();
    }

    /**
     * Get the national phase entry event for this matter.
     *
     * The entry event represents when a PCT application entered the national phase.
     * Returns a default empty model if no entry event exists.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function entered()
    {
        return $this->hasOne(Event::class)
            ->whereCode(EventCode::ENTRY->value)->withDefault();
    }

    /**
     * Get all priority events for this matter.
     *
     * Priority events link this matter to its priority applications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function priority()
    {
        return $this->hasMany(Event::class)
            ->whereCode(EventCode::PRIORITY->value);
    }

    /**
     * Get priority events using the event link view.
     *
     * Uses the EventLnkList view which provides a flattened representation of event links.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prioritiesFromView()
    {
        return $this->hasMany(EventLnkList::class, 'matter_id', 'id')
            ->where('code', EventCode::PRIORITY->value);
    }

    /**
     * Get all tasks for this matter, including renewals and completed tasks.
     *
     * Tasks are reminders and deadlines generated from events.
     * Uses a has-many-through relationship via the event table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function tasks()
    {
        return $this->hasManyThrough(Task::class, Event::class, 'matter_id', 'trigger_id', 'id');
    }

    /**
     * Get pending tasks excluding renewals.
     *
     * Returns uncompleted tasks ordered by due date, excluding renewal tasks.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function tasksPending()
    {
        return $this->tasks()
            ->where('task.code', '!=', EventCode::RENEWAL->value)
            ->whereDone(0)
            ->orderBy('due_date');
    }

    /**
     * Get pending renewal tasks.
     *
     * Returns uncompleted renewal tasks ordered by due date.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function renewalsPending()
    {
        return $this->tasks()
            ->where('task.code', EventCode::RENEWAL->value)
            ->whereDone(0)
            ->orderBy('due_date');
    }

    /**
     * Get all classifiers not shown in the main display.
     *
     * Uses the MatterClassifiers view which includes classifiers inherited from the container.
     * Excludes main display classifiers (titles).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function classifiers()
    {
        return $this->hasMany(MatterClassifiers::class)
            ->whereMainDisplay(0);
    }

    /**
     * Get classifiers directly attached to this matter.
     *
     * Returns only native classifiers, not inherited ones.
     * Typically used for container matters.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function classifiersNative()
    {
        return $this->hasMany(Classifier::class);
    }

    /**
     * Get all title classifiers for this matter.
     *
     * Uses the MatterClassifiers view which includes titles inherited from the container.
     * Includes only main display classifiers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function titles()
    {
        return $this->hasMany(MatterClassifiers::class)
            ->whereMainDisplay(1);
    }

    /**
     * Get matters that link to this matter via classifiers.
     *
     * Returns matters that reference this matter through the classifier table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function linkedBy()
    {
        return $this->belongsToMany(Matter::class, 'classifier', 'lnk_matter_id');
    }

    /**
     * Get the country information for this matter.
     *
     * Returns the Country model for the matter's jurisdiction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function countryInfo()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    /**
     * Get the origin country information for this matter.
     *
     * Returns the Country model for the matter's origin.
     * Returns a default empty model if no origin is set.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function originInfo()
    {
        return $this->belongsTo(Country::class, 'origin')->withDefault();
    }

    /**
     * Get the category for this matter.
     *
     * Category represents the IP type (Patent, Trademark, Design, etc.).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the matter type.
     *
     * Matter type provides additional classification within a category.
     * Returns a default empty model if no type is set.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(MatterType::class)->withDefault();
    }

    /**
     * Build a filtered query for matters with complex joins and filtering.
     *
     * This method delegates to MatterRepository for the actual query building.
     * Kept for backwards compatibility with existing code.
     *
     * @deprecated Use MatterRepository::filter() instead for new code
     * @param string $sortkey The column to sort by (default: 'id')
     * @param string $sortdir The sort direction 'asc' or 'desc' (default: 'desc')
     * @param array $multi_filter Associative array of filter criteria keyed by column name
     * @param string|bool $display_with Filter by category display_with value (optional)
     * @param bool $include_dead Whether to include dead families (default: false)
     * @return \Illuminate\Database\Eloquent\Builder The filtered query builder instance
     */
    public static function filter($sortkey = 'id', $sortdir = 'desc', $multi_filter = [], $display_with = false, $include_dead = false)
    {
        return app(\App\Repositories\MatterRepository::class)->filter(
            $sortkey,
            $sortdir,
            $multi_filter,
            $display_with,
            $include_dead
        );
    }

    /**
     * Get categories with their matter counts filtered by user and request parameters.
     *
     * This method delegates to MatterRepository for the actual query building.
     * Kept for backwards compatibility with existing code.
     *
     * @deprecated Use MatterRepository::getCategoryMatterCount() instead for new code
     * @return \Illuminate\Support\Collection Collection of Category models with 'total' count
     */
    public static function getCategoryMatterCount()
    {
        $whatTasks = request()->input('what_tasks');

        return app(\App\Repositories\MatterRepository::class)->getCategoryMatterCount($whatTasks);
    }

    // ========================================================================
    // The following methods were extracted to MatterRepository in Phase 4.
    // The original implementations have been removed to reduce model size.
    // ========================================================================

    /**
     * Generate a formatted description of the matter for documents or correspondence.
     *
     * Creates a human-readable description including reference numbers, filing/grant dates,
     * publication details, titles, and applicant names. Supports French and English languages
     * with appropriate formatting for patents and trademarks.
     *
     * @param string $lang Language code ('en' or 'fr', default: 'en')
     * @return array Array of description lines
     */
    public function getDescription($lang = 'en')
    {
        $description = [];
        // $matter = Matter::find($id);
        $filed_date = Carbon::parse($this->filing->event_date);
        // "grant" includes registration (for trademarks)
        $granted_date = Carbon::parse($this->grant->event_date);
        $published_date = Carbon::parse($this->publication->event_date);
        $title = $this->titles->where('type_code', ClassifierType::TITLE_OFFICIAL->value)->first()->value
            ?? $this->titles->first()->value
            ?? '';
        $title_EN = $this->titles->where('type_code', ClassifierType::TITLE_EN->value)->first()->value
            ?? $this->titles->first()->value
            ?? '';
        if ($lang == 'fr') {
            $description[] = "N/réf : {$this->uid}";
            if ($this->client->actor_ref) {
                $description[] = "V/réf : {$this->client->actor_ref}";
            }
            if ($this->category_code == CategoryCode::PATENT->value) {
                if ($granted_date) {
                    $description[] = "Brevet {$this->grant->detail} déposé en {$this->countryInfo->name_FR} le {$filed_date->locale('fr_FR')->isoFormat('LL')} et délivré le {$granted_date->locale('fr_FR')->isoFormat('LL')}";
                } else {
                    $line = "Demande de brevet {$this->filing->detail} déposée en {$this->countryInfo->name_FR} le {$filed_date->locale('fr_FR')->isoFormat('LL')}";
                    if ($published_date) {
                        $line .= " et publiée le {$published_date->locale('fr_FR')->isoFormat('LL')} sous le n°{$this->publication->detail}";
                    }
                    $description[] = $line;
                }
                $description[] = "Pour : $title";
                $description[] = "Au nom de : {$this->applicants->pluck('name')->join(', ')}";
            }
            if ($this->category_code == CategoryCode::TRADEMARK->value) {
                $line = "Marque {$this->filing->detail} déposée en {$this->countryInfo->name_FR} le {$filed_date->locale('fr_FR')->isoFormat('LL')}";
                if ($published_date) {
                    $line .= ", publiée le {$published_date->locale('fr_FR')->isoFormat('LL')} sous le n°{$this->publication->detail}";
                }
                if ($granted_date) {
                    $line .= " et enregistrée le {$granted_date->locale('fr_FR')->isoFormat('LL')}";
                }
                $description[] = $line;
                $description[] = "Pour : $title";
                $description[] = "Au nom de : {$this->applicants->pluck('name')->join(', ')}";
            }
        }
        if ($lang == 'en') {
            $description[] = "Our ref: {$this->uid}";
            if ($this->client->actor_ref) {
                $description[] = "Your ref: {$this->client->actor_ref}";
            }
            if ($this->category_code == CategoryCode::PATENT->value) {
                if ($granted_date) {
                    $description[] = "Patent {$this->grant->detail} filed in {$this->countryInfo->name} on {$filed_date->locale('en_US')->isoFormat('LL')} and granted on {$granted_date->locale('en_US')->isoFormat('LL')}";
                } else {
                    $description[] = "Patent application {$this->filing->detail} filed in {$this->countryInfo->name} on {$filed_date->locale('en_US')->isoFormat('LL')}";
                    if ($published_date) {
                        $description[] = " and published on {$published_date->locale('en_US')->isoFormat('LL')} as {$this->publication->detail}";
                    }
                }
                $description[] = "For: $title_EN";
                $description[] = "In name of: {$this->applicants->pluck('name')->join(', ')}";
            }
            if ($this->category_code == CategoryCode::TRADEMARK->value) {
                $line = "Trademark {$this->filing->detail} filed in {$this->countryInfo->name_FR} on {$filed_date->locale('en_US')->isoFormat('LL')}";
                if ($published_date) {
                    $line .= ", published on {$published_date->locale('en_US')->isoFormat('LL')} as {$this->publication->detail}";
                }
                if ($granted_date) {
                    $line .= " and registered on {$granted_date->locale('en_US')->isoFormat('LL')}";
                }
                $description[] = $line;
                $description[] = "For: $title_EN";
                $description[] = "In name of: {$this->applicants->pluck('name')->join(', ')}";
            }
        }

        return $description;
    }

    /**
     * Get the billing address for the matter.
     *
     * This method retrieves the billing address by combining the address parts
     * from the client and the payor associated with the matter. It filters out
     * any null values and ensures unique address parts before concatenating them
     * into a single string separated by newline characters.
     *
     * @return string The concatenated billing address.
     */
    public function getBillingAddress(): string
    {
        // Retrieve the client associated with the matter from the pivot table.
        $client = $this->clientFromLnk();

        // Retrieve the payor associated with the matter from the pivot table.
        $payor = $this->payor();

        // Collect the address parts from the payor and client, filter out null values, and ensure uniqueness.
        $addressParts = collect([
            $payor?->name,
            $payor?->actor?->address,
            $payor?->actor?->country,
            $client?->name,
            $client?->actor?->address_billing ?? $client?->actor?->address,
            $client?->actor?->country_billing ?? $client?->actor?->country,
        ])->filter()->unique();

        // Concatenate the address parts into a single string separated by newline characters.
        return $addressParts->implode("\n");
    }

    /**
     * Get the name of the owner or applicant of the current matter
     * Used for the document merge
     */
    public function getOwnerName(): ?string
    {
        $owners = $this->owners()->pluck('name')->unique();

        if ($owners->isNotEmpty()) {
            return $owners->implode("\n");
        }

        return $this->applicantsFromLnk()->pluck('name')->unique()->implode("\n");
    }

    /**
     * Scope to filter matters by team membership.
     *
     * Filters matters to show only those where the responsible user is
     * the authenticated user or one of their direct/indirect reports.
     *
     * @param  Builder  $query
     * @param  int|null  $userId  Optional user ID (defaults to authenticated user)
     * @return Builder
     */
    public function scopeForTeam(Builder $query, ?int $userId = null): Builder
    {
        $userId = $userId ?? Auth::id();

        if (! $userId) {
            return $query;
        }

        $teamService = app(TeamService::class);
        $teamLogins = $teamService->getSubordinateLogins($userId, true);

        return $query->whereIn('responsible', $teamLogins);
    }

    /**
     * Scope to filter matters by a specific user (responsible).
     *
     * @param  Builder  $query
     * @param  string  $login  The user login to filter by
     * @return Builder
     */
    public function scopeForUser(Builder $query, string $login): Builder
    {
        return $query->where('responsible', $login);
    }
}
