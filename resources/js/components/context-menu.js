/**
 * Context Menu Component
 *
 * A Linear/Notion-style right-click context menu with keyboard shortcut hints.
 * Provides position-aware rendering, keyboard navigation, and nested menus.
 *
 * Usage:
 *   <div x-data="contextMenu">
 *     <template x-teleport="body">
 *       <div x-ref="menu" x-show="open" ... >
 *         <template x-for="item in items">
 *           ...
 *         </template>
 *       </div>
 *     </template>
 *   </div>
 *
 *   // Trigger from any element:
 *   <tr @contextmenu="$dispatch('context-menu', { event: $event, items: [...], target: row })">
 */

export default function contextMenu() {
  return {
    open: false,
    x: 0,
    y: 0,
    items: [],
    target: null,
    highlightedIndex: -1,

    /**
     * Initialize the context menu
     */
    init() {
      // Listen for context-menu events dispatched from elements
      this.$el.addEventListener('context-menu', (e) => {
        this.show(e.detail.event, e.detail.items, e.detail.target);
      });

      // Close on escape or click outside
      document.addEventListener('keydown', (e) => {
        if (this.open) {
          this.handleKeydown(e);
        }
      });

      document.addEventListener('click', (e) => {
        if (this.open && !this.$refs.menu?.contains(e.target)) {
          this.close();
        }
      });

      // Close on scroll
      document.addEventListener('scroll', () => {
        if (this.open) this.close();
      }, true);
    },

    /**
     * Show the context menu
     * @param {MouseEvent} event - The contextmenu event
     * @param {Array} items - Menu items to display
     * @param {*} target - The target element/data for actions
     */
    show(event, items, target = null) {
      event.preventDefault();
      event.stopPropagation();

      this.items = items.filter(item => !item.hidden);
      this.target = target;
      this.highlightedIndex = -1;

      // Calculate position
      const padding = 8;
      let posX = event.clientX;
      let posY = event.clientY;

      // We'll adjust after the menu is rendered
      this.x = posX;
      this.y = posY;
      this.open = true;

      // Adjust position after render to prevent overflow
      this.$nextTick(() => {
        const menu = this.$refs.menu;
        if (!menu) return;

        const rect = menu.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        // Adjust horizontal position
        if (posX + rect.width > viewportWidth - padding) {
          posX = viewportWidth - rect.width - padding;
        }
        if (posX < padding) {
          posX = padding;
        }

        // Adjust vertical position
        if (posY + rect.height > viewportHeight - padding) {
          posY = viewportHeight - rect.height - padding;
        }
        if (posY < padding) {
          posY = padding;
        }

        this.x = posX;
        this.y = posY;

        // Focus the menu for keyboard navigation
        menu.focus();
      });
    },

    /**
     * Close the context menu
     */
    close() {
      this.open = false;
      this.items = [];
      this.target = null;
      this.highlightedIndex = -1;
    },

    /**
     * Execute a menu item action
     * @param {Object} item - The menu item
     */
    execute(item) {
      if (item.disabled) return;

      if (item.action && typeof item.action === 'function') {
        item.action(this.target);
      } else if (item.href) {
        window.location.href = item.href;
      } else if (item.dispatch) {
        this.$dispatch(item.dispatch.event, item.dispatch.detail);
      }

      this.close();
    },

    /**
     * Handle keyboard navigation
     * @param {KeyboardEvent} e
     */
    handleKeydown(e) {
      const actionableItems = this.items.filter(
        item => item.type !== 'divider' && !item.disabled
      );

      switch (e.key) {
        case 'Escape':
          e.preventDefault();
          this.close();
          break;

        case 'ArrowDown':
          e.preventDefault();
          this.highlightNext(actionableItems);
          break;

        case 'ArrowUp':
          e.preventDefault();
          this.highlightPrevious(actionableItems);
          break;

        case 'Enter':
        case ' ':
          e.preventDefault();
          if (this.highlightedIndex >= 0) {
            const item = actionableItems[this.highlightedIndex];
            if (item) this.execute(item);
          }
          break;

        case 'Home':
          e.preventDefault();
          this.highlightedIndex = 0;
          break;

        case 'End':
          e.preventDefault();
          this.highlightedIndex = actionableItems.length - 1;
          break;

        default:
          // Type-ahead search
          if (e.key.length === 1 && !e.ctrlKey && !e.metaKey) {
            const char = e.key.toLowerCase();
            const startIndex = this.highlightedIndex + 1;
            const index = actionableItems.findIndex((item, i) =>
              i >= startIndex && item.label?.toLowerCase().startsWith(char)
            );
            if (index >= 0) {
              this.highlightedIndex = index;
            } else {
              // Wrap around
              const wrapIndex = actionableItems.findIndex(item =>
                item.label?.toLowerCase().startsWith(char)
              );
              if (wrapIndex >= 0) {
                this.highlightedIndex = wrapIndex;
              }
            }
          }
          break;
      }
    },

    /**
     * Highlight next item
     */
    highlightNext(actionableItems) {
      if (this.highlightedIndex < actionableItems.length - 1) {
        this.highlightedIndex++;
      } else {
        this.highlightedIndex = 0;
      }
    },

    /**
     * Highlight previous item
     */
    highlightPrevious(actionableItems) {
      if (this.highlightedIndex > 0) {
        this.highlightedIndex--;
      } else {
        this.highlightedIndex = actionableItems.length - 1;
      }
    },

    /**
     * Check if an item is highlighted
     * @param {Object} item
     * @returns {boolean}
     */
    isHighlighted(item) {
      const actionableItems = this.items.filter(
        i => i.type !== 'divider' && !i.disabled
      );
      return actionableItems[this.highlightedIndex] === item;
    },

    /**
     * Format keyboard shortcut for display
     * @param {string} shortcut - e.g., 'Cmd+D', 'Delete', 'Shift+Enter'
     * @returns {string} - Formatted with symbols
     */
    formatShortcut(shortcut) {
      if (!shortcut) return '';

      const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;

      return shortcut
        .replace(/Cmd\+/gi, isMac ? '\u2318' : 'Ctrl+')
        .replace(/Ctrl\+/gi, isMac ? '\u2303' : 'Ctrl+')
        .replace(/Alt\+/gi, isMac ? '\u2325' : 'Alt+')
        .replace(/Shift\+/gi, isMac ? '\u21E7' : 'Shift+')
        .replace(/Enter/gi, '\u21B5')
        .replace(/Delete/gi, isMac ? '\u232B' : 'Del')
        .replace(/Backspace/gi, isMac ? '\u232B' : 'Bksp')
        .replace(/Escape/gi, 'Esc')
        .replace(/ArrowUp/gi, '\u2191')
        .replace(/ArrowDown/gi, '\u2193')
        .replace(/ArrowLeft/gi, '\u2190')
        .replace(/ArrowRight/gi, '\u2192');
    },

    /**
     * Get the actual index in items array for an actionable item
     */
    getItemIndex(item) {
      return this.items.indexOf(item);
    }
  };
}

