<?php

namespace App\Traits\Matter;

use App\Enums\EventCode;
use App\Models\Event;
use App\Models\EventLnkList;
use App\Models\Matter;
use App\Models\Task;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Trait for Matter event and task relationships.
 */
trait HasEvents
{
    /**
     * Get all events for this matter.
     *
     * Events represent important dates and milestones in the matter's lifecycle.
     * Results are ordered chronologically by event date.
     *
     * @return HasMany
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class)
            ->orderBy('event_date');
    }

    /**
     * Get the filing event for this matter.
     *
     * The filing event represents when the application was filed.
     * Returns a default empty model if no filing event exists.
     *
     * @return HasOne
     */
    public function filing(): HasOne
    {
        return $this->hasOne(Event::class)
            ->whereCode(EventCode::FILING->value)->withDefault();
    }

    /**
     * Get the parent filing event(s) for this matter.
     *
     * Parent filing events represent the filing dates of priority applications.
     *
     * @return HasMany
     */
    public function parentFiling(): HasMany
    {
        return $this->hasMany(Event::class)
            ->whereCode(EventCode::PCT_FILING->value);
    }

    /**
     * Get the publication event for this matter.
     *
     * The publication event represents when the application was published.
     * Returns a default empty model if no publication event exists.
     *
     * @return HasOne
     */
    public function publication(): HasOne
    {
        return $this->hasOne(Event::class)
            ->whereCode(EventCode::PUBLICATION->value)->withDefault();
    }

    /**
     * Get the grant or registration event for this matter.
     *
     * Returns either a grant (for patents) or registration (for trademarks/designs) event.
     * Returns a default empty model if neither event exists.
     *
     * @return HasOne
     */
    public function grant(): HasOne
    {
        return $this->hasOne(Event::class)
            ->whereIn('code', [EventCode::GRANT->value, EventCode::REGISTRATION->value])->withDefault();
    }

    /**
     * Get the registration event for this matter.
     *
     * The registration event represents when a trademark or design was registered.
     * Returns a default empty model if no registration event exists.
     *
     * @return HasOne
     */
    public function registration(): HasOne
    {
        return $this->hasOne(Event::class)
            ->whereCode(EventCode::REGISTRATION->value)->withDefault();
    }

    /**
     * Get the national phase entry event for this matter.
     *
     * The entry event represents when a PCT application entered the national phase.
     * Returns a default empty model if no entry event exists.
     *
     * @return HasOne
     */
    public function entered(): HasOne
    {
        return $this->hasOne(Event::class)
            ->whereCode(EventCode::ENTRY->value)->withDefault();
    }

    /**
     * Get all priority events for this matter.
     *
     * Priority events link this matter to its priority applications.
     *
     * @return HasMany
     */
    public function priority(): HasMany
    {
        return $this->hasMany(Event::class)
            ->whereCode(EventCode::PRIORITY->value);
    }

    /**
     * Get external matters claiming priority from this matter.
     *
     * Returns matters outside this family that have priority events linking to this matter.
     * Note: The where clause is ignored during eager loading.
     *
     * @return BelongsToMany
     */
    public function priorityTo(): BelongsToMany
    {
        // Gets external matters claiming priority on this one (where clause is ignored by eager loading)
        return $this->belongsToMany(Matter::class, 'event', 'alt_matter_id')
            ->where('caseref', '!=', $this->caseref)
            ->orderBy('caseref')
            ->orderBy('origin')
            ->orderBy('country')
            ->orderBy('type_code')
            ->orderBy('idx');
    }

    /**
     * Get priority events using the event link view.
     *
     * Uses the EventLnkList view which provides a flattened representation of event links.
     *
     * @return HasMany
     */
    public function prioritiesFromView(): HasMany
    {
        return $this->hasMany(EventLnkList::class, 'matter_id', 'id')
            ->where('code', EventCode::PRIORITY->value);
    }

    /**
     * Get all tasks for this matter, including renewals and completed tasks.
     *
     * Tasks are reminders and deadlines generated from events.
     * Uses a has-many-through relationship via the event table.
     *
     * @return HasManyThrough
     */
    public function tasks(): HasManyThrough
    {
        return $this->hasManyThrough(Task::class, Event::class, 'matter_id', 'trigger_id', 'id');
    }

    /**
     * Get pending tasks excluding renewals.
     *
     * Returns uncompleted tasks ordered by due date, excluding renewal tasks.
     *
     * @return HasManyThrough
     */
    public function tasksPending(): HasManyThrough
    {
        return $this->tasks()
            ->where('task.code', '!=', EventCode::RENEWAL->value)
            ->whereDone(0)
            ->orderBy('due_date');
    }

    /**
     * Get pending renewal tasks.
     *
     * Returns uncompleted renewal tasks ordered by due date.
     *
     * @return HasManyThrough
     */
    public function renewalsPending(): HasManyThrough
    {
        return $this->tasks()
            ->where('task.code', EventCode::RENEWAL->value)
            ->whereDone(0)
            ->orderBy('due_date');
    }
}
