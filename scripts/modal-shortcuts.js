/**
 * Unified Modal Keyboard Shortcuts
 * Apply to all modals: Enter = confirm, Esc = cancel
 */
function initializeModalKeyboardShortcuts() {
  document.addEventListener("keydown", (e) => {
    if (e.target.tagName === "INPUT" || e.target.tagName === "SELECT" || e.target.tagName === "TEXTAREA") {
      // Allow normal input behavior, but capture Enter if inside modal
      if (e.key === "Enter" && e.target.tagName === "INPUT" && e.target.type !== "text" && e.target.type !== "email" && e.target.type !== "password") {
        return;
      }
    }

    // Find if there's an open modal
    const modals = document.querySelectorAll(".modal.show");
    if (modals.length === 0) return;

    const activeModal = modals[modals.length - 1]; // Last (topmost) modal
    const modalId = activeModal?.id;

    if (!modalId) return;

    // Handle Escape key - always closes the topmost modal
    if (e.key === "Escape") {
      const closeBtn = activeModal.querySelector(".modal-close");
      if (closeBtn) {
        closeBtn.click();
      } else {
        closeModal(modalId);
      }
      e.preventDefault();
      return;
    }

    // Handle Enter key - submit/confirm
    if (e.key === "Enter") {
      // Find the primary action button (confirm/save)
      let confirmBtn = activeModal.querySelector("[data-action='confirm']");
      if (!confirmBtn) {
        confirmBtn = activeModal.querySelector(".btn-primary:not(.modal-close)");
      }
      if (!confirmBtn) {
        const buttons = activeModal.querySelectorAll(".modal-footer .btn");
        if (buttons.length > 0) {
          confirmBtn = buttons[buttons.length - 1]; // Last button is usually the action button
        }
      }
      if (confirmBtn && !confirmBtn.disabled) {
        confirmBtn.click();
        e.preventDefault();
      }
      return;
    }

    // Handle Tab key for button navigation within modal
    if (e.key === "Tab") {
      const buttons = activeModal.querySelectorAll("button:not([disabled])");
      if (buttons.length === 0) return;

      const activeElement = document.activeElement;
      const activeIndex = Array.from(buttons).indexOf(activeElement);

      if (e.shiftKey) {
        // Shift+Tab - previous button
        const prevIndex = activeIndex <= 0 ? buttons.length - 1 : activeIndex - 1;
        buttons[prevIndex].focus();
      } else {
        // Tab - next button
        const nextIndex = activeIndex < 0 || activeIndex >= buttons.length - 1 ? 0 : activeIndex + 1;
        buttons[nextIndex].focus();
      }
      e.preventDefault();
    }
  });
}

// Initialize on document ready
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializeModalKeyboardShortcuts);
} else {
  initializeModalKeyboardShortcuts();
}
