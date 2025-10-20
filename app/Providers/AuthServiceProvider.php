<?php

namespace App\Providers;

use App\Models\Actor;
use App\Models\Matter;
use App\Models\Task;
use App\Models\User;
use App\Policies\ActorPolicy;
use App\Policies\MatterPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Matter::class => MatterPolicy::class,
        Actor::class => ActorPolicy::class,
        Task::class => TaskPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
