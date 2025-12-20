<?php

namespace App\Policies;

use App\Models\EventName;
use App\Models\User;

/**
 * Authorization policy for EventName model.
 *
 * Event names define event types - critical system configuration:
 * - Admin (DBA) can manage event types
 * - Read-write users (DBRW) can view event types
 * - Read-only users (DBRO) can view event types
 * - Clients (CLI) cannot access event type configuration
 */
class EventNamePolicy
{
    protected function isAdmin(User $user): bool
    {
        return $user->default_role === 'DBA';
    }

    protected function canRead(User $user): bool
    {
        return in_array($user->default_role, ['DBA', 'DBRW', 'DBRO'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->canRead($user);
    }

    public function view(User $user, EventName $eventName): bool
    {
        return $this->canRead($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, EventName $eventName): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, EventName $eventName): bool
    {
        return $this->isAdmin($user);
    }
}
