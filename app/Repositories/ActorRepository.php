<?php

namespace App\Repositories;

use App\Models\Actor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for Actor-related database queries.
 *
 * Centralizes actor lookup and search queries that were previously scattered
 * across models and services. Provides phonetic matching and common lookups.
 *
 * Phase 4 refactoring: Extracted from Actor model and various services.
 */
class ActorRepository
{
    /**
     * Find an actor by ID.
     *
     * @param int $id
     * @return Actor|null
     */
    public function find(int $id): ?Actor
    {
        return Actor::find($id);
    }

    /**
     * Find an actor by ID with company relationship.
     *
     * @param int $id
     * @return Actor|null
     */
    public function findWithCompany(int $id): ?Actor
    {
        return Actor::with('company')->find($id);
    }

    /**
     * Find actors by their IDs.
     *
     * @param array $ids
     * @return Collection
     */
    public function findMany(array $ids): Collection
    {
        return Actor::whereIn('id', $ids)->get();
    }

    /**
     * Find an actor by phonetic name matching.
     *
     * Uses database-specific phonetic matching:
     * - MySQL: SOUNDS LIKE operator
     * - PostgreSQL: soundex() function
     * - Other: Case-insensitive exact match fallback
     *
     * @param string $name The name to match phonetically
     * @return Actor|null
     */
    public function findByPhoneticMatch(string $name): ?Actor
    {
        return Actor::phoneticMatch($name)->first();
    }

    /**
     * Get actors matching a phonetic name pattern.
     *
     * @param string $name The name to match phonetically
     * @return Collection
     */
    public function getByPhoneticMatch(string $name): Collection
    {
        return Actor::phoneticMatch($name)->get();
    }

    /**
     * Find an actor by exact name.
     *
     * @param string $name
     * @return Actor|null
     */
    public function findByName(string $name): ?Actor
    {
        return Actor::where('name', $name)->first();
    }

    /**
     * Find an actor by name pattern.
     *
     * @param string $pattern Name pattern with wildcards
     * @return Actor|null
     */
    public function findByNameLike(string $pattern): ?Actor
    {
        return Actor::where('name', 'LIKE', $pattern)->first();
    }

    /**
     * Get actors by name prefix.
     *
     * @param string $prefix Name prefix to search
     * @param int $limit Maximum results to return
     * @return Collection
     */
    public function getByNamePrefix(string $prefix, int $limit = 10): Collection
    {
        return Actor::where('name', 'LIKE', "$prefix%")
            ->limit($limit)
            ->get();
    }

    /**
     * Get email addresses for actors by their IDs.
     *
     * @param array $ids Actor IDs
     * @return array Array of email addresses
     */
    public function getEmailsByIds(array $ids): array
    {
        return Actor::whereIn('id', $ids)
            ->whereNotNull('email')
            ->pluck('email')
            ->toArray();
    }

    /**
     * Query builder for actors with name filter.
     *
     * @param string|null $name Name filter (prefix match)
     * @return Builder
     */
    public function query(?string $name = null): Builder
    {
        $query = Actor::query();

        if ($name) {
            $query->where('name', 'LIKE', "$name%");
        }

        return $query;
    }

    /**
     * Get actors with their company relationship, optionally filtered.
     *
     * @param string|null $name Name filter
     * @param string|null $selector Filter selector (phy, com, cli)
     * @return Builder
     */
    public function queryWithCompany(?string $name = null, ?string $selector = null): Builder
    {
        $query = Actor::with('company');

        if ($name) {
            $query->where('name', 'LIKE', "$name%");
        }

        if ($selector) {
            $query = $this->applySelectorFilter($query, $selector);
        }

        return $query;
    }

    /**
     * Apply selector filter to query.
     *
     * @param Builder $query
     * @param string $selector
     * @return Builder
     */
    protected function applySelectorFilter(Builder $query, string $selector): Builder
    {
        return match ($selector) {
            'phy' => $query->where('phy_person', true),
            'com' => $query->whereNotNull('company_id'),
            'cli' => $query->whereNotNull('email'),
            default => $query,
        };
    }

    /**
     * Create a new actor.
     *
     * @param array $data
     * @return Actor
     */
    public function create(array $data): Actor
    {
        return Actor::create($data);
    }

    /**
     * Update an actor.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        return Actor::where('id', $id)->update($data) > 0;
    }
}
