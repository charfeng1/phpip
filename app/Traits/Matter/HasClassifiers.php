<?php

namespace App\Traits\Matter;

use App\Models\Classifier;
use App\Models\Matter;
use App\Models\MatterClassifiers;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trait for Matter classifier relationships.
 */
trait HasClassifiers
{
    /**
     * Get all classifiers not shown in the main display.
     *
     * Uses the MatterClassifiers view which includes classifiers inherited from the container.
     * Excludes main display classifiers (titles).
     *
     * @return HasMany
     */
    public function classifiers(): HasMany
    {
        return $this->hasMany(MatterClassifiers::class)
            ->whereMainDisplay(0);
    }

    /**
     * Get classifiers directly attached to this matter.
     *
     * Returns only native classifiers, not inherited ones.
     * Typically used for container matters.
     *
     * @return HasMany
     */
    public function classifiersNative(): HasMany
    {
        return $this->hasMany(Classifier::class);
    }

    /**
     * Get all title classifiers for this matter.
     *
     * Uses the MatterClassifiers view which includes titles inherited from the container.
     * Includes only main display classifiers.
     *
     * @return HasMany
     */
    public function titles(): HasMany
    {
        return $this->hasMany(MatterClassifiers::class)
            ->whereMainDisplay(1);
    }

    /**
     * Get matters that link to this matter via classifiers.
     *
     * Returns matters that reference this matter through the classifier table.
     *
     * @return BelongsToMany
     */
    public function linkedBy(): BelongsToMany
    {
        return $this->belongsToMany(Matter::class, 'classifier', 'lnk_matter_id');
    }
}
