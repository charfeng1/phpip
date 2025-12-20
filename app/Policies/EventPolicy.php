<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\Matter;
use App\Models\User;
use App\Traits\HasPolicyAuthorization;

/**
 * Authorization policy for Event model.
 *
 * Events are milestones in a matter's lifecycle:
 * - Admin (DBA) and read-write users (DBRW) can manage events
 * - Read-only users (DBRO) can view events
 * - Clients (CLI) can view events for their own matters
 */
class EventPolicy
{
    use HasPolicyAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canRead($user) || $this->isClient($user);
    }

    public function view(User $user, Event $event): bool
    {
        if ($this->canRead($user)) {
            return true;
        }

        // Client users can view events for their own matters
        if ($this->isClient($user)) {
            $matter = $event->matter()->first();
            if ($matter instanceof Matter) {
                $clientActor = optional($matter->clientFromLnk())->actor_id;

                return (int) $clientActor === (int) $user->id;
            }
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->canWrite($user);
    }

    public function update(User $user, Event $event): bool
    {
        return $this->canWrite($user);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->canWrite($user);
    }
}
