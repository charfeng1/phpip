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

// Cache DOM check for matter-show page
const actorPanel = document.getElementById("actorPanel");

// Promise-based module loading to ensure proper timing between Alpine.start() and DOMContentLoaded
// This prevents race conditions where DOMContentLoaded fires before the async import completes
const matterShowReady = actorPanel
  ? import("./matter-show.js")
      .then((module) => {
        module.registerImageUpload();
        return module;
      })
      .catch((err) => {
        console.error("Failed to load matter-show module:", err);
        return null;
      })
  : Promise.resolve(null);

// Start Alpine after registering any required components
matterShowReady.then(() => Alpine.start());

/**
 * Initializes the application when DOM is ready.
 * Detects current page based on element IDs and dynamically imports modules.
 * Code splitting ensures only necessary code is loaded per page.
 *
 * @listens DOMContentLoaded
 */
document.addEventListener("DOMContentLoaded", async () => {
  initMain();

  // Dynamically load page-specific modules (code splitting)
  // Each import() creates a separate chunk loaded on demand

  // Home page - dashboard with tasks
  if (document.getElementById("alltasks")) {
    import("./home.js")
      .then(({ initHome }) => initHome())
      .catch((err) => console.error("Failed to load home module:", err));
  }

  // Matter detail page - await the cached module promise
  if (actorPanel) {
    const module = await matterShowReady;
    if (module) {
      module.initMatterShow();
    }
  }

  // Generic tables page
  if (document.getElementById("tableList")) {
    import("./tables.js")
      .then(({ initTables }) => initTables())
      .catch((err) => console.error("Failed to load tables module:", err));
  }

  // Matter listing page
  if (document.getElementById("matterList")) {
    import("./matter-index.js")
      .then(({ initMatterIndex }) => initMatterIndex())
      .catch((err) => console.error("Failed to load matter-index module:", err));
  }

  // Renewals management page
  if (document.getElementById("renewalList")) {
    import("./renewal-index.js")
      .then(({ initRenewalIndex }) => initRenewalIndex())
      .catch((err) => console.error("Failed to load renewal-index module:", err));
  }

  // Actor listing page
  if (document.getElementById("actorList")) {
    import("./actor-index.js")
      .then(({ initActorIndex }) => initActorIndex())
      .catch((err) => console.error("Failed to load actor-index module:", err));
  }

  // User management page
  if (document.getElementById("userList")) {
    import("./user-index.js")
      .then(({ initUserIndex }) => initUserIndex())
      .catch((err) => console.error("Failed to load user-index module:", err));
  }
});
