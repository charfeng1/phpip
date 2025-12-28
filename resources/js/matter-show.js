/**
 * Matter Show Page Module
 *
 * Provides functionality for the matter detail/show page including:
 * - Actor management with popovers for adding/removing actors
 * - Title/classifier management
 * - Image upload functionality with drag-and-drop support (Alpine.js component)
 * - File drop zone for document processing
 * - Panel reloading after modal operations
 * - Summary generation and clipboard operations
 */

import { fetchREST, reloadPart, processSubmitErrors } from "./main.js";

/**
 * Gets the current content source URL for modals.
 * @returns {string} The current contentSrc URL
 */
let getContentSrc = () => {
  return window.contentSrc || "";
};

/**
 * Sets the content source URL for modals.
 * @param {string} value - The URL to set as contentSrc
 */
let setContentSrc = (value) => {
  window.contentSrc = value;
};

/**
 * Registers the Alpine.js image upload component.
 * Creates a reusable component for image upload/delete with drag-and-drop support.
 *
 * @returns {void}
 */
export function registerImageUpload() {
  if (window.Alpine) {
    window.Alpine.data("imageUpload", (initialData) => ({
      expanded: initialData.hasImage || false,
      imageUrl: initialData.imageUrl || "",
      classifierId: initialData.classifierId || null,
      matterId: initialData.matterId,
      showControls: false,

      /**
       * Uploads an image file to the server.
       * @param {File} file - The image file to upload
       * @returns {Promise<void>}
       */
      async uploadImage(file) {
        if (!file || !file.type.startsWith("image/")) return;

        const formData = new FormData();
        formData.append("matter_id", this.matterId);
        formData.append("type_code", "IMG");
        formData.append("image", file);

        try {
          const response = await fetch("/classifier", {
            method: "POST",
            headers: {
              "X-Requested-With": "XMLHttpRequest",
              "X-CSRF-TOKEN":
                document.head.querySelector("[name=csrf-token]").content,
            },
            body: formData,
          });

          if (response.ok) {
            const data = await response.text();
            this.classifierId = parseInt(data);
            this.imageUrl = "/classifier/" + data + "/img";
            this.showControls = false;
          }
        } catch (error) {
          console.error("Upload failed:", error);
        }
      },

      /**
       * Deletes the current image after user confirmation.
       * @returns {Promise<void>}
       */
      async deleteImage() {
        if (
          !this.classifierId ||
          !confirm(
            window.appConfig?.translations?.deleteImageConfirm ||
              "Delete this image?",
          )
        )
          return;

        try {
          const response = await fetchREST(
            "/classifier/" + this.classifierId,
            "DELETE",
          );
          if (response) {
            this.imageUrl = "";
            this.classifierId = null;
            this.expanded = false;
            this.showControls = false;
          }
        } catch (error) {
          console.error("Delete failed:", error);
        }
      },

      /**
       * Handles drag-and-drop events for image upload.
       * @param {DragEvent} e - The drop event
       */
      handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        const file = e.dataTransfer.files[0];
        if (file) this.uploadImage(file);
      },
    }));
  }
}

/**
 * Initializes the matter show page functionality.
 * Sets up event listeners and handlers for:
 * - Actor addition/removal popovers
 * - Title/classifier management
 * - Panel reloading after modal operations
 * - File drop zone for document processing
 *
 * @returns {void}
 */
