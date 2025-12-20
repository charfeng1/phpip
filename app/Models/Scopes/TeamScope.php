<?php

namespace App\Models\Scopes;

use App\Enums\UserRole;
use App\Services\TeamService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Global scope for filtering records by team membership.
 *
 * When applied to a model, this scope filters records to show only those
 * belonging to the authenticated user or their subordinates (direct and indirect reports).
 *
 * This scope is designed to work with models that have a 'responsible' field
 * (like Matter) or an 'assigned_to' field (like Task) that references user logins.
 */
class TeamScope implements Scope
{
    /**
     * The field name that contains the responsible user's login.
     *
     * @var string
     */
    protected string $responsibleField;

    /**
     * Create a new TeamScope instance.
     *
     * @param  string  $responsibleField  The field name containing the user login
     */
    public function __construct(string $responsibleField = 'responsible')
    {
        $this->responsibleField = $responsibleField;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  Builder  $builder
     * @param  Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        // Admin users (DBA/DBRW/DBRO) see all records
        if (in_array($user->default_role, UserRole::readableRoleValues(), true)) {
            return;
        }

        // Get team service to fetch subordinate logins
        $teamService = app(TeamService::class);
        $teamLogins = $teamService->getSubordinateLogins($user->id, true);

        // Filter by team logins
        $builder->whereIn($this->responsibleField, $teamLogins);
    }
}
