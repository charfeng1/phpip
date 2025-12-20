<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Actor;
use App\Models\User;

class ActorPolicy
{
    protected function canRead(User $user): bool
    {
        return in_array($user->default_role, UserRole::readableRoleValues(), true);
    }

    protected function canWrite(User $user): bool
    {
        return in_array($user->default_role, UserRole::writableRoleValues(), true);
    }

    public function viewAny(User $user): bool
    {
        return $this->canRead($user);
    }

    public function view(User $user, Actor $actor): bool
    {
        return $this->canRead($user);
    }

    public function create(User $user): bool
    {
        return $this->canWrite($user);
    }

    public function update(User $user, Actor $actor): bool
    {
        return $this->canWrite($user);
    }

    public function delete(User $user, Actor $actor): bool
    {
        return $this->canWrite($user);
    }
}
