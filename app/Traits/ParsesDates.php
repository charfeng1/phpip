<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Provides date parsing utilities for controllers.
 *
 * Handles locale-aware date parsing from request input, converting
 * localized date strings to Carbon instances for database storage.
 *
 * Usage:
 * 1. Use this trait in your controller
 * 2. Call parseLocaleDate() or mergeLocaleDates() to parse date fields
 */
trait ParsesDates
{
    /**
     * Parse a locale-formatted date string to a Carbon instance.
     *
     * @param  string  $dateString  The date string in locale format
     * @param  string|null  $locale  Optional locale (defaults to app locale)
     * @return Carbon The parsed Carbon instance
     *
     * @throws \InvalidArgumentException If the date format is invalid
     */
    protected function parseLocaleDate(string $dateString, ?string $locale = null): Carbon
    {
        $locale = $locale ?? app()->getLocale();

        return Carbon::createFromLocaleIsoFormat('L', $locale, $dateString);
    }

    /**
     * Try to parse a locale-formatted date, returning null on failure.
     *
     * @param  string  $dateString  The date string in locale format
     * @param  string|null  $locale  Optional locale
     * @return Carbon|null The parsed Carbon instance or null on failure
     */
    protected function tryParseLocaleDate(string $dateString, ?string $locale = null): ?Carbon
    {
        try {
            return $this->parseLocaleDate($dateString, $locale);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse and merge a date field from request if present.
     *
     * @param  Request  $request  The HTTP request
     * @param  string  $field  The field name to parse
     * @return bool True if successfully parsed and merged, false otherwise
     */
    protected function mergeParsedDate(Request $request, string $field): bool
    {
        if (! $request->filled($field)) {
            return false;
        }

        $parsed = $this->tryParseLocaleDate($request->input($field));

        if ($parsed === null) {
            return false;
        }

        $request->merge([$field => $parsed]);

        return true;
    }

    /**
     * Parse and merge multiple date fields from request.
     *
     * @param  Request  $request  The HTTP request
     * @param  array  $fields  Array of field names to parse
     * @return array Array of fields that failed to parse
     */
    protected function mergeParsedDates(Request $request, array $fields): array
    {
        $failed = [];

        foreach ($fields as $field) {
            if ($request->filled($field) && ! $this->mergeParsedDate($request, $field)) {
                $failed[] = $field;
            }
        }

        return $failed;
    }

    /**
     * Parse a date field or return a JSON error response.
     *
     * @param  Request  $request  The HTTP request
     * @param  string  $field  The field name to parse
     * @return \Illuminate\Http\JsonResponse|null Error response if parsing fails, null on success
     */
    protected function parseDateOrFail(Request $request, string $field): ?\Illuminate\Http\JsonResponse
    {
        if (! $request->filled($field)) {
            return null;
        }

        if (! $this->mergeParsedDate($request, $field)) {
            return response()->json(['error' => 'Invalid date format'], 422);
        }

        return null;
    }
}
