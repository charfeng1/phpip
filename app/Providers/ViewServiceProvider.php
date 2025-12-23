<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\ClassifierType;
use App\Models\EventName;
use App\Models\MatterType;
use App\Models\Role;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $commentableViews = [
            'category.create' => [Category::class, null],
            'category.show' => [Category::class, 'category'],
            'role.create' => [Role::class, null],
            'role.show' => [Role::class, 'role'],
            'classifier_type.create' => [ClassifierType::class, null],
            'classifier_type.show' => [ClassifierType::class, 'classifier_type'],
            'type.create' => [MatterType::class, null],
            'type.show' => [MatterType::class, 'type'],
            'eventname.create' => [EventName::class, null],
            'eventname.show' => [EventName::class, 'eventname'],
        ];

        View::composer(array_keys($commentableViews), function ($view) use ($commentableViews) {
            $viewName = $view->name();
            if (! isset($commentableViews[$viewName])) {
                return;
            }

            if ($view->offsetExists('tableComments')) {
                return;
            }

            [$modelClass, $modelKey] = $commentableViews[$viewName];
            $model = $modelKey && isset($view[$modelKey]) ? $view[$modelKey] : new $modelClass;

            $view->with('tableComments', $model->getTableComments());
        });
    }
}
