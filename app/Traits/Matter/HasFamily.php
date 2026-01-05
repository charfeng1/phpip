<?php

namespace App\Traits\Matter;

use App\Models\Matter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trait for Matter family relationships.
 */
trait HasFamily
{
    /**
     * Get all family members of this matter.
     *
     * Family members are matters that share the same caseref (family identifier).
     * Results are ordered by origin, country, type, and index.
     */
    public function family(): HasMany
    {
        return $this->hasMany(Matter::class, 'caseref', 'caseref')
            ->orderBy('origin')
            ->orderBy('country')
            ->orderBy('type_code')
            ->orderBy('idx');
    }

    /**
     * Get the container (parent family) of this matter.
     *
     * A container is a matter that groups related cases together.
     * Returns a default empty model if no container exists.
     */
    public function container(): BelongsTo
    {
        return $this->belongsTo(Matter::class, 'container_id')->withDefault();
    }

    /**
     * Get the parent matter from which this matter was derived.
     *
     * Used for tracking priority relationships (e.g., PCT -> National phase).
     * Returns a default empty model if no parent exists.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Matter::class, 'parent_id')->withDefault();
    }

    /**
     * Get all descendants of this matter.
     *
     * Descendants are matters that were derived from this matter (e.g., national phases from PCT).
     * Results are ordered by origin, country, type, and index.
     */
    public function descendants(): HasMany
    {
        return $this->hasMany(Matter::class, 'parent_id')
            ->orderBy('origin')
            ->orderBy('country')
            ->orderBy('type_code')
            ->orderBy('idx');
    }
}
