let archiveState = {
  deleteItemId: null,
  deleteItemType: null,
  restoreItemId: null,
  restoreItemType: null,
};

const archiveFilterDefaults = {
  search: "",
  sort: "date-desc",
};

function getArchiveTabElements(tabId) {
  const tab = document.getElementById(tabId);
  return {
    tab,
    search: tab?.querySelector(".archive-search-input"),
    sort: tab?.querySelector(".archive-sort-select"),
    list: tab?.querySelector(".archive-table-body"),
    items: [...(tab?.querySelectorAll(".archive-table-body .list-item") || [])],
  };
}

function renderArchiveFilters(tabId) {
  const { tab, search, sort, list, items } = getArchiveTabElements(tabId);
  if (!tab || !search || !sort || !list) return;

  const query = search.value.trim().toLocaleLowerCase();
  const matchingItems = items.filter((item) =>
    (item.dataset.search || "").toLocaleLowerCase().includes(query),
  );

  items.forEach((item) => {
    item.hidden = !matchingItems.includes(item);
  });

  const sortedItems = [...matchingItems].sort((first, second) => {
    if (sort.value.startsWith("date")) {
      const firstDate = Date.parse(first.dataset.archivedAt || "") || 0;
      const secondDate = Date.parse(second.dataset.archivedAt || "") || 0;
      return sort.value === "date-asc"
        ? firstDate - secondDate
        : secondDate - firstDate;
    }

    const nameCompare = (first.dataset.name || "").localeCompare(
      second.dataset.name || "",
      undefined,
      { sensitivity: "base" },
    );
    return sort.value === "name-desc" ? -nameCompare : nameCompare;
  });

  sortedItems.forEach((item) => list.appendChild(item));

  let emptyState = tab.querySelector(".archive-filter-empty");
  if (matchingItems.length === 0 && items.length > 0) {
    if (!emptyState) {
      emptyState = document.createElement("div");
      emptyState.className = "empty-state archive-filter-empty";
      emptyState.innerHTML =
        '<i class="fas fa-search"></i><h3>No matching archived records found</h3><p>Try adjusting your search or clearing the filters.</p>';
      tab.querySelector(".archive-card")?.append(emptyState);
    }
    emptyState.hidden = false;
  } else if (emptyState) {
    emptyState.hidden = true;
  }
}

function clearArchiveFilters(tabId) {
  const { search, sort } = getArchiveTabElements(tabId);
  if (!search || !sort) return;
  search.value = archiveFilterDefaults.search;
  sort.value = archiveFilterDefaults.sort;
  renderArchiveFilters(tabId);
}

document.querySelectorAll(".archive-filter-bar").forEach((filterBar) => {
  const tab = filterBar.closest(".tab-content");
  const tabId = tab?.id;
  if (!tabId) return;
  filterBar
    .querySelector(".archive-search-input")
    ?.addEventListener("input", () => renderArchiveFilters(tabId));
  filterBar
    .querySelector(".archive-sort-select")
    ?.addEventListener("change", () => renderArchiveFilters(tabId));
});

document.querySelectorAll("[data-clear-filters]").forEach((button) => {
  button.addEventListener("click", () =>
    clearArchiveFilters(button.dataset.clearFilters),
  );
});

document.querySelectorAll(".tab-content").forEach((tab) => {
  renderArchiveFilters(tab.id);
});

document.querySelectorAll(".tab-btn").forEach((btn) => {
  btn.addEventListener("click", () => {
    const tabId = btn.getAttribute("data-tab");

    document
      .querySelectorAll(".tab-btn")
      .forEach((b) => b.classList.remove("active"));
    document
      .querySelectorAll(".tab-content")
      .forEach((t) => t.classList.remove("active"));

    btn.classList.add("active");
    document.getElementById(tabId).classList.add("active");
  });
});

function openRestoreStaffModal(staffId) {
  archiveState.restoreItemId = staffId;
  archiveState.restoreItemType = "staff";

  const itemName =
    document.querySelector(`[data-id="${staffId}"] .item-name`)?.textContent ||
    "this staff member";
  const message = `Restore ${itemName}? They will be inactive until you activate them.`;

  document.getElementById("restoreArchiveMessage").textContent = message;
  openModal("restoreArchiveModal");
}

