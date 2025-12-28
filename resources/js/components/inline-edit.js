/**
 * Inline Edit Component
 *
 * Click-to-edit functionality for inline field editing.
 * Replaces the content-editable and .noformat handlers in main.js.
 *
 * Usage:
 *   <div x-data="inlineEdit({
 *     resource: '/api/matter/123',
 *     field: 'title',
 *     value: 'Initial Title'
 *   })">
 *     <span x-show="!editing" @click="startEdit" x-text="displayValue"></span>
 *     <input x-show="editing" x-model="currentValue"
 *            @keydown.enter="save" @keydown.escape="cancel" @blur="save">
 *   </div>
 */

export default function inlineEdit(config = {}) {
  return {
    // Configuration
    resource: config.resource || '',
    field: config.field || '',
    method: config.method || 'PUT',
    emptyText: config.emptyText || '—',
    type: config.type || 'text', // text, textarea, select, date

    // State
    originalValue: config.value ?? '',
    currentValue: config.value ?? '',
    editing: false,
    saving: false,
    error: null,

    /**
     * Get display value
     */
    get displayValue() {
      return this.currentValue || this.emptyText;
    },

    /**
     * Start editing
     */
    startEdit() {
      this.editing = true;
      this.error = null;

      // Focus the input after render
      this.$nextTick(() => {
        const input = this.$el.querySelector('input, textarea, select');
        if (input) {
          input.focus();
          if (input.type === 'text' || input.tagName === 'TEXTAREA') {
            input.select();
          }
        }
      });
    },

    /**
     * Cancel editing
     */
    cancel() {
      this.currentValue = this.originalValue;
      this.editing = false;
      this.error = null;
    },

    /**
     * Save changes
     */
    async save() {
      // No change, just cancel
      if (this.currentValue === this.originalValue) {
        this.editing = false;
        return;
      }

      this.saving = true;
      this.error = null;

      try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const response = await fetch(this.resource, {
          method: this.method,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
          },
          body: JSON.stringify({
            [this.field]: this.currentValue
          })
        });

        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.message || data.errors?.[this.field]?.[0] || 'Save failed');
        }

        // Update original value on success
        this.originalValue = this.currentValue;
        this.editing = false;

        // Dispatch success event
        this.$dispatch('inline-edit-saved', {
          field: this.field,
          value: this.currentValue,
          data
        });

        // Show success indicator briefly
        this.showSuccessIndicator();

      } catch (err) {
        console.error('Inline edit error:', err);
        this.error = err.message;

        // Revert on error
        this.currentValue = this.originalValue;

        // Dispatch error event
        this.$dispatch('inline-edit-error', {
          field: this.field,
          error: err.message
        });
      } finally {
        this.saving = false;
      }
    },

    /**
     * Show brief success indicator
     */
    showSuccessIndicator() {
      const el = this.$el;
      el.classList.add('inline-edit-success');
      setTimeout(() => {
        el.classList.remove('inline-edit-success');
      }, 1000);
    },

    /**
     * Handle keydown events
     * @param {KeyboardEvent} e
     */
    handleKeydown(e) {
      if (e.key === 'Enter' && this.type !== 'textarea') {
        e.preventDefault();
        this.save();
      } else if (e.key === 'Escape') {
        e.preventDefault();
        this.cancel();
      }
    },

    /**
     * Handle blur - save unless clicking within component
     * @param {FocusEvent} e
     */
    handleBlur(e) {
      // Delay to check if focus moved within component
      setTimeout(() => {
        if (!this.$el.contains(document.activeElement)) {
          this.save();
        }
      }, 100);
    }
  };
}

/**
 * Content-editable variant for rich text
 */
export function contentEditable(config = {}) {
  return {
    ...inlineEdit(config),

    /**
     * Start editing - enable contenteditable
     */
    startEdit() {
      const el = this.$refs.content;
      if (el) {
        el.contentEditable = 'true';
        el.focus();

        // Select all text
        const range = document.createRange();
        range.selectNodeContents(el);
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
      }
      this.editing = true;
      this.error = null;
    },

    /**
     * Cancel - disable contenteditable and restore
     */
    cancel() {
      const el = this.$refs.content;
      if (el) {
        el.contentEditable = 'false';
        el.textContent = this.originalValue;
      }
      this.editing = false;
      this.error = null;
    },

    /**
     * Save - get content from element
     */
    async save() {
      const el = this.$refs.content;
      if (el) {
        this.currentValue = el.textContent.trim();
        el.contentEditable = 'false';
      }

      // Call parent save
      await inlineEdit(config).save.call(this);
    }
  };
}

/**
 * Inline select variant
 */
export function inlineSelect(config = {}) {
  return {
    ...inlineEdit({ ...config, type: 'select' }),

    options: config.options || [],

    /**
     * Get display text for current value
     */
    get displayValue() {
      const option = this.options.find(o => o.value == this.currentValue);
      return option?.label || this.currentValue || this.emptyText;
    },

    /**
     * Handle select change
     * @param {Event} e
     */
    handleChange(e) {
      this.currentValue = e.target.value;
      this.save();
    }
  };
}

/**
 * Inline date picker variant
 */
export function inlineDate(config = {}) {
  return {
    ...inlineEdit({ ...config, type: 'date' }),

    format: config.format || 'Y-m-d',

    /**
     * Format date for display
     */
    get displayValue() {
      if (!this.currentValue) return this.emptyText;

      try {
        const date = new Date(this.currentValue);
        return date.toLocaleDateString();
      } catch {
        return this.currentValue || this.emptyText;
      }
    }
  };
}

/**
 * Batch inline edit - update multiple fields at once
 */
export function batchInlineEdit(config = {}) {
  return {
    resource: config.resource || '',
    fields: config.fields || {},
    editing: false,
    saving: false,
    errors: {},

    /**
     * Start editing all fields
     */
    startEdit() {
      this.editing = true;
      this.errors = {};
    },

    /**
     * Cancel editing
     */
    cancel() {
      // Reset all fields to original values
      for (const [field, value] of Object.entries(config.fields)) {
        this.fields[field] = value;
      }
      this.editing = false;
      this.errors = {};
    },

    /**
     * Save all fields
     */
    async save() {
      this.saving = true;
      this.errors = {};

      try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const response = await fetch(this.resource, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
          },
          body: JSON.stringify(this.fields)
        });

        const data = await response.json();

        if (!response.ok) {
          if (response.status === 422 && data.errors) {
            this.errors = data.errors;
            return;
          }
          throw new Error(data.message || 'Save failed');
        }

        this.editing = false;
        this.$dispatch('batch-edit-saved', { data });

      } catch (err) {
        console.error('Batch edit error:', err);
        this.$dispatch('batch-edit-error', { error: err.message });
      } finally {
        this.saving = false;
      }
    }
  };
}