/**
 * Helper function to create menu item objects
 */
export function menuItem(label, options = {}) {
  return {
    label,
    icon: options.icon || null,
    shortcut: options.shortcut || null,
    action: options.action || null,
    href: options.href || null,
    dispatch: options.dispatch || null,
    disabled: options.disabled || false,
    hidden: options.hidden || false,
    danger: options.danger || false,
    type: options.type || 'item'
  };
}

/**
 * Create a divider
 */
export function menuDivider() {
  return { type: 'divider' };
}

/**
 * Common menu item presets for phpIP
 */
export const menuPresets = {
  // Matter actions
  openMatter: (matterId) => menuItem('Open', {
    icon: 'bi-box-arrow-up-right',
    shortcut: 'Cmd+O',
    href: `/matter/${matterId}`
  }),

  openInNewTab: (matterId) => menuItem('Open in new tab', {
    icon: 'bi-window-plus',
    shortcut: 'Cmd+Shift+O',
    action: () => window.open(`/matter/${matterId}`, '_blank')
  }),

  addEvent: (matterId) => menuItem('Add Event', {
    icon: 'bi-calendar-plus',
    shortcut: 'E',
    dispatch: { event: 'add-event', detail: { matterId } }
  }),

  addTask: (matterId) => menuItem('Add Task', {
    icon: 'bi-list-task',
    shortcut: 'T',
    dispatch: { event: 'add-task', detail: { matterId } }
  }),

  addActor: (matterId) => menuItem('Add Actor', {
    icon: 'bi-person-plus',
    shortcut: 'A',
    dispatch: { event: 'add-actor', detail: { matterId } }
  }),

  cloneMatter: (matterId) => menuItem('Clone Matter', {
    icon: 'bi-copy',
    shortcut: 'Cmd+D',
    dispatch: { event: 'clone-matter', detail: { matterId } }
  }),

  deleteMatter: (matterId) => menuItem('Delete', {
    icon: 'bi-trash',
    shortcut: 'Delete',
    danger: true,
    dispatch: { event: 'delete-matter', detail: { matterId } }
  }),

  // Generic actions
  edit: (action) => menuItem('Edit', {
    icon: 'bi-pencil',
    shortcut: 'E',
    action
  }),

  delete: (action) => menuItem('Delete', {
    icon: 'bi-trash',
    shortcut: 'Delete',
    danger: true,
    action
  }),

  copy: (action) => menuItem('Copy', {
    icon: 'bi-clipboard',
    shortcut: 'Cmd+C',
    action
  })
};