async function confirmRestore() {
  const { restoreItemId, restoreItemType } = archiveState;

  if (!restoreItemId || !restoreItemType) return;

  const btn = document.getElementById("confirmRestoreBtn");
  if (btn.disabled) return;
  setBtnLoading(btn, true);

  try {
    const action =
      restoreItemType === "staff" ? "restore_staff" : "restore_service";
    const payload = {
      action,
      csrf_token: getCsrfToken(),
    };

    if (restoreItemType === "staff") {
      payload.staff_id = restoreItemId;
    } else {
      payload.service_id = restoreItemId;
    }

    const response = await fetch("api/archive_actions.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    const data = await response.json();

    if (data.success) {
      closeModal("restoreArchiveModal");
      displayNotification(
        data.message || "Item restored successfully.",
        "success",
      );
      setTimeout(() => location.reload(), 500);
    } else {
      displayNotification(data.message || "Failed to restore item.", "error");
    }
  } catch (error) {
    displayNotification("An error occurred while restoring item.", "error");
  }

  setBtnLoading(btn, false);
}

function restoreStaff(staffId) {
  openRestoreStaffModal(staffId);
}

function restoreService(serviceId) {
  archiveState.restoreItemId = serviceId;
  archiveState.restoreItemType = "service";

  const itemName =
    document.querySelector(`[data-id="${serviceId}"] .item-name`)
      ?.textContent || "this service";
  const message = `Restore ${itemName}? It will be inactive until you activate it.`;

  document.getElementById("restoreArchiveMessage").textContent = message;
  openModal("restoreArchiveModal");
}

function openDeleteArchiveModal(itemId, itemType) {
  archiveState.deleteItemId = itemId;
  archiveState.deleteItemType = itemType;

  const itemName =
    document.querySelector(`[data-id="${itemId}"] .item-name`)?.textContent ||
    "this item";
  const message = `Are you sure you want to permanently delete "${itemName}"? This cannot be undone.`;

  document.getElementById("deleteArchiveMessage").textContent = message;
  openModal("deleteArchiveModal");
}

async function confirmDeleteArchive() {
  const { deleteItemId, deleteItemType } = archiveState;

  if (!deleteItemId || !deleteItemType) return;

  const btn = document.getElementById("confirmDeleteArchiveBtn");
  if (btn.disabled) return;
  setBtnLoading(btn, true);

  try {
    const action =
      deleteItemType === "staff"
        ? "delete_staff_permanently"
        : "delete_service_permanently";
    const payload = {
      action,
      csrf_token: getCsrfToken(),
    };

    if (deleteItemType === "staff") {
      payload.staff_id = deleteItemId;
    } else {
      payload.service_id = deleteItemId;
    }

    const response = await fetch("api/archive_actions.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    const data = await response.json();

    if (data.success) {
      closeModal("deleteArchiveModal");
      displayNotification("Item permanently deleted successfully.", "success");
      setTimeout(() => location.reload(), 500);
    } else {
      displayNotification(data.message || "Failed to delete item.", "error");
    }
  } catch (error) {
    displayNotification("An error occurred while deleting item.", "error");
  }

  setBtnLoading(btn, false, '<i class="fas fa-trash"></i> Delete Permanently');
}

function getCsrfToken() {
  return CSRF_TOKEN || "";
}

function setBtnLoading(
  btn,
  loading,
  fallbackHTML = '<i class="fas fa-undo"></i> Restore',
) {
  if (!btn) return;
  if (loading) {
    btn.disabled = true;
    btn.dataset.originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.style.opacity = "0.6";
  } else {
    btn.disabled = false;
    btn.innerHTML = btn.dataset.originalText || fallbackHTML;
    btn.style.opacity = "1";
  }
}

function displayNotification(message, type = "info") {
  if (
    window.showToastNotification &&
    typeof window.showToastNotification === "function"
  ) {
    window.showToastNotification(message, type);
  } else if (typeof window.Toast !== "undefined" && window.Toast.show) {
    window.Toast.show(message, type);
  } else {
    console.log(`[${type.toUpperCase()}] ${message}`);
  }
}

function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.style.display = "flex";
    requestAnimationFrame(() => modal.classList.add("show"));
  }
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.remove("show");
    setTimeout(() => (modal.style.display = "none"), 300);
  }
}

document.querySelectorAll(".modal").forEach((m) => {
  m.addEventListener("click", (e) => {
    if (e.target === m) closeModal(m.id);
  });
});

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    document.querySelectorAll(".modal.show").forEach((m) => closeModal(m.id));
  }
});

document.addEventListener("DOMContentLoaded", () => {
  if (TAB_COUNTS.staff === 0 && TAB_COUNTS.services > 0) {
    document.querySelector('[data-tab="services-tab"]')?.click();
  }
});
