<?php

namespace App\Providers;

use App\Enums\UserRole;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();
        Gate::define('client', fn ($user) => $user->default_role === UserRole::CLIENT->value || empty($user->default_role));
        Gate::define('except_client', fn ($user) => $user->default_role !== UserRole::CLIENT->value && ! empty($user->default_role));
        Gate::define('admin', fn ($user) => $user->default_role === UserRole::ADMIN->value);
        Gate::define('readwrite', fn ($user) => in_array($user->default_role, UserRole::writableRoleValues()));
        Gate::define('readonly', fn ($user) => in_array($user->default_role, UserRole::readableRoleValues()));

        // Add query macro for case-insensitive JSON column queries
        // Supports both MySQL and PostgreSQL syntax
        \Illuminate\Database\Query\Builder::macro('whereJsonLike', function ($column, $value, $locale = null) {
            if (! $locale) {
                $locale = app()->getLocale();
                // Normalize to base locale (e.g., 'en' from 'en_US')
                $locale = substr($locale, 0, 2);
            }

            // Validate locale is only alphabetic characters to prevent SQL injection
            if (! preg_match('/^[a-zA-Z]{2}$/', $locale)) {
                $locale = 'en'; // Fallback to English if invalid
            }

            $driver = $this->getConnection()->getDriverName();

            if ($driver === 'pgsql') {
                // PostgreSQL: use ->> operator for JSON text extraction and ILIKE for case-insensitive
                return $this->whereRaw(
                    "$column ->> ? ILIKE ?",
                    [$locale, "$value%"]
                );
            }

            // MySQL: use JSON_UNQUOTE(JSON_EXTRACT()) with COLLATE for case-insensitive
            // Locale is validated by regex above - only [a-zA-Z]{2} allowed
            return $this->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT($column, '$.\"{$locale}\"')) COLLATE utf8mb4_0900_ai_ci LIKE ?",
                ["$value%"]
            );
        });
    }
}
