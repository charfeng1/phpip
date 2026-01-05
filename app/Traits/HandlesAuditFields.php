<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Trait for handling audit fields (creator/updater) in requests.
 *
 * Automatically adds the authenticated user's login to requests
 * for creator (on store) and updater (on update) fields.
 */
trait HandlesAuditFields
{
    /**
     * Merge the creator field into the request.
     */
    protected function mergeCreator(Request $request): Request
    {
        if (Auth::check()) {
            $request->merge(['creator' => Auth::user()->login]);
        }

        return $request;
    }

    /**
     * Merge the updater field into the request.
     */
    protected function mergeUpdater(Request $request): Request
    {
        if (Auth::check()) {
            $request->merge(['updater' => Auth::user()->login]);
        }

        return $request;
    }

    /**
     * Get the standard fields to exclude from mass assignment.
     *
     * @return array<string>
     */
    protected function getExcludedFields(): array
    {
        return ['_token', '_method'];
    }

    /**
     * Get filtered request data suitable for model creation/update.
     *
     * @param  array<string>  $additionalExcludes  Additional fields to exclude
     */
    protected function getFilteredData(Request $request, array $additionalExcludes = []): array
    {
        $excludes = array_merge($this->getExcludedFields(), $additionalExcludes);

        return $request->except($excludes);
    }
}
