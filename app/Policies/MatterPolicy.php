<?php

namespace App\Policies;

use App\Models\Matter;
use App\Models\User;
use App\Services\TeamService;
use App\Traits\HasPolicyAuthorization;
use Illuminate\Auth\Access\HandlesAuthorization;

class MatterPolicy
{
    use HandlesAuthorization;
    use HasPolicyAuthorization;

    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    /**
     * Determine whether the user can view the matter.
     *
     * Access control hierarchy:
     * - DBA/DBRW/DBRO: Can view all matters
     * - Internal users: Can view matters they're responsible for OR matters
     *   assigned to their direct/indirect reports (team hierarchy)
     * - CLI (client): Can only view matters where they are the linked client
     *
     * @return mixed
     */
    public function view(User $user, Matter $matter): bool
    {
        // Admin and internal users (DBA/DBRW/DBRO) can view all matters
        if ($this->canRead($user)) {
            return true;
        }

        // Client users only see matters where they are the linked client
        if ($this->isClient($user)) {
            $clientActor = optional($matter->clientFromLnk())->actor_id;

            return (int) $clientActor === (int) $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can view the matter based on team hierarchy.
     *
     * This is used for team-based filtering where users can see:
     * - Their own assigned matters
     * - Matters assigned to their direct and indirect reports
     *
     * @return bool
     */
    public function viewAsTeamMember(User $user, Matter $matter): bool
    {
        // Get the responsible user's login for this matter
        $responsibleLogin = $matter->responsible;

        if (! $responsibleLogin) {
            return false;
        }

        // Check if the user can view this matter based on team hierarchy
        return $this->teamService->canViewUserWorkByLogin($user->id, $responsibleLogin);
    }

    /**
     * Check if user is the responsible party or a supervisor of the responsible party.
     *
     * @return bool
     */
    public function isResponsibleOrSupervisor(User $user, Matter $matter): bool
    {
        $responsibleLogin = $matter->responsible;

        if (! $responsibleLogin) {
            return false;
        }

        // Check if user's login matches the responsible party
        if ($user->login === $responsibleLogin) {
            return true;
        }

        // Check if user is a supervisor of the responsible party
        return $this->teamService->canViewUserWorkByLogin($user->id, $responsibleLogin);
    }
}
