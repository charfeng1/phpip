<?php

namespace App\Models;

use App\Enums\CategoryCode;
use App\Enums\ClassifierType;
use App\Services\TeamService;
use App\Traits\Auditable;
use App\Traits\DatabaseJsonHelper;
use App\Traits\HasActorsFromRole;
use App\Traits\Matter\HasActors;
use App\Traits\Matter\HasClassifiers;
use App\Traits\Matter\HasEvents;
use App\Traits\Matter\HasFamily;
use App\Traits\TrimsCharColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

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
    use HasActors;
    use HasActorsFromRole;
    use HasClassifiers;
    use HasEvents;
    use HasFactory;
    use HasFamily;
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

    protected $casts = [
        'expire_date' => 'date:Y-m-d',
    ];

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
