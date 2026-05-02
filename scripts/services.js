const AJAX_URL = "api/service_actions.php";
let currentRequest = 0;

let currentPage = 1;
let currentLimit =
  typeof PAGINATION_LIMIT !== "undefined" ? PAGINATION_LIMIT : 10;
let currentTotal = typeof TOTAL_SERVICES !== "undefined" ? TOTAL_SERVICES : 0;
let currentTotalPages = Math.ceil(currentTotal / currentLimit) || 1;

document.addEventListener("DOMContentLoaded", () => {
  fetchFilteredRows();
});

async function ajax(payload) {
  const res = await fetch(AJAX_URL, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ ...payload, csrf_token: CSRF_TOKEN }),
  });
  return res.json();
}

function showToast(message, type = "success") {
  const container = document.getElementById("toast-container");
  const toast = document.createElement("div");
  const icon = type === "success" ? "fa-check-circle" : "fa-exclamation-circle";
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <i class="fas ${icon}"></i>
    <span>${message}</span>
    <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
  `;
  container.appendChild(toast);
  requestAnimationFrame(() => toast.classList.add("show"));
  setTimeout(() => {
    toast.style.transition = "opacity 0.4s, transform 0.4s";
    toast.style.opacity = "0";
    toast.style.transform = "translateX(100%)";
    setTimeout(() => toast.remove(), 400);
  }, 4000);
}

function updateStats(data) {
  if (data.totalServices !== undefined)
    document.getElementById("stat-total").textContent = data.totalServices;
  if (data.activeServices !== undefined)
    document.getElementById("stat-active").textContent = data.activeServices;
  if (data.inactiveServices !== undefined)
    document.getElementById("stat-inactive").textContent =
      data.inactiveServices;
}

function updateTable(html) {
  const tbody = document.getElementById("servicesTableBody");
  tbody.style.transition = "opacity 0.2s ease";
  tbody.style.opacity = "0";
  setTimeout(() => {
    tbody.innerHTML = html;
    tbody.style.opacity = "1";
  }, 150);
}

function setTableLoading(on) {
  const el = document.getElementById("tableLoading");
  if (!el) return;
  el.style.transition = "opacity 0.2s ease";
  if (on) {
    el.style.display = "flex";
    requestAnimationFrame(() => (el.style.opacity = "1"));
  } else {
    el.style.opacity = "0";
    setTimeout(() => (el.style.display = "none"), 200);
  }
}

function updateResultCount(total, limit, page) {
  const el = document.getElementById("resultCount");
  if (!el) return;
  if (total === 0) {
    el.textContent = "No results";
    return;
  }
  const from = (page - 1) * limit + 1;
  const to = Math.min(page * limit, total);
  el.textContent = `${from}–${to} of ${total}`;
}

function renderPagination(page, totalPages, total, limit) {
  const bar = document.getElementById("paginationBar");
  const infoEl = document.getElementById("paginationInfo");
  const ctrlEl = document.getElementById("paginationControls");
  if (!bar || !infoEl || !ctrlEl) return;

  bar.style.display = "flex";

  const from = total === 0 ? 0 : (page - 1) * limit + 1;
  const to = Math.min(page * limit, total);
  infoEl.textContent =
    total === 0
      ? "No results"
      : `Showing ${from}–${to} of ${total} service${total !== 1 ? "s" : ""}`;

  ctrlEl.innerHTML = "";

  const navBtn = (iconClass, title, disabled, onClick) => {
    const b = document.createElement("button");
    b.className = "page-nav-btn";
    b.title = title;
    b.disabled = disabled;
    b.innerHTML = `<i class="${iconClass}"></i>`;
    if (!disabled) b.addEventListener("click", onClick);
    return b;
  };

  const pageBtn = (label, active, onClick) => {
    const b = document.createElement("button");
    b.className = "page-nav-btn" + (active ? " page-nav-active" : "");
    b.innerHTML = label;
    if (onClick) b.addEventListener("click", onClick);
    return b;
  };

  ctrlEl.appendChild(
    navBtn("fas fa-angle-double-left", "First page", page <= 1, () =>
      goToPage(1),
    ),
  );
  ctrlEl.appendChild(
    navBtn("fas fa-angle-left", "Previous page", page <= 1, () =>
      goToPage(page - 1),
    ),
  );

  buildPageList(page, totalPages).forEach((p) => {
    if (p === "…") {
      const e = document.createElement("button");
      e.className = "page-nav-btn page-ellipsis";
      e.textContent = "…";
      e.disabled = true;
      ctrlEl.appendChild(e);
    } else {
      ctrlEl.appendChild(
        pageBtn(p, p === page, p !== page ? () => goToPage(p) : null),
      );
    }
  });

  ctrlEl.appendChild(
    navBtn("fas fa-angle-right", "Next page", page >= totalPages, () =>
      goToPage(page + 1),
    ),
  );
  ctrlEl.appendChild(
    navBtn("fas fa-angle-double-right", "Last page", page >= totalPages, () =>
      goToPage(totalPages),
    ),
  );

  const sel = document.getElementById("pageLimitSelect");
  if (sel) sel.value = limit;
}

function buildPageList(current, total) {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

  const pages = [];
  const addPage = (p) => {
    if (!pages.includes(p)) pages.push(p);
  };
  const addEllipsis = () => {
    if (pages[pages.length - 1] !== "…") pages.push("…");
  };

  addPage(1);
  if (current > 3) addEllipsis();
  for (
    let p = Math.max(2, current - 1);
    p <= Math.min(total - 1, current + 1);
    p++
  )
    addPage(p);
  if (current < total - 2) addEllipsis();
  addPage(total);

  return pages;
}

function goToPage(page) {
  currentPage = page;
  fetchFilteredRows();
}

function changePageLimit() {
  const sel = document.getElementById("pageLimitSelect");
  if (sel) currentLimit = parseInt(sel.value, 10);
  currentPage = 1;
  fetchFilteredRows();
}

let searchDebounce;

function applyFilters() {
  currentPage = 1;
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(fetchFilteredRows, 250);
}

document.getElementById("searchInput").addEventListener("input", function () {
  const clearBtn = document.getElementById("searchClear");
  clearBtn.style.opacity = this.value ? "1" : "0";
  currentPage = 1;
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(fetchFilteredRows, 280);
});

function clearSearch() {
  document.getElementById("searchInput").value = "";
  document.getElementById("searchClear").style.opacity = "0";
  currentPage = 1;
  fetchFilteredRows();
}

async function fetchFilteredRows() {
  const requestId = ++currentRequest;
  setTableLoading(true);

  const data = await ajax({
    action: "get_services",
    search: document.getElementById("searchInput").value,
    office: document.getElementById("filterOffice").value,
    status: document.getElementById("filterStatus").value,
    page: currentPage,
    limit: currentLimit,
  });

  if (requestId !== currentRequest) return;
  setTableLoading(false);

  if (data.success) {
    currentTotal = data.count;
    currentTotalPages = data.totalPages;
    currentPage = data.page;

    updateTable(data.html);
    updateResultCount(currentTotal, currentLimit, currentPage);
    renderPagination(
      currentPage,
      currentTotalPages,
      currentTotal,
      currentLimit,
    );
  }
}

async function toggleQueueMode(officeId, btn) {
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

  const data = await ajax({ action: "toggle_queue_mode", office_id: officeId });

  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-exchange-alt"></i>';

  if (data.success) {
    const isServiceBased = data.message.includes("Service-Based");
    const label = document.getElementById(`office-mode-label-${officeId}`);
    const desc = document.getElementById(`office-mode-desc-${officeId}`);
    label.textContent = isServiceBased ? "Service-Based" : "Unified";
    label.className =
      "queue-mode-value " + (isServiceBased ? "mode-service" : "mode-unified");
    desc.textContent = isServiceBased
      ? "Kiosk shows service selection before issuing a number."
      : "Kiosk issues a number directly without service selection.";
    showToast(data.message, "success");
  } else {
    showToast(data.message, "error");
  }
}

function openModal(id) {
  const modal = document.getElementById(id);
  modal.style.display = "flex";
  requestAnimationFrame(() => modal.classList.add("show"));
}

function closeModal(id) {
  const modal = document.getElementById(id);
  modal.classList.remove("show");
  setTimeout(() => (modal.style.display = "none"), 300);
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

function closeAllDropdowns() {
  document
    .querySelectorAll(".dropdown.active")
    .forEach((d) => d.classList.remove("active"));
}

document.addEventListener("click", function (e) {
  const toggle = e.target.closest(".dropdown-toggle");
  if (toggle) {
    const dropdown = toggle.closest(".dropdown");
    const isAlreadyOn = dropdown.classList.contains("active");
    closeAllDropdowns();
    if (!isAlreadyOn) dropdown.classList.add("active");
    e.stopPropagation();
    return;
  }
  closeAllDropdowns();
});

window.addEventListener("scroll", closeAllDropdowns, true);
window.addEventListener("resize", closeAllDropdowns);

async function openEditServiceModal(svc) {
  document.getElementById("edit_service_id").value = svc.id;
  document.getElementById("edit_name").value = svc.name;
  document.getElementById("edit_office").value = svc.office_id;
  document.getElementById("edit_category").value = svc.category || "";
  document.getElementById("edit_description").value = svc.description || "";

  const winData = await ajax({
    action: "get_windows_for_service",
    service_id: svc.id,
  });
  const assigned = winData.success ? winData.windows : [];
  await loadServiceWindowCheckboxes(
    "edit_office",
    "edit_windows_list",
    "edit_windows_group",
    assigned,
  );

  openModal("editServiceModal");
}

function openToggleModal(id, isActive) {
  document.getElementById("toggle_service_id").value = id;
  const title = document.getElementById("toggleModalTitle");
  const body = document.getElementById("toggleModalBody");
  const btn = document.getElementById("toggleSubmitBtn");
  if (isActive) {
    title.textContent = "Deactivate Service";
    body.textContent =
      "Deactivate this service? It will be hidden from the kiosk.";
    btn.textContent = "Deactivate";
    btn.className = "btn-warning";
  } else {
    title.textContent = "Activate Service";
    body.textContent =
      "Activate this service? It will become visible on the kiosk.";
    btn.textContent = "Activate";
    btn.className = "btn-primary";
  }
  openModal("toggleServiceModal");
}

function openDeleteModal(id) {
  document.getElementById("delete_service_id").value = id;
  openModal("deleteServiceModal");
}

function setBtnLoading(btnId, loading, defaultHtml) {
  const btn = document.getElementById(btnId);
  if (!btn) return;
  btn.disabled = loading;
  btn.innerHTML = loading
    ? '<i class="fas fa-spinner fa-spin"></i> Please wait…'
    : defaultHtml;
}

async function submitAddService() {
  setBtnLoading(
    "addServiceBtn",
    true,
    '<i class="fas fa-plus"></i> Add Service',
  );
  const data = await ajax({
    action: "add_service",
    name: document.getElementById("add_name").value,
    office_id: document.getElementById("add_office").value,
    category: document.getElementById("add_category").value,
    description: document.getElementById("add_description").value,
    windows: getCheckedWindows("add_windows_list"),
  });
  setBtnLoading(
    "addServiceBtn",
    false,
    '<i class="fas fa-plus"></i> Add Service',
  );

  if (data.success) {
    closeModal("addServiceModal");
    ["add_name", "add_description"].forEach(
      (id) => (document.getElementById(id).value = ""),
    );
    document.getElementById("add_office").selectedIndex = 0;
    document.getElementById("add_category").selectedIndex = 0;
    document.getElementById("add_windows_list").innerHTML = "";
    document.getElementById("add_windows_group").style.display = "none";

    currentPage = 1;
    updateTable(data.tableHtml);
    updateStats(data);
    renderPagination(data.page, data.totalPages, data.count, data.limit);
    updateResultCount(data.count, data.limit, data.page);
    showToast(data.message, "success");
  } else {
    showToast(data.message, "error");
  }
}

async function submitEditService() {
  setBtnLoading(
    "editServiceBtn",
    true,
    '<i class="fas fa-save"></i> Save Changes',
  );
  const data = await ajax({
    action: "edit_service",
    service_id: document.getElementById("edit_service_id").value,
    name: document.getElementById("edit_name").value,
    office_id: document.getElementById("edit_office").value,
    category: document.getElementById("edit_category").value,
    description: document.getElementById("edit_description").value,
    windows: getCheckedWindows("edit_windows_list"),
  });
  setBtnLoading(
    "editServiceBtn",
    false,
    '<i class="fas fa-save"></i> Save Changes',
  );

  if (data.success) {
    closeModal("editServiceModal");
    currentPage = 1;
    updateTable(data.tableHtml);
    updateStats(data);
    renderPagination(data.page, data.totalPages, data.count, data.limit);
    updateResultCount(data.count, data.limit, data.page);
    showToast(data.message, "success");
  } else {
    showToast(data.message, "error");
  }
}

async function submitToggleService() {
  setBtnLoading(
    "toggleSubmitBtn",
    true,
    document.getElementById("toggleSubmitBtn").textContent,
  );
  const data = await ajax({
    action: "toggle_service",
    service_id: document.getElementById("toggle_service_id").value,
  });
  setBtnLoading(
    "toggleSubmitBtn",
    false,
    document.getElementById("toggleSubmitBtn").textContent,
  );

  if (data.success) {
    closeModal("toggleServiceModal");
    currentPage = 1;
    updateTable(data.tableHtml);
    updateStats(data);
    renderPagination(data.page, data.totalPages, data.count, data.limit);
    updateResultCount(data.count, data.limit, data.page);
    showToast(data.message, "success");
  } else {
    showToast(data.message, "error");
  }
}

async function submitDeleteService() {
  setBtnLoading(
    "deleteServiceBtn",
    true,
    '<i class="fas fa-trash"></i> Delete',
  );
  const data = await ajax({
    action: "delete_service",
    service_id: document.getElementById("delete_service_id").value,
  });
  setBtnLoading(
    "deleteServiceBtn",
    false,
    '<i class="fas fa-trash"></i> Delete',
  );

  if (data.success) {
    closeModal("deleteServiceModal");
    currentPage = 1;
    updateTable(data.tableHtml);
    updateStats(data);
    renderPagination(data.page, data.totalPages, data.count, data.limit);
    updateResultCount(data.count, data.limit, data.page);
    showToast(data.message, "success");
  } else {
    showToast(data.message, "error");
  }
}

async function loadServiceWindowCheckboxes(
  officeSelectId,
  listId,
  groupId,
  selectedWindows = [],
) {
  const officeId = document.getElementById(officeSelectId).value;
  const list = document.getElementById(listId);
  const group = document.getElementById(groupId);

  list.innerHTML = "";
  if (!officeId) {
    group.style.display = "none";
    return;
  }

  const data = await ajax({
    action: "get_windows_for_office",
    office_id: officeId,
  });

  if (!data.success || data.window_count < 1) {
    group.style.display = "none";
    return;
  }

  for (let i = 1; i <= data.window_count; i++) {
    const checked = selectedWindows.includes(i) ? "checked" : "";
    list.innerHTML += `
      <label class="window-chip">
        <input type="checkbox" name="window" value="${i}" ${checked}>
        <span>
          <svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="0.5" y="1.5" width="10" height="7" rx="1" stroke="currentColor" stroke-width="1.2"/>
            <rect x="3" y="8.5" width="5" height="1" rx="0.5" fill="currentColor"/>
            <rect x="4.5" y="9.5" width="2" height="1" rx="0.5" fill="currentColor"/>
          </svg>
          ${i}
        </span>
      </label>`;
  }

  group.style.display = "";
}

function getCheckedWindows(listId) {
  return [
    ...document.querySelectorAll(`#${listId} input[name="window"]:checked`),
  ].map((cb) => parseInt(cb.value));
}
