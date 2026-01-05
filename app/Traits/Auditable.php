<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Auditable Trait
 *
 * Provides automatic audit logging for Eloquent models.
 * When used in a model, it will automatically record all creates, updates, and deletes
 * to the audit_logs table.
 *
 * Usage:
 * ```php
 * class Matter extends Model
 * {
 *     use Auditable;
 * }
 * ```
 *
 * Customization:
 * - Override $auditExclude to exclude specific fields from audit logging
 * - Override $auditInclude to only audit specific fields (takes precedence over exclude)
 * - Override shouldAudit() method to conditionally enable/disable auditing
 */
trait Auditable
{
    /**
     * Flag to temporarily disable auditing for this model instance.
     */
    protected bool $auditingDisabled = false;

    /**
     * Boot the auditable trait.
     * Registers model event listeners for created, updated, and deleted events.
     */
    public static function bootAuditable(): void
    {
        // Log when a model is created
        static::created(function ($model) {
            $model->logAudit('created', [], $model->getAuditableAttributes());
        });

        // Log when a model is updated
        static::updated(function ($model) {
            $oldValues = $model->getAuditableOriginal();
            $newValues = $model->getAuditableAttributes();

            // Only log if there are actual changes
            if ($oldValues !== $newValues) {
                $model->logAudit('updated', $oldValues, $newValues);
            }
        });

        // Log when a model is deleted
        static::deleted(function ($model) {
            $model->logAudit('deleted', $model->getAuditableOriginal(), []);
        });
    }

    /**
     * Initialize the auditable trait for an instance.
     */
    public function initializeAuditable(): void
    {
        // Ensure we track original values for comparison
        $this->addObservableEvents(['audited']);
    }

    /**
     * Get the audit logs for this model instance.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * Log an audit entry for this model.
     *
     * @param  string  $action  The action type (created, updated, deleted)
     * @param  array  $oldValues  The old attribute values
     * @param  array  $newValues  The new attribute values
     */
    protected function logAudit(string $action, array $oldValues, array $newValues): void
    {
        // Check if auditing should be performed
        if (! $this->shouldAudit()) {
            return;
        }

        // Get the current user information
        $user = Auth::user();
        $userLogin = $user?->login;
        $userName = $user ? ($user->name ?? $user->login) : null;

        // Create the audit log entry
        AuditLog::create([
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'action' => $action,
            'user_login' => $userLogin,
            'user_name' => $userName,
            'old_values' => ! empty($oldValues) ? $oldValues : null,
            'new_values' => ! empty($newValues) ? $newValues : null,
            'ip_address' => Request::ip(),
            'user_agent' => substr(Request::userAgent() ?? '', 0, 500),
            'url' => substr(Request::fullUrl() ?? '', 0, 500),
            'created_at' => now(),
        ]);
    }

    /**
     * Determine if the model should be audited.
     * Override this method in your model to conditionally enable/disable auditing.
     */
    protected function shouldAudit(): bool
    {
        // Don't audit if explicitly disabled
        if ($this->auditingDisabled) {
            return false;
        }

        return true;
    }

    /**
     * Get the attributes that should be audited.
     * Applies include/exclude filters.
     */
    protected function getAuditableAttributes(): array
    {
        $attributes = $this->getAttributes();

        return $this->filterAuditableAttributes($attributes);
    }

    /**
     * Get the original attributes that should be audited.
     * Applies include/exclude filters.
     */
    protected function getAuditableOriginal(): array
    {
        $original = $this->getOriginal();

        return $this->filterAuditableAttributes($original);
    }

    /**
     * Filter attributes based on include/exclude lists.
     */
    protected function filterAuditableAttributes(array $attributes): array
    {
        // Always exclude these system fields
        $systemExcludes = ['password', 'remember_token'];

        // Get model-specific excludes
        $excludes = array_merge($systemExcludes, $this->getAuditExclude());

        // If include list is defined, only include those fields
        $includes = $this->getAuditInclude();
        if (! empty($includes)) {
            $attributes = array_intersect_key($attributes, array_flip($includes));
        }

        // Apply excludes
        $attributes = array_diff_key($attributes, array_flip($excludes));

        // Convert any objects to strings for proper JSON storage
        foreach ($attributes as $key => $value) {
            if (is_object($value)) {
                $attributes[$key] = (string) $value;
            }
        }

        return $attributes;
    }

    /**
     * Get the list of attributes to exclude from auditing.
     * Override this property in your model to customize.
     */
    protected function getAuditExclude(): array
    {
        return property_exists($this, 'auditExclude') ? $this->auditExclude : [];
    }

    /**
     * Get the list of attributes to include in auditing.
     * If defined, only these attributes will be audited.
     * Override this property in your model to customize.
     */
    protected function getAuditInclude(): array
    {
        return property_exists($this, 'auditInclude') ? $this->auditInclude : [];
    }

    /**
     * Get the latest audit log entry for this model.
     */
    public function getLatestAuditLog(): ?AuditLog
    {
        return $this->auditLogs()->latest('id')->first();
    }

    /**
     * Get the audit history for this model, ordered by most recent first.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAuditHistory(?int $limit = null)
    {
        $query = $this->auditLogs()->orderByDesc('created_at');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Temporarily disable auditing for this model instance.
     *
     * @return $this
     */
    public function withoutAuditing(): static
    {
        $this->auditingDisabled = true;

        return $this;
    }

    /**
     * Re-enable auditing for this model instance.
     *
     * @return $this
     */
    public function withAuditing(): static
    {
        $this->auditingDisabled = false;

        return $this;
    }
}
