<?php

namespace App\Providers;

use App\Models\Actor;
use App\Models\Category;
use App\Models\Classifier;
use App\Models\Country;
use App\Models\Event;
use App\Models\EventName;
use App\Models\Fee;
use App\Models\Matter;
use App\Models\RenewalsLog;
use App\Models\Rule;
use App\Models\Task;
use App\Models\TemplateClass;
use App\Models\TemplateMember;
use App\Models\User;
use App\Policies\ActorPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ClassifierPolicy;
use App\Policies\CountryPolicy;
use App\Policies\EventNamePolicy;
use App\Policies\EventPolicy;
use App\Policies\FeePolicy;
use App\Policies\MatterPolicy;
use App\Policies\RenewalPolicy;
use App\Policies\RulePolicy;
use App\Policies\TaskPolicy;
use App\Policies\TemplateClassPolicy;
use App\Policies\TemplateMemberPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Matter::class => MatterPolicy::class,
        Actor::class => ActorPolicy::class,
        Task::class => TaskPolicy::class,
        User::class => UserPolicy::class,
        Fee::class => FeePolicy::class,
        RenewalsLog::class => RenewalPolicy::class,
        Rule::class => RulePolicy::class,
        Event::class => EventPolicy::class,
        Classifier::class => ClassifierPolicy::class,
        EventName::class => EventNamePolicy::class,
        Category::class => CategoryPolicy::class,
        Country::class => CountryPolicy::class,
        TemplateClass::class => TemplateClassPolicy::class,
        TemplateMember::class => TemplateMemberPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
