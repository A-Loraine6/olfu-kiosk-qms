function initializeModalKeyboardShortcuts() {
  document.addEventListener("keydown", (e) => {
    if (e.target.tagName === "INPUT" || e.target.tagName === "SELECT" || e.target.tagName === "TEXTAREA") {
      if (e.key === "Enter" && e.target.tagName === "INPUT" && e.target.type !== "text" && e.target.type !== "email" && e.target.type !== "password") {
        return;
      }
    }

    const modals = document.querySelectorAll(".modal.show");
    if (modals.length === 0) return;

    const activeModal = modals[modals.length - 1];
    const modalId = activeModal?.id;

    if (!modalId) return;

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

    if (e.key === "Enter") {
      let confirmBtn = activeModal.querySelector("[data-action='confirm']");
      if (!confirmBtn) {
        confirmBtn = activeModal.querySelector(".btn-primary:not(.modal-close)");
      }
      if (!confirmBtn) {
        const buttons = activeModal.querySelectorAll(".modal-footer .btn");
        if (buttons.length > 0) {
          confirmBtn = buttons[buttons.length - 1]; 
        }
      }
      if (confirmBtn && !confirmBtn.disabled) {
        confirmBtn.click();
        e.preventDefault();
      }
      return;
    }

    if (e.key === "Tab") {
      const buttons = activeModal.querySelectorAll("button:not([disabled])");
      if (buttons.length === 0) return;

      const activeElement = document.activeElement;
      const activeIndex = Array.from(buttons).indexOf(activeElement);

      if (e.shiftKey) {
        const prevIndex = activeIndex <= 0 ? buttons.length - 1 : activeIndex - 1;
        buttons[prevIndex].focus();
      } else {
        const nextIndex = activeIndex < 0 || activeIndex >= buttons.length - 1 ? 0 : activeIndex + 1;
        buttons[nextIndex].focus();
      }
      e.preventDefault();
    }
  });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializeModalKeyboardShortcuts);
} else {
  initializeModalKeyboardShortcuts();
}
