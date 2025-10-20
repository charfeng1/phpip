<?php

namespace App\Policies;

use App\Models\Matter;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MatterPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the matter.
     *
     * @return mixed
     */
    public function view(User $user, Matter $matter): bool
    {
        // Admin and internal users (DBA/DBRW/DBRO) can view all matters
        if (in_array($user->default_role, ['DBA', 'DBRW', 'DBRO'], true)) {
            return true;
        }

        // Client users only see matters where they are the linked client
        if ($user->default_role === 'CLI' || empty($user->default_role)) {
            $clientActor = optional($matter->clientFromLnk())->actor_id;

            return (int) $clientActor === (int) $user->id;
        }

        return false;
    }
}
