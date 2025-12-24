<?php

namespace App\Traits\Matter;

use App\Models\Classifier;
use App\Models\Matter;
use App\Models\MatterClassifiers;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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
     * Uses hasManyThrough to traverse: this matter → classifiers with lnk_matter_id → their owning matters.
     *
     * @return HasManyThrough
     */
    public function linkedBy(): HasManyThrough
    {
        return $this->hasManyThrough(
            Matter::class,      // Final model we want
            Classifier::class,  // Intermediate model
            'lnk_matter_id',   // Foreign key on classifiers table (points to this matter)
            'id',              // Foreign key on matters table
            'id',              // Local key on this matter
            'matter_id'        // Local key on classifiers table (points to the owning matter)
        );
    }
}
