/**
 * Keyboard Shortcuts Component
 *
 * Global keyboard shortcut handler with help dialog.
 * Provides Linear/Notion-style keyboard navigation.
 *
 * Usage:
 *   <div x-data="keyboardShortcuts" @keydown.window="handleKeydown">
 *     <!-- Help modal -->
 *     <div x-show="showHelp" class="modal modal-open">...</div>
 *   </div>
 *
 * Register shortcuts:
 *   shortcuts.register('ctrl+n', () => createMatter(), 'Create new matter');
 */

export default function keyboardShortcuts() {
  return {
    showHelp: false,
    shortcuts: new Map(),
    sequenceBuffer: '',
    sequenceTimeout: null,
    lastKeyTime: 0,

    /**
     * Initialize default shortcuts
     */
    init() {
      // Register default phpIP shortcuts
      this.registerDefaults();

      // Global keydown listener is attached via @keydown.window
    },

    /**
     * Register default phpIP shortcuts
     */
    registerDefaults() {
      // Help
      this.register('?', () => this.toggleHelp(), 'Show keyboard shortcuts', 'General');

      // Navigation
      this.register('g m', () => this.navigate('/matter'), 'Go to matters', 'Navigation');
      this.register('g t', () => this.navigate('/task'), 'Go to tasks', 'Navigation');
      this.register('g r', () => this.navigate('/renewal'), 'Go to renewals', 'Navigation');
      this.register('g a', () => this.navigate('/actor'), 'Go to actors', 'Navigation');
      this.register('g h', () => this.navigate('/'), 'Go to home', 'Navigation');

      // Actions
      this.register('ctrl+n', () => this.dispatch('create-matter'), 'Create new matter', 'Actions');
      this.register('ctrl+f', () => this.focusSearch(), 'Focus search', 'Actions');
      this.register('ctrl+s', () => this.saveForm(), 'Save current form', 'Actions');

      // UI
      this.register('escape', () => this.closeAll(), 'Close modal/menu', 'General');
      this.register('ctrl+k', () => this.dispatch('open-command-palette'), 'Command palette', 'General');
    },

    /**
     * Register a keyboard shortcut
     * @param {string} combo - Key combination (e.g., 'ctrl+n', 'g m', '?')
     * @param {Function} action - Action to execute
     * @param {string} description - Human-readable description
     * @param {string} category - Category for grouping in help
     */
    register(combo, action, description = '', category = 'General') {
      const normalizedCombo = this.normalizeCombo(combo);
      this.shortcuts.set(normalizedCombo, {
        combo: normalizedCombo,
        displayCombo: combo,
        action,
        description,
        category
      });
    },

    /**
     * Unregister a shortcut
     * @param {string} combo
     */
    unregister(combo) {
      const normalizedCombo = this.normalizeCombo(combo);
      this.shortcuts.delete(normalizedCombo);
    },

    /**
     * Normalize key combination for consistent matching
     * @param {string} combo
     * @returns {string}
     */
    normalizeCombo(combo) {
      return combo
        .toLowerCase()
        .replace(/cmd\+/g, 'meta+')
        .replace(/command\+/g, 'meta+')
        .replace(/\s+/g, ' ')
        .trim();
    },

    /**
     * Handle keydown events
     * @param {KeyboardEvent} e
     */
    handleKeydown(e) {
      // Don't trigger in editable elements (unless it's Escape)
      if (this.isEditableElement(e.target) && e.key !== 'Escape') {
        return;
      }

      // Build the key combo string
      const combo = this.buildCombo(e);

      // Check for sequence shortcuts (e.g., 'g m')
      const now = Date.now();
      if (now - this.lastKeyTime < 1000 && this.sequenceBuffer) {
        const sequenceCombo = `${this.sequenceBuffer} ${combo}`;
        if (this.tryExecute(sequenceCombo, e)) {
          this.clearSequence();
          return;
        }
      }

      // Check for single key shortcut
      if (this.tryExecute(combo, e)) {
        this.clearSequence();
        return;
      }

      // Start or continue sequence
      if (this.isSequenceStarter(combo)) {
        this.sequenceBuffer = combo;
        this.lastKeyTime = now;

        // Clear sequence after timeout
        clearTimeout(this.sequenceTimeout);
        this.sequenceTimeout = setTimeout(() => {
          this.clearSequence();
        }, 1000);
      }
    },

    /**
     * Build combo string from keyboard event
     * @param {KeyboardEvent} e
     * @returns {string}
     */
    buildCombo(e) {
      const parts = [];

      if (e.ctrlKey) parts.push('ctrl');
      if (e.altKey) parts.push('alt');
      if (e.shiftKey) parts.push('shift');
      if (e.metaKey) parts.push('meta');

      // Get the key, handling special cases
      let key = e.key.toLowerCase();

      // Normalize special keys
      if (key === ' ') key = 'space';
      if (key === 'arrowup') key = 'up';
      if (key === 'arrowdown') key = 'down';
      if (key === 'arrowleft') key = 'left';
      if (key === 'arrowright') key = 'right';

      // Don't add modifier keys as the key itself
      if (!['control', 'alt', 'shift', 'meta'].includes(key)) {
        parts.push(key);
      }

      return parts.join('+');
    },

    /**
     * Try to execute a shortcut
     * @param {string} combo
     * @param {KeyboardEvent} e
     * @returns {boolean} - Whether a shortcut was executed
     */
    tryExecute(combo, e) {
      const shortcut = this.shortcuts.get(combo);

      if (shortcut) {
        e.preventDefault();
        e.stopPropagation();

        try {
          shortcut.action();
        } catch (error) {
          console.error(`Shortcut error [${combo}]:`, error);
        }

        return true;
      }

      return false;
    },

    /**
     * Check if a key could start a sequence
     * @param {string} combo
     * @returns {boolean}
     */
    isSequenceStarter(combo) {
      // Check if any registered shortcut starts with this combo
      for (const [key] of this.shortcuts) {
        if (key.startsWith(combo + ' ')) {
          return true;
        }
      }
      return false;
    },

    /**
     * Clear the sequence buffer
     */
    clearSequence() {
      this.sequenceBuffer = '';
      clearTimeout(this.sequenceTimeout);
    },

    /**
     * Check if element is editable
     * @param {HTMLElement} element
     * @returns {boolean}
     */
    isEditableElement(element) {
      const tagName = element.tagName.toLowerCase();
      const isInput = tagName === 'input' || tagName === 'textarea' || tagName === 'select';
      const isContentEditable = element.isContentEditable;

      return isInput || isContentEditable;
    },

    /**
     * Toggle help dialog
     */
    toggleHelp() {
      this.showHelp = !this.showHelp;
    },

    /**
     * Navigate to a URL
     * @param {string} url
     */
    navigate(url) {
      window.location.href = url;
    },

    /**
     * Dispatch a custom event
     * @param {string} eventName
     * @param {*} detail
     */
    dispatch(eventName, detail = {}) {
      window.dispatchEvent(new CustomEvent(eventName, { detail }));
    },

    /**
     * Focus the search input
     */
    focusSearch() {
      const search = document.querySelector('#search, [data-search], input[type="search"]');
      if (search) {
        search.focus();
        search.select();
      }
    },

    /**
     * Save the current form
     */
    saveForm() {
      const form = document.querySelector('form:not([data-no-save])');
      if (form) {
        form.requestSubmit();
      }
    },

    /**
     * Close all open modals/menus
     */
    closeAll() {
      // Close Alpine modals
      this.dispatch('close-all');

      // Close Bootstrap modals (during transition period)
      const bootstrapModals = document.querySelectorAll('.modal.show');
      bootstrapModals.forEach(modal => {
        const bsModal = window.bootstrap?.Modal?.getInstance(modal);
        if (bsModal) bsModal.hide();
      });

      // Close any open dropdowns
      document.querySelectorAll('.dropdown-content.show, .dropdown-menu.show')
        .forEach(dropdown => dropdown.classList.remove('show'));

      // Close help if open
      this.showHelp = false;
    },

    /**
     * Get shortcuts grouped by category
     * @returns {Object}
     */
    getGroupedShortcuts() {
      const groups = {};

      for (const [, shortcut] of this.shortcuts) {
        const category = shortcut.category || 'General';
        if (!groups[category]) {
          groups[category] = [];
        }
        groups[category].push(shortcut);
      }

      return groups;
    },

    /**
     * Format shortcut for display
     * @param {string} combo
     * @returns {string}
     */
    formatShortcut(combo) {
      const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;

      return combo
        .split(' ')
        .map(part => {
          return part
            .split('+')
            .map(key => {
              // Format each key
              const formatted = key
                .replace(/^meta$/i, isMac ? '\u2318' : 'Ctrl')
                .replace(/^ctrl$/i, isMac ? '\u2303' : 'Ctrl')
                .replace(/^alt$/i, isMac ? '\u2325' : 'Alt')
                .replace(/^shift$/i, isMac ? '\u21E7' : 'Shift')
                .replace(/^escape$/i, 'Esc')
                .replace(/^enter$/i, '\u21B5')
                .replace(/^space$/i, 'Space')
                .replace(/^up$/i, '\u2191')
                .replace(/^down$/i, '\u2193')
                .replace(/^left$/i, '\u2190')
                .replace(/^right$/i, '\u2192');

              // Capitalize single letters
              if (formatted.length === 1) {
                return formatted.toUpperCase();
              }

              return formatted;
            })
            .join(isMac ? '' : '+');
        })
        .join(' then ');
    }
  };
}

/**
 * Create a shortcut registration helper for page-specific shortcuts
 * @param {Object} shortcutsComponent - The keyboard shortcuts Alpine component
 * @returns {Function} - Registration function
 */
export function createShortcutRegistrar(shortcutsComponent) {
  return (combo, action, description, category) => {
    shortcutsComponent.register(combo, action, description, category);
  };
}
