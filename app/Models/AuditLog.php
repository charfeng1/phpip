<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * AuditLog Model
 *
 * Represents an audit trail entry tracking data changes in the system.
 * Used for compliance and dispute resolution by recording who made what changes and when.
 *
 * Database table: audit_logs
 *
 * Key relationships:
 * - Polymorphic relation to any auditable model (Matter, Event, Task, etc.)
 * - Belongs to a user (via login field)
 *
 * Business logic:
 * - Records all create, update, and delete operations on auditable models
 * - Stores old and new values as JSON for easy comparison
 * - Captures user context (login, name, IP address, user agent)
 * - Provides methods to retrieve human-readable change descriptions
 */
class AuditLog extends Model
{
    /**
     * The database table associated with the model.
     *
     * @var string
     */
    protected $table = 'audit_logs';

    /**
     * Indicates if the model should be timestamped.
     * We only use created_at, no updated_at needed for audit logs.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'action',
        'user_login',
        'user_name',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'created_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the auditable model (polymorphic relation).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Get the user who performed the action.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_login', 'login');
    }

    /**
     * Get a human-readable model name from the auditable_type.
     */
    public function getModelNameAttribute(): string
    {
        return class_basename($this->auditable_type);
    }

    /**
     * Get the changed fields between old and new values.
     */
    public function getChangedFieldsAttribute(): array
    {
        if ($this->action === 'created') {
            return array_keys($this->new_values ?? []);
        }

        if ($this->action === 'deleted') {
            return array_keys($this->old_values ?? []);
        }

        // For updates, find fields that actually changed
        $changedFields = [];
        $oldValues = $this->old_values ?? [];
        $newValues = $this->new_values ?? [];

        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changedFields[] = $key;
            }
        }

        return $changedFields;
    }

    /**
     * Get a summary of changes for display.
     */
    public function getChangeSummaryAttribute(): string
    {
        $changedFields = $this->changed_fields;
        $count = count($changedFields);

        if ($count === 0) {
            return 'No changes';
        }

        if ($count <= 3) {
            return implode(', ', $changedFields);
        }

        return implode(', ', array_slice($changedFields, 0, 3)).' (+'.($count - 3).' more)';
    }

    /**
     * Scope a query to filter by auditable type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModel($query, string $type)
    {
        return $query->where('auditable_type', $type);
    }

    /**
     * Scope a query to filter by user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, string $userLogin)
    {
        return $query->where('user_login', $userLogin);
    }

    /**
     * Scope a query to filter by action type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to filter by date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, string $startDate, ?string $endDate = null)
    {
        $query->where('created_at', '>=', $startDate);

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Get audit logs for a specific auditable model instance.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function forAuditable(Model $model)
    {
        return static::where('auditable_type', get_class($model))
            ->where('auditable_id', $model->getKey());
    }
}
