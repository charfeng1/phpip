/**
 * Modal Component
 *
 * A modern modal dialog with AJAX content loading support.
 * Replaces Bootstrap modal with Alpine.js-powered solution.
 *
 * Usage:
 *   <div x-data="modal" @open-modal.window="open($event.detail)">
 *     <dialog x-ref="dialog" class="modal" :class="{ 'modal-open': isOpen }">
 *       <div class="modal-box">
 *         <h3 x-text="title"></h3>
 *         <div x-html="content"></div>
 *       </div>
 *       <form method="dialog" class="modal-backdrop">
 *         <button @click="close">close</button>
 *       </form>
 *     </dialog>
 *   </div>
 */

export default function modal() {
  return {
    isOpen: false,
    title: '',
    content: '',
    size: 'md', // sm, md, lg, xl, full
    loading: false,
    closeable: true,
    onClose: null,
    onOpen: null,

    /**
     * Initialize
     */
    init() {
      // Store bound handlers for cleanup
      this._closeAllHandler = () => this.close();
      this._escapeHandler = (e) => {
        if (e.key === 'Escape' && this.isOpen && this.closeable) {
          this.close();
        }
      };

      // Listen for close-all events
      window.addEventListener('close-all', this._closeAllHandler);

      // Handle escape key
      document.addEventListener('keydown', this._escapeHandler);
    },

    /**
     * Cleanup event listeners
     */
    destroy() {
      window.removeEventListener('close-all', this._closeAllHandler);
      document.removeEventListener('keydown', this._escapeHandler);
    },

    /**
     * Open the modal
     * @param {Object} options
     */
    async open(options = {}) {
      this.title = options.title || '';
      this.size = options.size || 'md';
      this.closeable = options.closeable !== false;
      this.onClose = options.onClose || null;
      this.onOpen = options.onOpen || null;

      // Load content from URL or use provided content
      if (options.url) {
        this.loading = true;
        this.content = '';
        this.isOpen = true;

        try {
          const response = await fetch(options.url, {
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'text/html'
            }
          });

          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
          }

          this.content = await response.text();
        } catch (error) {
          console.error('Modal load error:', error);
          this.content = `
            <div class="alert alert-error">
              <span>Failed to load content. Please try again.</span>
            </div>
          `;
        } finally {
          this.loading = false;
        }
      } else {
        this.content = options.content || '';
        this.isOpen = true;
      }

      // Focus trap
      this.$nextTick(() => {
        const dialog = this.$refs.dialog;
        if (dialog) {
          const focusable = dialog.querySelector(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
          );
          if (focusable) focusable.focus();
        }

        // Callback
        if (this.onOpen) this.onOpen();
      });

      // Prevent body scroll
      document.body.style.overflow = 'hidden';
    },

    /**
     * Close the modal
     */
    close() {
      if (!this.closeable) return;

      this.isOpen = false;

      // Re-enable body scroll
      document.body.style.overflow = '';

      // Callback
      if (this.onClose) this.onClose();

      // Clean up after animation
      setTimeout(() => {
        this.content = '';
        this.title = '';
        this.loading = false;
      }, 200);
    },

    /**
     * Handle form submission within modal
     * @param {Event} e
     */
    async submitForm(e) {
      e.preventDefault();

      const form = e.target;
      let url = form.action;
      const method = form.method.toUpperCase();

      this.loading = true;

      try {
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const options = {
          method,
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        };

        if (csrfToken) {
          options.headers['X-CSRF-TOKEN'] = csrfToken;
        }

        if (method === 'GET') {
          const params = new URLSearchParams(formData);
          url += '?' + params.toString();
        } else {
          options.body = formData;
        }

        const response = await fetch(url, options);

        // Handle non-JSON responses (e.g., HTML error pages)
        let data;
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          data = await response.json();
        } else {
          const text = await response.text();
          throw new Error(response.ok ? 'Unexpected response format' : `Server error: ${response.status}`);
        }

        if (!response.ok) {
          // Handle validation errors
          if (response.status === 422 && data.errors) {
            this.showValidationErrors(form, data.errors);
          } else {
            throw new Error(data.message || 'Request failed');
          }
          return;
        }

        // Success - dispatch event and close
        window.dispatchEvent(new CustomEvent('modal-success', {
          detail: { data, form: form.id || null }
        }));

        // Show success message if provided
        if (data.message) {
          window.dispatchEvent(new CustomEvent('toast', {
            detail: { message: data.message, type: 'success' }
          }));
        }

        this.close();

        // Reload page section if specified
        if (data.reload) {
          window.location.reload();
        } else if (data.redirect) {
          window.location.href = data.redirect;
        }

      } catch (error) {
        console.error('Form submission error:', error);
        window.dispatchEvent(new CustomEvent('toast', {
          detail: { message: error.message || 'An error occurred', type: 'error' }
        }));
      } finally {
        this.loading = false;
      }
    },

    /**
     * Show validation errors on form fields
     * @param {HTMLFormElement} form
     * @param {Object} errors
     */
    showValidationErrors(form, errors) {
      // Clear previous errors
      form.querySelectorAll('.input-error, .select-error, .textarea-error')
        .forEach(el => {
          el.classList.remove('input-error', 'select-error', 'textarea-error');
        });
      form.querySelectorAll('.error-message')
        .forEach(el => el.remove());

      // Show new errors
      for (const [field, messages] of Object.entries(errors)) {
        const input = form.querySelector(`[name="${field}"]`);
        if (input) {
          // Add error class based on element type
          const inputType = input.tagName.toLowerCase();
          const errorClass = inputType === 'select' ? 'select-error'
            : inputType === 'textarea' ? 'textarea-error'
            : 'input-error';
          input.classList.add(errorClass);

          // Add error message
          const errorEl = document.createElement('p');
          errorEl.className = 'error-message text-error text-sm mt-1';
          errorEl.textContent = Array.isArray(messages) ? messages[0] : messages;
          input.parentNode.appendChild(errorEl);
        }
      }
    },

    /**
     * Get size class for modal box
     * @returns {string}
     */
    getSizeClass() {
      const sizes = {
        sm: 'max-w-sm',
        md: 'max-w-lg',
        lg: 'max-w-2xl',
        xl: 'max-w-4xl',
        full: 'max-w-full mx-4'
      };
      return sizes[this.size] || sizes.md;
    }
  };
}

