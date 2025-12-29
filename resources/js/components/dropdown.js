/**
 * Dropdown Component
 *
 * A flexible dropdown menu with keyboard navigation.
 * Replaces Bootstrap dropdowns with Alpine.js solution.
 *
 * Usage:
 *   <div x-data="dropdown">
 *     <button @click="toggle" class="btn">Options</button>
 *     <ul x-show="open" class="dropdown-content menu">
 *       <li><a @click="select('option1')">Option 1</a></li>
 *     </ul>
 *   </div>
 */

export default function dropdown() {
  return {
    open: false,
    highlightedIndex: -1,
    items: [],
    selectedValue: null,
    searchQuery: '',
    searchTimeout: null,

    /**
     * Initialize
     */
    init() {
      // Store bound handlers for cleanup
      this._handleClick = (e) => {
        if (this.open && !this.$el.contains(e.target)) {
          this.close();
        }
      };

      this._handleKeydown = (e) => {
        if (e.key === 'Escape' && this.open) {
          this.close();
        }
      };

      this._handleDropdownOpened = (e) => {
        if (e.detail.id !== this.$el.id && this.open) {
          this.close();
        }
      };

      // Close on click outside
      document.addEventListener('click', this._handleClick);

      // Close on escape
      document.addEventListener('keydown', this._handleKeydown);

      // Close when another dropdown opens
      window.addEventListener('dropdown-opened', this._handleDropdownOpened);
    },

    /**
     * Cleanup event listeners when component is destroyed
     */
    destroy() {
      document.removeEventListener('click', this._handleClick);
      document.removeEventListener('keydown', this._handleKeydown);
      window.removeEventListener('dropdown-opened', this._handleDropdownOpened);
      clearTimeout(this.searchTimeout);
    },

    /**
     * Toggle dropdown
     */
    toggle() {
      if (this.open) {
        this.close();
      } else {
        this.openDropdown();
      }
    },

    /**
     * Open dropdown
     */
    openDropdown() {
      this.open = true;
      this.highlightedIndex = -1;
      this.searchQuery = '';

      // Notify other dropdowns
      window.dispatchEvent(new CustomEvent('dropdown-opened', {
        detail: { id: this.$el.id }
      }));

      // Focus first item
      this.$nextTick(() => {
        const content = this.$el.querySelector('.dropdown-content, .menu');
        if (content) {
          this.items = Array.from(content.querySelectorAll('li > a, li > button, [role="menuitem"]'));
        }
      });
    },

    /**
     * Close dropdown
     */
    close() {
      this.open = false;
      this.highlightedIndex = -1;
      this.searchQuery = '';
    },

    /**
     * Select an item
     * @param {*} value
     * @param {Event} e
     */
    select(value, e) {
      this.selectedValue = value;

      // Dispatch selection event
      this.$dispatch('dropdown-select', { value });

      // Close unless prevented
      if (!e?.target?.dataset?.keepOpen) {
        this.close();
      }
    },

    /**
     * Handle keyboard navigation
     * @param {KeyboardEvent} e
     */
    handleKeydown(e) {
      if (!this.open) {
        // Open on arrow down or enter
        if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          this.openDropdown();
        }
        return;
      }

      switch (e.key) {
        case 'ArrowDown':
          e.preventDefault();
          this.highlightNext();
          break;

        case 'ArrowUp':
          e.preventDefault();
          this.highlightPrevious();
          break;

        case 'Enter':
        case ' ':
          e.preventDefault();
          if (this.highlightedIndex >= 0 && this.items[this.highlightedIndex]) {
            this.items[this.highlightedIndex].click();
          }
          break;

        case 'Home':
          e.preventDefault();
          this.highlightedIndex = 0;
          this.scrollToHighlighted();
          break;

        case 'End':
          e.preventDefault();
          this.highlightedIndex = this.items.length - 1;
          this.scrollToHighlighted();
          break;

        case 'Tab':
          this.close();
          break;

        default:
          // Type-ahead search
          if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
            this.handleTypeAhead(e.key);
          }
          break;
      }
    },

    /**
     * Highlight next item
     */
    highlightNext() {
      if (this.highlightedIndex < this.items.length - 1) {
        this.highlightedIndex++;
      } else {
        this.highlightedIndex = 0;
      }
      this.scrollToHighlighted();
    },

    /**
     * Highlight previous item
     */
    highlightPrevious() {
      if (this.highlightedIndex > 0) {
        this.highlightedIndex--;
      } else {
        this.highlightedIndex = this.items.length - 1;
      }
      this.scrollToHighlighted();
    },

    /**
     * Scroll to keep highlighted item visible
     */
    scrollToHighlighted() {
      const item = this.items[this.highlightedIndex];
      if (item) {
        item.scrollIntoView({ block: 'nearest' });
      }
    },

    /**
     * Handle type-ahead search
     * @param {string} char
     */
    handleTypeAhead(char) {
      // Clear search after 500ms of inactivity
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.searchQuery = '';
      }, 500);

      this.searchQuery += char.toLowerCase();

      // Find matching item
      const startIndex = this.highlightedIndex + 1;
      let found = false;

      // Search from current position
      for (let i = startIndex; i < this.items.length; i++) {
        if (this.matchesSearch(this.items[i])) {
          this.highlightedIndex = i;
          found = true;
          break;
        }
      }

      // Wrap around if not found
      if (!found) {
        for (let i = 0; i < startIndex; i++) {
          if (this.matchesSearch(this.items[i])) {
            this.highlightedIndex = i;
            break;
          }
        }
      }

      this.scrollToHighlighted();
    },

    /**
     * Check if item matches search query
     * @param {HTMLElement} item
     * @returns {boolean}
     */
    matchesSearch(item) {
      const text = item.textContent?.toLowerCase() || '';
      return text.startsWith(this.searchQuery);
    },

    /**
     * Check if item is highlighted
     * @param {number} index
     * @returns {boolean}
     */
    isHighlighted(index) {
      return this.highlightedIndex === index;
    }
  };
}

