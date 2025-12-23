<?php

namespace App\Services;

use App\Enums\EventCode;
use App\Models\ActorPivot;
use App\Models\Matter;

/**
 * Service for handling special matter creation operations.
 *
 * Handles three types of matter creation operations:
 * - 'descendant': Create a descendant matter with priority claims or entry events
 * - 'clone': Clone a matter with actors and classifiers
 * - 'new': Create a new matter with a received event
 */
class MatterOperationService
{
    /**
     * Allowed operation types for validation.
     */
    private const ALLOWED_OPERATIONS = ['descendant', 'clone', 'new'];

    /**
     * Handle the post-creation operations for a new matter.
     *
     * @param Matter $newMatter The newly created matter
     * @param string $operation The operation type ('descendant', 'clone', 'new')
     * @param array $data Additional data including parent_id, priority flag
     * @return void
     */
    public function handleOperation(Matter $newMatter, string $operation, array $data = []): void
    {
        // Validate operation type
        if (! in_array($operation, self::ALLOWED_OPERATIONS, true)) {
            return; // Unknown operation - do nothing
        }

        match ($operation) {
            'descendant' => $this->handleDescendant($newMatter, $data),
            'clone' => $this->handleClone($newMatter, $data),
            'new' => $this->handleNew($newMatter),
        };
    }

    /**
     * Handle descendant operation.
     *
     * Creates a descendant matter with:
     * - Copied priority claims from parent
     * - Container_id set from parent
     * - Either a priority claim event OR filing event + entry event
     *
     * @param Matter $newMatter The new descendant matter
     * @param array $data Data containing parent_id and priority flag
     * @return void
     */
    protected function handleDescendant(Matter $newMatter, array $data): void
    {
        $parentId = $data['parent_id'] ?? null;
        $hasPriority = $data['priority'] ?? false;

        $parentMatter = Matter::with('priority', 'filing')->find($parentId);
        if (! $parentMatter) {
            return;
        }

        // Copy priority claims from original matter
        $newMatter->priority()->createMany($parentMatter->priority->toArray());

        // Set container from parent (or use parent as container)
        $newMatter->container_id = $parentMatter->container_id ?? $parentId;

        if ($hasPriority) {
            // Create priority claim event linking to parent
            $newMatter->events()->create([
                'code' => EventCode::PRIORITY->value,
                'alt_matter_id' => $parentId,
            ]);
        } else {
            // Copy filing event (without detail) from parent
            $newMatter->filing()->save($parentMatter->filing->replicate(['detail']));

            // Set parent relationship
            $newMatter->parent_id = $parentId;

            // Create entry event for descendant filing date
            $newMatter->events()->create([
                'code' => EventCode::ENTRY->value,
                'event_date' => now(),
                'detail' => 'Descendant filing date',
            ]);
        }

        $newMatter->save();
    }

    /**
     * Handle clone operation.
     *
     * Creates a clone of a matter with:
     * - Copied priority claims from parent
     * - Copied actors (direct and shared from container)
     * - Copied classifiers (from container or direct)
     *
     * Note: Uses insertOrIgnore for actors to handle unique key constraints
     * from default_actors trigger.
     *
     * @param Matter $newMatter The new cloned matter
     * @param array $data Data containing parent_id
     * @return void
     */
    protected function handleClone(Matter $newMatter, array $data): void
    {
        $parentId = $data['parent_id'] ?? null;

        $parentMatter = Matter::with('priority', 'classifiersNative', 'actorPivot')->find($parentId);
        if (! $parentMatter) {
            return;
        }

        $newMatterId = $newMatter->id;

        // Copy priority claims from original matter
        $newMatter->priority()->createMany($parentMatter->priority->toArray());

        // Copy actors from original matter
        // Cannot use Eloquent relationships because they do not handle unique key constraints
        // - the issue arises for actors that are inserted upon matter creation by a trigger based
        //   on the default_actors table
        $actors = $parentMatter->actorPivot;
        $actors->each(function ($item) use ($newMatterId) {
            $item->matter_id = $newMatterId;
            $item->id = null;
        });
        ActorPivot::insertOrIgnore($actors->toArray());

        if ($parentMatter->container_id) {
            // Copy shared actors and classifiers from original matter's container
            $sharedActors = $parentMatter->container->actorPivot->where('shared', 1);
            $sharedActors->each(function ($item) use ($newMatterId) {
                $item->matter_id = $newMatterId;
                $item->id = null;
            });
            ActorPivot::insertOrIgnore($sharedActors->toArray());

            $newMatter->classifiersNative()
                ->createMany($parentMatter->container->classifiersNative->toArray());
        } else {
            // Copy classifiers from original matter (no container)
            $newMatter->classifiersNative()->createMany($parentMatter->classifiersNative->toArray());
        }
    }

    /**
     * Handle new operation.
     *
     * Creates a simple new matter with a received event.
     *
     * @param Matter $newMatter The new matter
     * @return void
     */
    protected function handleNew(Matter $newMatter): void
    {
        $newMatter->events()->create([
            'code' => EventCode::RECEIVED->value,
            'event_date' => now(),
        ]);
    }

    /**
     * Validate if an operation type is allowed.
     *
     * @param string $operation The operation type to validate
     * @return bool True if operation is allowed, false otherwise
     */
    public function isValidOperation(string $operation): bool
    {
        return in_array($operation, self::ALLOWED_OPERATIONS, true);
    }
}
