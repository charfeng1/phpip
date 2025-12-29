/**
 * Application Entry Point
 *
 * This module serves as the main entry point for the phpIP application.
 * It handles:
 * - Bootstrap and styling imports
 * - Alpine.js initialization and component registration
 * - Page-specific module initialization based on DOM elements (lazy-loaded)
 * - Global function exposure for use in Alpine.js and inline handlers
 *
 * Page-specific modules are dynamically imported for code splitting,
 * ensuring users only download code needed for the current page.
 */

// Styles: Tailwind CSS + DaisyUI + SCSS compatibility layer
import "../css/app.css";
import "../sass/app.scss";

// Import Alpine.js
import Alpine from "alpinejs";
window.Alpine = Alpine;

// Import and register new Alpine components
import { registerAllComponents } from "./components/index.js";
registerAllComponents(Alpine);

// Import main functionality (always needed)
import {
  initMain,
  fetchInto,
  fetchREST,
  reloadPart,
  debounce,
  submitModalForm,
  processSubmitErrors,
} from "./main.js";

// Expose utility functions globally for Alpine.js and inline handlers
// These must be available before Alpine starts for inline handlers
window.fetchInto = fetchInto;
window.fetchREST = fetchREST;
window.reloadPart = reloadPart;
window.debounce = debounce;
window.submitModalForm = submitModalForm;
window.processSubmitErrors = processSubmitErrors;
window.contentSrc = "";

// Register image upload component before Alpine starts (needed on matter-show)
// Dynamic import for matter-show specific functionality
if (document.getElementById("actorPanel")) {
  import("./matter-show.js").then(({ registerImageUpload }) => {
    registerImageUpload();
  });
}

Alpine.start();

/**
 * Initializes the application when DOM is ready.
 * Detects current page based on element IDs and dynamically imports modules.
 * Code splitting ensures only necessary code is loaded per page.
 *
 * @listens DOMContentLoaded
 */
document.addEventListener("DOMContentLoaded", () => {
  initMain();

  // Dynamically load page-specific modules (code splitting)
  // Each import() creates a separate chunk loaded on demand

  // Home page - dashboard with tasks
  if (document.getElementById("alltasks")) {
    import("./home.js").then(({ initHome }) => initHome());
  }

  // Matter detail page
  if (document.getElementById("actorPanel")) {
    import("./matter-show.js").then(({ initMatterShow }) => initMatterShow());
  }

  // Generic tables page
  if (document.getElementById("tableList")) {
    import("./tables.js").then(({ initTables }) => initTables());
  }

  // Matter listing page
  if (document.getElementById("matterList")) {
    import("./matter-index.js").then(({ initMatterIndex }) => initMatterIndex());
  }

  // Renewals management page
  if (document.getElementById("renewalList")) {
    import("./renewal-index.js").then(({ initRenewalIndex }) => initRenewalIndex());
  }

  // Actor listing page
  if (document.getElementById("actorList")) {
    import("./actor-index.js").then(({ initActorIndex }) => initActorIndex());
  }

  // User management page
  if (document.getElementById("userList")) {
    import("./user-index.js").then(({ initUserIndex }) => initUserIndex());
  }
});