export function initMatterShow() {
  // Actor processing using DaisyUI dropdown/popover

  /**
   * Popover element for adding actors.
   * @type {HTMLElement|null}
   */
  let actorPopover = null;

  /**
   * Current actor autocomplete event handler.
   * @type {Function|null}
   */
  let currentActorHandler = null;

  /**
   * Current role autocomplete event handler.
   * @type {Function|null}
   */
  let currentRoleHandler = null;

  /**
   * Creates or returns the actor popover element.
   * @returns {HTMLElement}
   */
  function getActorPopover() {
    if (!actorPopover) {
      actorPopover = document.createElement("div");
      actorPopover.id = "actorPopover";
      actorPopover.className =
        "absolute z-50 bg-base-100 border border-base-300 rounded-lg shadow-lg p-2 w-72";
      actorPopover.innerHTML =
        actorPopoverTemplate.content.firstElementChild.outerHTML;
      actorPopover.style.display = "none";
      document.body.appendChild(actorPopover);
    }
    return actorPopover;
  }

  /**
   * Removes event listeners to prevent memory leaks when popover changes.
   * @returns {void}
   */
  function cleanupListeners() {
    const popover = getActorPopover();
    const actorNameEl = popover.querySelector("#actorName");
    const roleNameEl = popover.querySelector("#roleName");
    if (currentActorHandler && actorNameEl) {
      actorNameEl.removeEventListener("acCompleted", currentActorHandler);
      currentActorHandler = null;
    }
    if (currentRoleHandler && roleNameEl) {
      roleNameEl.removeEventListener("acCompleted", currentRoleHandler);
      currentRoleHandler = null;
    }
  }

  /**
   * Hides the actor popover.
   */
  function hideActorPopover() {
    const popover = getActorPopover();
    popover.style.display = "none";
    const form = popover.querySelector("#addActorForm");
    if (form) form.reset();
    cleanupListeners();
  }

  /**
   * Shows the actor popover near the trigger element.
   * @param {HTMLElement} trigger - The element that triggered the popover
   */
  function showActorPopover(trigger) {
    const popover = getActorPopover();
    const form = popover.querySelector("#addActorForm");
    const roleNameEl = popover.querySelector("#roleName");
    const actorNameEl = popover.querySelector("#actorName");
    const actorSharedEl = popover.querySelector("#actorShared");
    const actorNotSharedEl = popover.querySelector("#actorNotShared");

    // Position the popover near the trigger
    const rect = trigger.getBoundingClientRect();
    popover.style.position = "fixed";
    popover.style.top = rect.bottom + 5 + "px";
    popover.style.left = Math.max(10, rect.left - 100) + "px";
    popover.style.display = "block";

    // Configure form based on trigger data
    if (trigger.hasAttribute("data-role_code")) {
      form["role"].value = trigger.dataset.role_code;
      roleNameEl.setAttribute("placeholder", trigger.dataset.role_name);
      form["shared"].value = trigger.dataset.shareable;
      if (trigger.dataset.shareable === "1") {
        actorSharedEl.checked = true;
      } else {
        actorNotSharedEl.checked = true;
      }
      actorNameEl.focus();
    } else {
      form["role"].value = "";
      roleNameEl.setAttribute("placeholder", "Role");
      form["shared"].value = "1";
      actorSharedEl.checked = true;
      roleNameEl.focus();
    }

    // Attach listener for actorName's "acCompleted"
    currentActorHandler = (event) => {
      const selectedItem = event.detail;
      if (selectedItem.key === "create") {
        fetchREST(
          "/actor",
          "POST",
          new URLSearchParams(
            "name=" +
              selectedItem.value.toUpperCase() +
              "&default_role=" +
              form.role.value,
          ),
        ).then((response) => {
          form.actor_id.value = response.id;
          actorNameEl.classList.add("input-success");
          actorNameEl.value = response.name;
        });
      } else {
        form.actor_id.value = selectedItem.key;
      }
    };
    actorNameEl.addEventListener("acCompleted", currentActorHandler);

    // Attach listener for roleName's "acCompleted"
    currentRoleHandler = (event) => {
      const selectedItem = event.detail;
      form.shared.value = selectedItem.shareable;
      if (selectedItem.shareable) {
        actorSharedEl.checked = true;
      } else {
        actorNotSharedEl.checked = true;
      }
    };
    roleNameEl.addEventListener("acCompleted", currentRoleHandler);

    // Handle shared radio buttons
    actorSharedEl.onclick = () => {
      form["shared"].value = "1";
    };
    actorNotSharedEl.onclick = () => {
      form["shared"].value = "0";
    };

    // Handle submit
    const submitBtn = popover.querySelector("#addActorSubmit");
    submitBtn.onclick = () => {
      const formData = new FormData(form);
      const params = new URLSearchParams(formData);
      fetchREST("/actor-pivot", "POST", params).then((data) => {
        if (data.errors) {
          processSubmitErrors(data.errors, form);
        } else {
          hideActorPopover();
          reloadPart(window.location.href, "actorPanel");
        }
      });
    };

    // Handle cancel
    const cancelBtn = popover.querySelector("#popoverCancel");
    cancelBtn.onclick = () => {
      hideActorPopover();
    };
  }

  // Handle clicks on add actor buttons
  document.addEventListener("click", (e) => {
    const addActorBtn = e.target.closest(".add-actor-btn");
    if (addActorBtn) {
      e.preventDefault();
      e.stopPropagation();
      const popover = getActorPopover();
      if (popover.style.display === "block") {
        hideActorPopover();
      }
      showActorPopover(addActorBtn);
      return;
    }

    // Close popover when clicking outside
    if (actorPopover && actorPopover.style.display === "block") {
      if (!actorPopover.contains(e.target)) {
        hideActorPopover();
      }
    }
  }); // End actor popover processing

  // Titles processing

  // Show the title creation form when the title panel is empty
  if (titlePanel && !titlePanel.querySelector("dt")) {
    // Trigger Alpine.js to show the add title form
    const addTitleBtn = titlePanel.querySelector('[role="button"]');
    if (addTitleBtn) addTitleBtn.click();
  }

  if (titlePanel) {
    titlePanel.onclick = (e) => {
      if (e.target.id == "addTitleSubmit") {
        const formData = new FormData(addTitleForm);
        const params = new URLSearchParams(formData);
        fetchREST("/classifier", "POST", params).then((data) => {
          if (data.errors) {
            processSubmitErrors(data.errors, addTitleForm);
            if (footerAlert) {
              footerAlert.innerHTML = data.message;
              footerAlert.classList.add("alert-error");
            }
          } else {
            reloadPart(window.location.href, "titlePanel");
          }
        });
      }
    };
  }

  // Ajax refresh various panels when a modal is closed (using native dialog close event)
  ajaxModal.addEventListener("close", (e) => {
    const contentSrc = getContentSrc();
    switch (contentSrc.split("/")[5]) {
      case "roleActors":
        reloadPart(window.location.href, "actorPanel");
        break;
      case "events":
      case "tasks":
      case "renewals":
      case "classifiers":
        reloadPart(window.location.href, "multiPanel");
        break;
      case "edit":
        reloadPart(window.location.href, "refsPanel");
        break;
    }
    setContentSrc("");
  });

  //  Generate summary and copy

  ajaxModal.onclick = (e) => {
    switch (e.target.id) {
      case "sumButton":
        /* write to the clipboard now */
        //var text = document.getElementById("tocopy").textContent;
        var node = document.getElementById("tocopy");

        var selection = getSelection();
        selection.removeAllRanges();

        var range = document.createRange();
        range.selectNodeContents(node);
        selection.addRange(range);

        var success = document.execCommand("copy");
        selection.removeAllRanges();
        return success;

      case "addTaskReset":
        e.target.closest("tr").innerHTML = "";
        break;

      // case 'addClassifierReset':
      //   // Doesn't work, probably not necessary
      //   bootstrap.Collapse.getOrCreateInstance(addClassifierRow).hide();
      //   break;
    }
  };

  // File drop zone management

  dropZone.ondragover = function () {
    this.classList.remove("bg-info");
    this.classList.add("bg-primary");
    return false;
  };
  dropZone.ondragleave = function () {
    this.classList.remove("bg-primary");
    this.classList.add("bg-info");
    return false;
  };
  dropZone.ondrop = function (event) {
    event.preventDefault();
    this.classList.add("bg-info");
    this.classList.remove("bg-primary");
    var file = event.dataTransfer.files[0];
    var formData = new FormData();
    formData.append("file", file);
    fetch(this.dataset.url, {
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN":
          document.head.querySelector("[name=csrf-token]").content,
      },
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok) {
          if (response.status == 422) {
            alert("Only DOCX files can be processed for the moment");
          }
          throw new Error("Response status " + response.status);
        }
        return response.blob();
      })
      .then((blob) => {
        // Simulate click on a temporary link to perform download
        var tempLink = document.createElement("a");
        tempLink.style.display = "none";
        tempLink.href = URL.createObjectURL(blob);
        tempLink.download = uid.outerText + "-" + file.name;
        document.body.appendChild(tempLink);
        tempLink.click();
        document.body.removeChild(tempLink);
      })
      .catch((error) => {
        console.error(error);
      });
    return false;
  };
}