/**
 * Select dropdown (replacement for native select)
 */
export function selectDropdown(config = {}) {
  return {
    ...dropdown(),

    value: config.value || null,
    options: config.options || [],
    placeholder: config.placeholder || 'Select...',
    searchable: config.searchable || false,
    multiple: config.multiple || false,
    selectedValues: config.multiple ? (config.value || []) : [],
    filterQuery: '',

    /**
     * Get display text for current selection
     */
    getDisplayText() {
      if (this.multiple) {
        if (this.selectedValues.length === 0) return this.placeholder;
        if (this.selectedValues.length === 1) {
          const option = this.options.find(o => o.value === this.selectedValues[0]);
          return option?.label || this.placeholder;
        }
        return `${this.selectedValues.length} selected`;
      }

      if (!this.value) return this.placeholder;
      const option = this.options.find(o => o.value === this.value);
      return option?.label || this.placeholder;
    },

    /**
     * Select an option
     * @param {*} optionValue
     */
    selectOption(optionValue) {
      if (this.multiple) {
        const index = this.selectedValues.indexOf(optionValue);
        if (index >= 0) {
          this.selectedValues.splice(index, 1);
        } else {
          this.selectedValues.push(optionValue);
        }
        this.$dispatch('change', { value: this.selectedValues });
      } else {
        this.value = optionValue;
        this.$dispatch('change', { value: this.value });
        this.close();
      }
    },

    /**
     * Check if option is selected
     * @param {*} optionValue
     */
    isSelected(optionValue) {
      if (this.multiple) {
        return this.selectedValues.includes(optionValue);
      }
      return this.value === optionValue;
    },

    /**
     * Clear selection
     */
    clear() {
      if (this.multiple) {
        this.selectedValues = [];
        this.$dispatch('change', { value: [] });
      } else {
        this.value = null;
        this.$dispatch('change', { value: null });
      }
    }
  };
}