/**
 * Confirm dialog helper
 * @param {Object} options
 * @returns {Promise<boolean>}
 */
export function confirm(options = {}) {
  return new Promise((resolve) => {
    const title = options.title || 'Confirm';
    const message = options.message || 'Are you sure?';
    const confirmText = options.confirmText || 'Confirm';
    const cancelText = options.cancelText || 'Cancel';
    const danger = options.danger || false;

    const content = `
      <p class="py-4">${message}</p>
      <div class="modal-action">
        <button class="btn btn-ghost" data-action="cancel">${cancelText}</button>
        <button class="btn ${danger ? 'btn-error' : 'btn-primary'}" data-action="confirm">${confirmText}</button>
      </div>
    `;

    // Use AbortController to clean up event listener on close
    const abortController = new AbortController();

    window.dispatchEvent(new CustomEvent('open-modal', {
      detail: {
        title,
        content,
        size: 'sm',
        onOpen: () => {
          const dialog = document.querySelector('.modal-open .modal-box');
          if (dialog) {
            dialog.addEventListener('click', (e) => {
              const action = e.target.dataset.action;
              if (action === 'confirm') {
                abortController.abort();
                resolve(true);
                window.dispatchEvent(new CustomEvent('close-all'));
              } else if (action === 'cancel') {
                abortController.abort();
                resolve(false);
                window.dispatchEvent(new CustomEvent('close-all'));
              }
            }, { signal: abortController.signal });
          }
        },
        onClose: () => {
          abortController.abort();
          resolve(false);
        }
      }
    }));
  });
}
