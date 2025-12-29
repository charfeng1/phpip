/**
 * Autocomplete Component
 *
 * A powerful autocomplete/combobox with server-side search.
 * Replaces the custom autocomplete implementation in main.js.
 *
 * Usage:
 *   <div x-data="autocomplete({
 *     url: '/api/actors/search',
 *     minLength: 2,
 *     valueField: 'id',
 *     labelField: 'name',
 *     target: 'actor_id'
 *   })">
 *     <input x-model="query" @input.debounce.300ms="search" @keydown="handleKeydown">
 *     <ul x-show="open">
 *       <template x-for="item in suggestions">
 *         <li @click="select(item)" x-text="item.name"></li>
 *       </template>
 *     </ul>
 *     <input type="hidden" :name="target" :value="selectedValue">
 *   </div>
 */

export default function autocomplete(config = {}) {
  return {
    // Configuration
    url: config.url || '',
    minLength: config.minLength ?? 1,
    valueField: config.valueField || 'id',
    labelField: config.labelField || 'name',
    target: config.target || null,
    params: config.params || {},
    debounceMs: config.debounceMs || 300,
    maxResults: config.maxResults || 10,
    allowCreate: config.allowCreate || false,
    createLabel: config.createLabel || 'Create "{query}"',

    // State
    query: config.initialQuery || '',
    suggestions: [],
    open: false,
    loading: false,
    selectedValue: config.initialValue || null,
    selectedItem: null,
    highlightedIndex: -1,
    abortController: null,

    /**
     * Initialize
     */
    init() {
      // If we have an initial value, try to load the display text
      if (this.selectedValue && config.initialLabel) {
        this.query = config.initialLabel;
      }

      // Store bound handler for cleanup
      this._clickOutsideHandler = (e) => {
        if (this.open && !this.$el.contains(e.target)) {
          this.close();
        }
      };

      // Close on click outside
      document.addEventListener('click', this._clickOutsideHandler);
    },

    /**
     * Cleanup event listeners
     */
    destroy() {
      document.removeEventListener('click', this._clickOutsideHandler);
    },

    /**
     * Search for suggestions
     */
    async search() {
      // Cancel previous request
      if (this.abortController) {
        this.abortController.abort();
      }

      // Check minimum length
      if (this.query.length < this.minLength) {
        this.suggestions = [];
        this.close();
        return;
      }

      // Clear selection when query changes
      if (this.selectedItem && this.query !== this.getLabel(this.selectedItem)) {
        this.selectedValue = null;
        this.selectedItem = null;
        this.updateTarget(null);
      }

      this.loading = true;
      this.abortController = new AbortController();

      try {
        const params = new URLSearchParams({
          q: this.query,
          limit: this.maxResults,
          ...this.params
        });

        const response = await fetch(`${this.url}?${params}`, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          signal: this.abortController.signal
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        // Handle different response formats
        this.suggestions = Array.isArray(data) ? data : (data.data || data.results || []);

        // Add create option if enabled
        if (this.allowCreate && this.query && !this.suggestions.some(
          item => this.getLabel(item).toLowerCase() === this.query.toLowerCase()
        )) {
          this.suggestions.push({
            [this.valueField]: '__create__',
            [this.labelField]: this.createLabel.replace('{query}', this.query),
            __isCreate: true,
            __createValue: this.query
          });
        }

        this.open = this.suggestions.length > 0;
        this.highlightedIndex = -1;

      } catch (error) {
        if (error.name !== 'AbortError') {
          console.error('Autocomplete search error:', error);
          this.suggestions = [];
        }
      } finally {
        this.loading = false;
      }
    },

    /**
     * Select a suggestion
     * @param {Object} item
     */
    select(item) {
      if (item.__isCreate) {
        // Handle create new item
        this.$dispatch('autocomplete-create', {
          value: item.__createValue,
          target: this.target
        });
        this.query = item.__createValue;
        this.selectedValue = null;
        this.selectedItem = null;
      } else {
        this.selectedItem = item;
        this.selectedValue = this.getValue(item);
        this.query = this.getLabel(item);
        this.updateTarget(this.selectedValue);

        // Dispatch selection event
        this.$dispatch('autocomplete-select', {
          item,
          value: this.selectedValue,
          target: this.target
        });
      }

      this.close();
    },

    /**
     * Clear selection
     */
    clear() {
      this.query = '';
      this.selectedValue = null;
      this.selectedItem = null;
      this.suggestions = [];
      this.updateTarget(null);

      this.$dispatch('autocomplete-clear', { target: this.target });
    },

    /**
     * Close the suggestions dropdown
     */
    close() {
      this.open = false;
      this.highlightedIndex = -1;
    },

    /**
     * Handle keyboard navigation
     * @param {KeyboardEvent} e
     */
    handleKeydown(e) {
      switch (e.key) {
        case 'ArrowDown':
          e.preventDefault();
          if (!this.open && this.suggestions.length > 0) {
            this.open = true;
          } else {
            this.highlightNext();
          }
          break;

        case 'ArrowUp':
          e.preventDefault();
          this.highlightPrevious();
          break;

        case 'Enter':
          e.preventDefault();
          if (this.open && this.highlightedIndex >= 0) {
            this.select(this.suggestions[this.highlightedIndex]);
          }
          break;

        case 'Escape':
          e.preventDefault();
          if (this.open) {
            this.close();
          } else if (this.selectedItem) {
            // Reset to selected value
            this.query = this.getLabel(this.selectedItem);
          }
          break;

        case 'Tab':
          if (this.open && this.highlightedIndex >= 0) {
            this.select(this.suggestions[this.highlightedIndex]);
          }
          this.close();
          break;
      }
    },

    /**
     * Highlight next suggestion
     */
    highlightNext() {
      if (this.highlightedIndex < this.suggestions.length - 1) {
        this.highlightedIndex++;
      } else {
        this.highlightedIndex = 0;
      }
      this.scrollToHighlighted();
    },

    /**
     * Highlight previous suggestion
     */
    highlightPrevious() {
      if (this.highlightedIndex > 0) {
        this.highlightedIndex--;
      } else {
        this.highlightedIndex = this.suggestions.length - 1;
      }
      this.scrollToHighlighted();
    },

    /**
     * Scroll to keep highlighted item visible
     */
    scrollToHighlighted() {
      this.$nextTick(() => {
        const list = this.$el.querySelector('ul, .dropdown-content');
        const item = list?.children[this.highlightedIndex];
        if (item) {
          item.scrollIntoView({ block: 'nearest' });
        }
      });
    },

    /**
     * Get value from item
     * @param {Object} item
     */
    getValue(item) {
      return item[this.valueField];
    },

    /**
     * Get label from item
     * @param {Object} item
     */
    getLabel(item) {
      return item[this.labelField] || '';
    },

    /**
     * Update the hidden target input
     * @param {*} value
     */
    updateTarget(value) {
      if (this.target) {
        const targetInput = document.querySelector(`[name="${this.target}"], #${this.target}`);
        if (targetInput) {
          targetInput.value = value || '';

          // Trigger change event
          targetInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    },

    /**
     * Check if suggestion is highlighted
     * @param {number} index
     */
    isHighlighted(index) {
      return this.highlightedIndex === index;
    },

    /**
     * Handle focus
     */
    handleFocus() {
      if (this.query.length >= this.minLength && this.suggestions.length > 0) {
        this.open = true;
      }
    },

    /**
     * Handle blur with delay to allow click selection
     */
    handleBlur() {
      setTimeout(() => {
        if (!this.$el.contains(document.activeElement)) {
          this.close();
        }
      }, 200);
    }
  };
}

/**
 * Combobox variant - combines select with search
 */
export function combobox(config = {}) {
  const base = autocomplete(config);

  return {
    ...base,

    options: config.options || [],
    filteredOptions: [],

    init() {
      // Call parent init for common setup (click-outside listener, etc.)
      if (base.init) {
        base.init.call(this);
      }

      this.filteredOptions = this.options;
    },

    /**
     * Filter local options instead of server search
     */
    search() {
      const query = this.query.toLowerCase();

      if (!query) {
        this.filteredOptions = this.options;
      } else {
        this.filteredOptions = this.options.filter(option => {
          const label = this.getLabel(option).toLowerCase();
          return label.includes(query);
        });
      }

      this.suggestions = this.filteredOptions;
      this.open = true;
      this.highlightedIndex = -1;
    },

    /**
     * Toggle dropdown
     */
    toggle() {
      if (this.open) {
        this.close();
      } else {
        this.suggestions = this.options;
        this.open = true;
      }
    }
  };
}

/**
 * Multi-select autocomplete variant
 */
export function multiAutocomplete(config = {}) {
  const base = autocomplete(config);

  return {
    ...base,

    selectedItems: config.initialItems || [],

    /**
     * Initialize - call base init for click-outside handler
     */
    init() {
      if (base.init) {
        base.init.call(this);
      }
    },

    /**
     * Select adds to list instead of replacing
     */
    select(item) {
      if (item.__isCreate) {
        this.$dispatch('autocomplete-create', {
          value: item.__createValue,
          target: this.target
        });
      } else {
        // Check if already selected
        const exists = this.selectedItems.some(
          selected => this.getValue(selected) === this.getValue(item)
        );

        if (!exists) {
          this.selectedItems.push(item);
          this.updateTargets();

          this.$dispatch('autocomplete-select', {
            item,
            items: this.selectedItems,
            target: this.target
          });
        }
      }

      this.query = '';
      this.close();
    },

    /**
     * Remove an item from selection
     */
    removeItem(item) {
      const index = this.selectedItems.findIndex(
        selected => this.getValue(selected) === this.getValue(item)
      );

      if (index >= 0) {
        this.selectedItems.splice(index, 1);
        this.updateTargets();

        this.$dispatch('autocomplete-remove', {
          item,
          items: this.selectedItems,
          target: this.target
        });
      }
    },

    /**
     * Update hidden inputs for form submission
     */
    updateTargets() {
      if (this.target) {
        // Remove existing inputs (scoped to this component)
        this.$el.querySelectorAll(`input[name="${this.target}[]"]`).forEach(el => el.remove());

        // Create new inputs
        this.selectedItems.forEach(item => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = `${this.target}[]`;
          input.value = this.getValue(item);
          this.$el.appendChild(input);
        });
      }
    },

    /**
     * Clear all selections
     */
    clear() {
      this.selectedItems = [];
      this.query = '';
      this.updateTargets();
      this.$dispatch('autocomplete-clear', { target: this.target });
    }
  };
}
