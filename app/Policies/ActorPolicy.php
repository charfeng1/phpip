<?php

namespace App\Policies;

use App\Models\Actor;
use App\Models\User;
use App\Traits\HasPolicyAuthorization;

class ActorPolicy
{
    use HasPolicyAuthorization;

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
