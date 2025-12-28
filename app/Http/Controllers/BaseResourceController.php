<?php

namespace App\Http\Controllers;

use App\Traits\Filterable;
use App\Traits\HandlesAuditFields;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Base controller for resource CRUD operations.
 *
 * Provides common patterns extracted from resource controllers:
 * - Audit field handling (creator/updater)
 * - JSON response handling
 * - Pagination with configuration
 * - Date parsing with locale support
 * - Standard response patterns
 *
 * Child controllers should extend this and override methods as needed.
 *
 * Usage:
 * ```php
 * class ActorController extends BaseResourceController
 * {
 *     protected string $paginationConfigKey = 'pagination.actors';
 * }
 * ```
 */
abstract class BaseResourceController extends Controller
{
    use Filterable;
    use HandlesAuditFields;

    /**
     * Configuration key for pagination count.
     * Override in child controllers.
     */
    protected string $paginationConfigKey = 'pagination.default';

    /**
     * Default number of items per page if config key not found.
     */
    protected int $defaultPerPage = 21;

    /**
     * Check if request wants JSON response.
     */
    protected function wantsJson(Request $request): bool
    {
        return $request->wantsJson();
    }

    /**
     * Return JSON response for API requests if applicable.
     *
     * Returns null if not a JSON request, allowing callers to continue
     * with view rendering.
     *
     * @param  Builder|iterable  $data  Query builder or collection
     */
    protected function jsonOrNull(Request $request, $data): ?JsonResponse
    {
        if (! $this->wantsJson($request)) {
            return null;
        }

        $items = $data instanceof Builder ? $data->get() : $data;

        return response()->json($items);
    }

    /**
     * Get pagination count from config.
     */
    protected function getPerPage(): int
    {
        return config($this->paginationConfigKey, $this->defaultPerPage);
    }

    /**
     * Paginate query results with filter parameters appended.
     *
     * @param  Builder  $query  The query builder
     * @param  Request  $request  Current request for query string preservation
     * @param  bool  $simple  Use simplePaginate instead of paginate
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Contracts\Pagination\Paginator
     */
    protected function paginateWithQueryString(Builder $query, Request $request, bool $simple = false)
    {
        $perPage = $this->getPerPage();

        $results = $simple
            ? $query->simplePaginate($perPage)
            : $query->paginate($perPage);

        return $results->withQueryString();
    }

    /**
     * Parse a locale-formatted date from request.
     *
     * Uses Carbon's locale ISO format (L) which adapts to app locale.
     *
     * @param  Request  $request  The HTTP request
     * @param  string  $field  The date field name
     * @return Carbon|null Parsed date or null if field not filled
     *
     * @throws ValidationException If date format is invalid
     */
    protected function parseLocaleDate(Request $request, string $field): ?Carbon
    {
        if (! $request->filled($field)) {
            return null;
        }

        try {
            return Carbon::createFromLocaleIsoFormat('L', app()->getLocale(), $request->input($field));
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                $field => __('Invalid date format'),
            ]);
        }
    }

    /**
     * Parse multiple locale-formatted date fields and merge into request.
     *
     * @param  Request  $request  The HTTP request
     * @param  array<string>  $fields  Array of date field names to parse
     * @return Request Modified request with parsed dates
     *
     * @throws ValidationException If any date format is invalid
     */
    protected function parseDateFields(Request $request, array $fields): Request
    {
        foreach ($fields as $field) {
            $parsed = $this->parseLocaleDate($request, $field);
            if ($parsed !== null) {
                $request->merge([$field => $parsed]);
            }
        }

        return $request;
    }

    /**
     * Standard store operation pattern.
     *
     * Merges creator, filters data, and creates model.
     *
     * @param  Request  $request  The HTTP request
     * @param  string  $modelClass  Fully qualified model class name
     * @param  array<string>  $excludeFields  Additional fields to exclude from mass assignment
     * @return Model The created model
     */
    protected function performStore(Request $request, string $modelClass, array $excludeFields = []): Model
    {
        $this->mergeCreator($request);

        return $modelClass::create($this->getFilteredData($request, $excludeFields));
    }

    /**
     * Standard update operation pattern.
     *
     * Merges updater, filters data, and updates model.
     *
     * @param  Request  $request  The HTTP request
     * @param  Model  $model  The model to update
     * @param  array<string>  $excludeFields  Additional fields to exclude from mass assignment
     * @return Model The updated model
     */
    protected function performUpdate(Request $request, Model $model, array $excludeFields = []): Model
    {
        $this->mergeUpdater($request);
        $model->update($this->getFilteredData($request, $excludeFields));

        return $model;
    }

    /**
     * Standard destroy operation pattern.
     *
     * Deletes model and returns it.
     *
     * @param  Model  $model  The model to delete
     * @return Model The deleted model
     */
    protected function performDestroy(Model $model): Model
    {
        $model->delete();

        return $model;
    }

    /**
     * Return a JSON success response.
     *
     * @param  string  $message  Success message
     * @param  int  $status  HTTP status code
     */
    protected function jsonSuccess(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => $message], $status);
    }

    /**
     * Return a JSON error response.
     *
     * @param  string  $message  Error message
     * @param  int  $status  HTTP status code
     */
    protected function jsonError(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['error' => $message], $status);
    }

    /**
     * Return a JSON redirect response.
     *
     * Used for SPA-like navigation after form submission.
     *
     * @param  string  $url  The URL to redirect to
     */
    protected function jsonRedirect(string $url): JsonResponse
    {
        return response()->json(['redirect' => $url]);
    }

    /**
     * Return model as JSON with 201 Created status.
     *
     * @param  Model  $model  The created model
     */
    protected function jsonCreated(Model $model): JsonResponse
    {
        return response()->json($model, 201);
    }
}
