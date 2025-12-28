/**
 * API Utility Module
 *
 * Provides a centralized, consistent interface for making API requests.
 * Handles CSRF tokens, common error responses, and provides typed request methods.
 *
 * Usage:
 * ```javascript
 * import { api, getCsrfToken } from './utils/api';
 *
 * // Simple GET request
 * const data = await api.get('/matters/1');
 *
 * // POST with form data
 * const result = await api.post('/matters', formData);
 *
 * // PUT with JSON body
 * await api.put('/matters/1', { status: 'active' });
 *
 * // DELETE request
 * await api.delete('/matters/1');
 * ```
 */

/**
 * HTTP status codes for reference
 */
export const HttpStatus = {
  OK: 200,
  CREATED: 201,
  NO_CONTENT: 204,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  UNPROCESSABLE_ENTITY: 422,
  TOKEN_EXPIRED: 419,
  SERVER_ERROR: 500,
};

/**
 * Get the CSRF token from the page meta tag.
 *
 * @returns {string} The CSRF token
 * @throws {Error} If CSRF token meta tag is not found
 */
export function getCsrfToken() {
  const meta = document.head.querySelector('[name="csrf-token"]');
  if (!meta) {
    throw new Error(
      "CSRF token not found. Ensure <meta name='csrf-token'> exists in the page."
    );
  }
  return meta.content;
}

/**
 * Build common headers for API requests.
 *
 * @param {Object} additionalHeaders - Extra headers to merge
 * @returns {Object} Headers object
 */
export function buildHeaders(additionalHeaders = {}) {
  return {
    "X-Requested-With": "XMLHttpRequest",
    "X-CSRF-TOKEN": getCsrfToken(),
    ...additionalHeaders,
  };
}

/**
 * Custom error class for API errors.
 */
export class ApiError extends Error {
  constructor(message, status, data = null) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.data = data;
  }
}

/**
 * Handle common HTTP error responses.
 *
 * @param {Response} response - The fetch response
 * @returns {Promise<Response>} The response if OK
 * @throws {ApiError} For error status codes
 */
async function handleResponse(response) {
  if (response.ok) {
    return response;
  }

  let errorMessage;
  let errorData = null;

  switch (response.status) {
    case HttpStatus.TOKEN_EXPIRED:
      errorMessage = "Session expired. Please refresh the page.";
      break;
    case HttpStatus.UNPROCESSABLE_ENTITY:
      try {
        errorData = await response.json();
        errorMessage = errorData.message || "Validation failed";
      } catch {
        errorMessage = "Validation failed";
      }
      break;
    case HttpStatus.UNAUTHORIZED:
      errorMessage = "Authentication required";
      break;
    case HttpStatus.FORBIDDEN:
      errorMessage = "You do not have permission to perform this action";
      break;
    case HttpStatus.NOT_FOUND:
      errorMessage = "Resource not found";
      break;
    case HttpStatus.SERVER_ERROR:
      try {
        const text = await response.text();
        errorMessage = `Server error: ${text}`;
      } catch {
        errorMessage = "An unexpected server error occurred";
      }
      break;
    default:
      errorMessage = `Request failed with status ${response.status}`;
  }

  throw new ApiError(errorMessage, response.status, errorData);
}

/**
 * Parse response based on content type.
 *
 * @param {Response} response - The fetch response
 * @returns {Promise<*>} Parsed response data
 */
async function parseResponse(response) {
  const contentType = response.headers.get("content-type") || "";

  if (response.status === HttpStatus.NO_CONTENT) {
    return null;
  }

  if (contentType.includes("application/json")) {
    return response.json();
  }

  if (contentType.includes("text/html") || contentType.includes("text/plain")) {
    return response.text();
  }

  // For blobs (file downloads)
  if (
    contentType.includes("application/octet-stream") ||
    contentType.includes("application/pdf")
  ) {
    return response.blob();
  }

  // Default to JSON
  return response.json();
}

/**
 * Prepare request body based on data type.
 *
 * @param {*} data - The data to send
 * @returns {Object} Object with body and additional headers
 */
function prepareBody(data) {
  if (data === undefined || data === null) {
    return { body: undefined, headers: {} };
  }

  // FormData - send as-is
  if (data instanceof FormData) {
    return { body: data, headers: {} };
  }

  // URLSearchParams - send as-is
  if (data instanceof URLSearchParams) {
    return {
      body: data,
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
    };
  }

  // Plain object - convert to JSON
  if (typeof data === "object") {
    return {
      body: JSON.stringify(data),
      headers: { "Content-Type": "application/json" },
    };
  }

  // String - send as-is
  return { body: data, headers: {} };
}

/**
 * Make a fetch request with common options.
 *
 * @param {string} url - The URL to fetch
 * @param {Object} options - Fetch options
 * @returns {Promise<*>} The parsed response
 */
