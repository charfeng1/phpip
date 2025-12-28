/**
 * Alpine.js Components Index
 *
 * Central export for all phpIP Alpine components.
 */

// Core components
import contextMenuFn, { menuItem, menuDivider, menuPresets } from './context-menu.js';
import keyboardShortcutsFn, { createShortcutRegistrar } from './keyboard-shortcuts.js';
import modalFn, { confirm } from './modal.js';
import dropdownFn, { selectDropdown } from './dropdown.js';
import autocompleteFn, { combobox, multiAutocomplete } from './autocomplete.js';
import inlineEditFn, { contentEditable, inlineSelect, inlineDate, batchInlineEdit } from './inline-edit.js';

// Re-export for direct imports
export {
  contextMenuFn as contextMenu,
  menuItem,
  menuDivider,
  menuPresets,
  keyboardShortcutsFn as keyboardShortcuts,
  createShortcutRegistrar,
  modalFn as modal,
  confirm,
  dropdownFn as dropdown,
  selectDropdown,
  autocompleteFn as autocomplete,
  combobox,
  multiAutocomplete,
  inlineEditFn as inlineEdit,
  contentEditable,
  inlineSelect,
  inlineDate,
  batchInlineEdit
};

/**
 * Register all components with Alpine
 * @param {Object} Alpine - The Alpine.js instance
 */
export function registerAllComponents(Alpine) {
  // Core UI components
  Alpine.data('contextMenu', contextMenuFn);
  Alpine.data('keyboardShortcuts', keyboardShortcutsFn);
  Alpine.data('modal', modalFn);
  Alpine.data('dropdown', dropdownFn);
  Alpine.data('selectDropdown', selectDropdown);

  // Form components
  Alpine.data('autocomplete', autocompleteFn);
  Alpine.data('combobox', combobox);
  Alpine.data('multiAutocomplete', multiAutocomplete);

  // Inline editing components
  Alpine.data('inlineEdit', inlineEditFn);
  Alpine.data('contentEditable', contentEditable);
  Alpine.data('inlineSelect', inlineSelect);
  Alpine.data('inlineDate', inlineDate);
  Alpine.data('batchInlineEdit', batchInlineEdit);
}
