const AJAX_URL = "api/service_actions.php";
let currentRequest = 0;

async function ajax(payload) {
  const res = await fetch(AJAX_URL, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      ...payload,
      csrf_token: CSRF_TOKEN,
    }),
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

let searchDebounce;

function applyFilters() {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(fetchFilteredRows, 250);
}

document.getElementById("searchInput").addEventListener("input", function () {
  const clearBtn = document.getElementById("searchClear");
  clearBtn.style.opacity = this.value ? "1" : "0";
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(fetchFilteredRows, 280);
});

function clearSearch() {
  document.getElementById("searchInput").value = "";
  document.getElementById("searchClear").style.opacity = "0";
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
  });

  if (requestId !== currentRequest) return;

  setTableLoading(false);

  if (data.success) {
    updateTable(data.html);
  }
}

async function toggleQueueMode(officeId, btn) {
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

  const data = await ajax({
    action: "toggle_queue_mode",
    office_id: officeId,
  });

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
  requestAnimationFrame(() => {
    modal.classList.add("show");
  });
}

function closeModal(id) {
  const modal = document.getElementById(id);
  modal.classList.remove("show");
  setTimeout(() => {
    modal.style.display = "none";
  }, 300);
}

document.querySelectorAll(".modal").forEach((m) => {
  m.addEventListener("click", (e) => {
    if (e.target === m) closeModal(m.id);
  });
});
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    document.querySelectorAll(".modal.show").forEach((m) => {
      closeModal(m.id);
    });
  }
});

function positionDropdown(toggle) {
  const menu = toggle.closest(".dropdown").querySelector(".dropdown-menu");
  const rect = toggle.getBoundingClientRect();
  const items = menu.querySelectorAll("button").length;
  const menuH = items * 41 + 16;
  const spaceBelow = window.innerHeight - rect.bottom;
  const openUp = spaceBelow < menuH;

  menu.style.position = "fixed";
  menu.style.right = window.innerWidth - rect.right + "px";
  menu.style.minWidth = "160px";
  menu.style.top = openUp ? "auto" : rect.bottom + 4 + "px";
  menu.style.bottom = openUp
    ? window.innerHeight - rect.top + 4 + "px"
    : "auto";
}

function closeAllDropdowns() {
  document.querySelectorAll(".dropdown.active").forEach((d) => {
    d.classList.remove("active");
  });
}

document.addEventListener("click", function (e) {
  const toggle = e.target.closest(".dropdown-toggle");
  if (toggle) {
    const dropdown = toggle.closest(".dropdown");
    const isAlreadyOn = dropdown.classList.contains("active");
    closeAllDropdowns();
    if (!isAlreadyOn) {
      dropdown.classList.add("active");
    }
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

    updateTable(data.tableHtml);
    updateStats(data);
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
    updateTable(data.tableHtml);
    updateStats(data);
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
    updateTable(data.tableHtml);
    updateStats(data);
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
    updateTable(data.tableHtml);
    updateStats(data);
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
        </label>
    `;
  }

  group.style.display = "";
}

function getCheckedWindows(listId) {
  return [
    ...document.querySelectorAll(`#${listId} input[name="window"]:checked`),
  ].map((cb) => parseInt(cb.value));
}