async function request(url, options = {}) {
  const { method = "GET", data, headers: customHeaders = {}, ...rest } = options;

  const { body, headers: bodyHeaders } = prepareBody(data);

  const response = await fetch(url, {
    method,
    headers: buildHeaders({ ...bodyHeaders, ...customHeaders }),
    body,
    ...rest,
  });

  const validResponse = await handleResponse(response);
  return parseResponse(validResponse);
}

/**
 * API client with convenience methods for each HTTP verb.
 */
export const api = {
  /**
   * Make a GET request.
   *
   * @param {string} url - The URL to fetch
   * @param {Object} options - Additional fetch options
   * @returns {Promise<*>}
   */
  get(url, options = {}) {
    return request(url, { ...options, method: "GET" });
  },

  /**
   * Make a POST request.
   *
   * @param {string} url - The URL to fetch
   * @param {*} data - Data to send (FormData, URLSearchParams, or object)
   * @param {Object} options - Additional fetch options
   * @returns {Promise<*>}
   */
  post(url, data, options = {}) {
    return request(url, { ...options, method: "POST", data });
  },

  /**
   * Make a PUT request.
   *
   * @param {string} url - The URL to fetch
   * @param {*} data - Data to send
   * @param {Object} options - Additional fetch options
   * @returns {Promise<*>}
   */
  put(url, data, options = {}) {
    return request(url, { ...options, method: "PUT", data });
  },

  /**
   * Make a PATCH request.
   *
   * @param {string} url - The URL to fetch
   * @param {*} data - Data to send
   * @param {Object} options - Additional fetch options
   * @returns {Promise<*>}
   */
  patch(url, data, options = {}) {
    return request(url, { ...options, method: "PATCH", data });
  },

  /**
   * Make a DELETE request.
   *
   * @param {string} url - The URL to fetch
   * @param {Object} options - Additional fetch options
   * @returns {Promise<*>}
   */
  delete(url, options = {}) {
    return request(url, { ...options, method: "DELETE" });
  },

  /**
   * Submit a form via AJAX.
   *
   * @param {HTMLFormElement} form - The form element
   * @param {Object} options - Additional options
   * @returns {Promise<*>}
   */
  submitForm(form, options = {}) {
    const url = form.action;
    const method = form.method?.toUpperCase() || "POST";
    const data = new FormData(form);

    return request(url, { ...options, method, data });
  },
};

/**
 * Fetch HTML content and inject into an element.
 *
 * Uses centralized headers for CSRF token and standard error handling.
 *
 * @param {string} url - The URL to fetch
 * @param {HTMLElement} element - The element to populate
 * @returns {Promise<void>}
 */
export async function fetchHtml(url, element) {
  const response = await fetch(url, {
    headers: buildHeaders({ Accept: "text/html" }),
  });
  if (!response.ok) {
    throw new ApiError("Failed to load content", response.status);
  }
  element.innerHTML = await response.text();
}

/**
 * Fetch HTML and update a specific element by ID.
 *
 * Uses centralized headers for CSRF token and standard error handling.
 *
 * @param {string} url - The URL to fetch
 * @param {string} elementId - The ID of the element to update
 * @returns {Promise<void>}
 */
export async function refreshElement(url, elementId) {
  const response = await fetch(url, {
    headers: buildHeaders({ Accept: "text/html" }),
  });
  if (!response.ok) {
    throw new ApiError("Failed to refresh content", response.status);
  }
  const doc = new DOMParser().parseFromString(await response.text(), "text/html");
  const sourceElement = doc.getElementById(elementId);
  const targetElement = document.getElementById(elementId);

  if (sourceElement && targetElement) {
    targetElement.innerHTML = sourceElement.innerHTML;
  }
}

/**
 * Show loading spinner on a button.
 *
 * @param {HTMLButtonElement} button - The button element
 * @returns {Function} Function to remove the spinner
 */
export function showButtonSpinner(button) {
  const spinner = document.createElement("i");
  spinner.className = "spinner-border spinner-border-sm me-1";
  spinner.setAttribute("role", "status");
  button.insertBefore(spinner, button.firstChild);
  button.disabled = true;

  return () => {
    spinner.remove();
    button.disabled = false;
  };
}

/**
 * Display validation errors on form fields.
 *
 * @param {Object} errors - Object with field names as keys and error arrays as values
 * @param {HTMLFormElement} form - The form containing the fields
 */
export function displayFormErrors(errors, form) {
  // Clear previous errors
  form.querySelectorAll(".is-invalid").forEach((el) => {
    el.classList.remove("is-invalid");
  });
  form.querySelectorAll(".invalid-feedback").forEach((el) => {
    el.remove();
  });

  // Display new errors
  Object.entries(errors).forEach(([field, messages]) => {
    // Try to find by data-actarget first (for autocomplete fields)
    let input = form.querySelector(`[data-actarget="${field}"]`);
    if (!input) {
      input = form.elements[field];
    }

    if (input) {
      input.classList.add("is-invalid");

      // Create feedback element
      const feedback = document.createElement("div");
      feedback.className = "invalid-feedback";
      feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
      input.parentNode?.appendChild(feedback);
    }
  });
}

export default api;
