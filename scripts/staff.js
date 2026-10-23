const AJAX_URL = "api/staff_actions.php";
const LOGS_API = "api/get_staff_logs.php";
let currentRequest = 0;

let currentPage = 1;
let currentLimit =
  typeof PAGINATION_LIMIT !== "undefined" ? PAGINATION_LIMIT : 10;
let currentTotal = typeof TOTAL_STAFF !== "undefined" ? TOTAL_STAFF : 0;
let currentTotalPages = Math.ceil(currentTotal / currentLimit) || 1;
let logsFilters = { search: "", date_from: "", date_to: "" };
let logsSearchDebounce;

document.addEventListener("DOMContentLoaded", () => {
  fetchFilteredRows();
  restrictFutureDates();
  if (document.getElementById("add_office").value) {
    loadWindowOptions("add_office", "add_window", "add_window_group");
  }
});

function restrictFutureDates() {
  const today = new Date().toISOString().split("T")[0]; // "YYYY-MM-DD"
  document.querySelectorAll('input[type="date"]').forEach((input) => {
    input.max = today;
  });
}

function linkDateRangeInputs(fromId, toId) {
  const fromEl = document.getElementById(fromId);
  const toEl = document.getElementById(toId);
  if (!fromEl || !toEl) return;

  const today = new Date().toISOString().split("T")[0];

  fromEl.addEventListener("change", () => {
    toEl.min = fromEl.value || "";
  });
  toEl.addEventListener("change", () => {
    fromEl.max = toEl.value || today;
  });
}

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
  if (data.totalStaff !== undefined)
    document.getElementById("stat-total").textContent = data.totalStaff;
  if (data.activeStaff !== undefined)
    document.getElementById("stat-active").textContent = data.activeStaff;
  if (data.inactiveStaff !== undefined)
    document.getElementById("stat-inactive").textContent = data.inactiveStaff;
}

function updateTable(html) {
  const tbody = document.getElementById("staffTableBody");
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
      : `Showing ${from}–${to} of ${total} staff member${total !== 1 ? "s" : ""}`;

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
  document.getElementById("searchClear").style.opacity = this.value ? "1" : "0";
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
    action: "get_staff_list",
    search: document.getElementById("searchInput").value,
    office: IS_DEPARTMENT_HEAD
      ? ""
      : document.getElementById("filterOffice").value,
    status: document.getElementById("filterStatus").value,
    role:   document.getElementById("filterRole").value,
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
    renderPagination(
      currentPage,
      currentTotalPages,
      currentTotal,
      currentLimit,
    );
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
  if (e.key === "Escape")
    document.querySelectorAll(".modal.show").forEach((m) => closeModal(m.id));
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

function setBtnLoading(btnId, loading, defaultHtml) {
  const btn = document.getElementById(btnId);
  if (!btn) return;
  btn.disabled = loading;
  btn.innerHTML = loading
    ? '<i class="fas fa-spinner fa-spin"></i> Please wait…'
    : defaultHtml;
}

function togglePassword(fieldId, btn) {
  const field = document.getElementById(fieldId);
  const icon = btn.querySelector("i");
  if (field.type === "password") {
    field.type = "text";
    icon.classList.replace("fa-eye", "fa-eye-slash");
  } else {
    field.type = "password";
    icon.classList.replace("fa-eye-slash", "fa-eye");
  }
}

async function openEditStaffModal(staffId) {
  const res = await fetch("api/get_staff.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "staff_id=" + encodeURIComponent(staffId) + "&csrf_token=" + encodeURIComponent(CSRF_TOKEN),
  });
  const data = await res.json();

  if (data.success) {
    const s = data.staff;
    document.getElementById("edit_staff_id").value = s.id;
    document.getElementById("edit_full_name").value = s.full_name;
    document.getElementById("edit_email").value = s.email;
    if (!IS_DEPARTMENT_HEAD) {
      document.getElementById("edit_office").value = s.office ?? "";
      document.getElementById("edit_role").value = s.role_id ?? "";
    }

    await loadWindowOptions(
      IS_DEPARTMENT_HEAD ? null : "edit_office",
      "edit_window",
      "edit_window_group",
      s.id,
      IS_DEPARTMENT_HEAD ? STAFF_OFFICE_NAME : null,
    );
    if (s.window_number)
      document.getElementById("edit_window").value = s.window_number;

    openModal("editStaffModal");
  } else {
    showToast(data.message || "Failed to load staff details.", "error");
  }
}

function openToggleModal(id, isActive) {
  document.getElementById("toggle_staff_id").value = id;
  const title = document.getElementById("toggleModalTitle");
  const body = document.getElementById("toggleModalBody");
  const btn = document.getElementById("toggleSubmitBtn");
  btn.dataset.isActive = isActive ? "1" : "0";
  if (isActive) {
    title.textContent = "Deactivate Staff";
    body.textContent =
      "Deactivate this staff member? They will no longer be able to access the system.";
    btn.textContent = "Deactivate";
    btn.className = "btn-warning";
  } else {
    title.textContent = "Activate Staff";
    body.textContent =
      "Activate this staff member? They will regain access to the system.";
    btn.textContent = "Activate";
    btn.className = "btn-primary";
  }
  openModal("toggleStaffModal");
}

function openResetPasswordModal(staffId) {
  document.getElementById("reset_staff_id").value = staffId;
  document.getElementById("new_password").value = "";
  openModal("resetPasswordModal");
}

function openDeleteModal(staffId) {
  document.getElementById("delete_staff_id").value = staffId;
  openModal("deleteStaffModal");
}

async function submitAddStaff() {
  const fullNameInput = document.getElementById("add_full_name");
  fullNameInput.value = fullNameInput.value.trim();
  if (!fullNameInput.checkValidity()) {
    showToast("Full name must be 3 to 100 characters and use only letters, spaces, periods, hyphens, or apostrophes.", "error");
    return;
  }

  setBtnLoading("addStaffBtn", true, '<i class="fas fa-plus"></i> Add Staff');
  const data = await ajax({
    action: "add_staff",
    full_name: document.getElementById("add_full_name").value,
    email: document.getElementById("add_email").value,
    password: document.getElementById("add_password").value,
    office: document.getElementById("add_office").value,
    window_number: document.getElementById("add_window").value,
    role_id: document.getElementById("add_role").value,
  });
  setBtnLoading("addStaffBtn", false, '<i class="fas fa-plus"></i> Add Staff');

  if (data.success) {
    closeModal("addStaffModal");
    ["add_full_name", "add_email", "add_password"].forEach(
      (id) => (document.getElementById(id).value = ""),
    );
    document.getElementById("add_office").selectedIndex = 0;
    await loadWindowOptions("add_office", "add_window", "add_window_group");

    currentPage = 1;
    updateStats(data);
    await fetchFilteredRows();
    showToast(data.message, "success");
  } else {
    showToast(data.message, "error");
  }
}

async function submitEditStaff() {
  const fullNameInput = document.getElementById("edit_full_name");
  fullNameInput.value = fullNameInput.value.trim();
  if (!fullNameInput.checkValidity()) {
    showToast("Full name must be 3 to 100 characters and use only letters, spaces, periods, hyphens, or apostrophes.", "error");
    return;
  }

  setBtnLoading(
    "editStaffBtn",
    true,
    '<i class="fas fa-save"></i> Save Changes',
  );
  const editData = {
    action: "edit_staff",
    staff_id: document.getElementById("edit_staff_id").value,
    full_name: document.getElementById("edit_full_name").value,
    email: document.getElementById("edit_email").value,
    window_number: document.getElementById("edit_window").value,
  };
  if (!IS_DEPARTMENT_HEAD) {
    editData.office = document.getElementById("edit_office").value;
    editData.role_id = document.getElementById("edit_role").value;
  }
  const data = await ajax(editData);
  setBtnLoading(
    "editStaffBtn",
    false,
    '<i class="fas fa-save"></i> Save Changes',
  );

  if (data.success) {
    closeModal("editStaffModal");
    currentPage = 1;
    updateStats(data);
    await fetchFilteredRows();
    showToast(data.message, "success");
  } else {
    showToast(data.message, "error");
  }
}

async function submitToggleStaff() {
  const btn = document.getElementById("toggleSubmitBtn");
  const label = btn.textContent;
  const isActive = btn.dataset.isActive === "1";
  setBtnLoading("toggleSubmitBtn", true, label);
  const data = await ajax({
    action: "toggle_staff",
    staff_id: document.getElementById("toggle_staff_id").value,
    is_active: isActive,
  });
  setBtnLoading("toggleSubmitBtn", false, label);

  if (data.success) {
    closeModal("toggleStaffModal");
    currentPage = 1;
    updateStats(data);
    await fetchFilteredRows();
    showToast(data.message, "success");
  } else {
    showToast(data.message, "error");
  }
}

async function submitResetPassword() {
  setBtnLoading(
    "resetPasswordBtn",
    true,
    '<i class="fas fa-key"></i> Reset Password',
  );
  const data = await ajax({
    action: "reset_password",
    staff_id: document.getElementById("reset_staff_id").value,
    new_password: document.getElementById("new_password").value,
  });
  setBtnLoading(
    "resetPasswordBtn",
    false,
    '<i class="fas fa-key"></i> Reset Password',
  );

  if (data.success) {
    closeModal("resetPasswordModal");
    showToast(data.message, "success");
  } else {
    showToast(data.message, "error");
  }
}

async function submitDeleteStaff() {
  setBtnLoading("deleteStaffBtn", true, '<i class="fas fa-trash"></i> Delete');
  const data = await ajax({
    action: "delete_staff",
    staff_id: document.getElementById("delete_staff_id").value,
  });
  setBtnLoading("deleteStaffBtn", false, '<i class="fas fa-trash"></i> Delete');

  if (data.success) {
    closeModal("deleteStaffModal");
    currentPage = 1;
    updateStats(data);
    await fetchFilteredRows();
    showToast(data.message, "success");
  } else {
    showToast(data.message, "error");
  }
}

async function loadWindowOptions(
  officeSelectId,
  windowSelectId,
  groupId,
  staffId = null,
  officeOverride = null,
) {
  const officeVal =
    officeOverride ??
    (officeSelectId ? document.getElementById(officeSelectId)?.value : "");
  const windowSel = document.getElementById(windowSelectId);
  const group = document.getElementById(groupId);

  windowSel.innerHTML = '<option value="">No window</option>';
  if (!officeVal) {
    group.style.display = "none";
    return;
  }

  const data = await ajax({
    action: "get_windows_for_office",
    office: officeVal,
    staff_id: staffId,
  });
  if (!data.success) {
    group.style.display = "none";
    showToast(data.message || "Failed to load window assignments.", "error");
    return;
  }
  if (data.window_count < 1) {
    group.style.display = "none";
    return;
  }

  const assignedWindows = data.assigned_windows || [];
  for (let i = 1; i <= data.window_count; i++) {
    const opt = document.createElement("option");
    opt.value = i;
    const assigned = assignedWindows.includes(i);
    opt.textContent = assigned ? `Window ${i} (assigned)` : `Window ${i}`;
    opt.disabled = assigned;
    windowSel.appendChild(opt);
  }
  group.style.display = "";
}

const LOG_META = {
  USER_LOGIN: { label: "User Login", cls: "log-neutral" },
  USER_LOGOUT: { label: "User Logout", cls: "log-neutral" },

  STAFF_ADD: { label: "Add Staff", cls: "log-success" },
  STAFF_EDIT: { label: "Edit Staff", cls: "log-warning" },
  STAFF_DELETE: { label: "Delete Staff", cls: "log-danger" },
  STAFF_ACTIVATE: { label: "Activate Staff", cls: "log-success" },
  STAFF_DEACTIVATE: { label: "Deactivate Staff", cls: "log-danger" },
  PASSWORD_RESET: { label: "Password Reset", cls: "log-warning" },

  ROLE_CHANGE: { label: "Role Change", cls: "log-warning" },
  OFFICE_ASSIGN: { label: "Office Assignment", cls: "log-info" },

  SERVICE_ADD: { label: "Add Service", cls: "log-success" },
  SERVICE_EDIT: { label: "Edit Service", cls: "log-warning" },
  SERVICE_DELETE: { label: "Delete Service", cls: "log-danger" },
  SERVICE_TOGGLE_STATUS: { label: "Toggle Service Status", cls: "log-info" },

  OFFICE_QUEUE_MODE_TOGGLE: { label: "Toggle Queue Mode", cls: "log-info" },

  UPDATE_KIOSK: { label: "Kiosk Updated", cls: "log-info" },
  SETTINGS_KIOSK_UPDATE: { label: "Update Kiosk Settings", cls: "log-info" },
  SETTINGS_SCHEDULE_UPDATE: {
    label: "Update Office Schedule",
    cls: "log-info",
  },
  SETTINGS_QUEUE_UPDATE: {
    label: "Update Queue Configuration",
    cls: "log-info",
  },

  PASSWORD_CHANGE: { label: "Password Changed", cls: "log-warning" },
  PROFILE_UPDATE: { label: "Profile Updated", cls: "log-info" },
};

function snakeToTitle(str) {
  return str
    .toLowerCase()
    .split("_")
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(" ");
}

function getLogMeta(type) {
  if (!type) return { label: "Activity", cls: "log-neutral" };
  return LOG_META[type] || { label: snakeToTitle(type), cls: "log-neutral" };
}

function formatLogTimestamp(ts) {
  if (!ts) return "";
  const d = new Date(ts.replace(" ", "T"));
  return d.toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
    hour12: true,
  });
}

function renderDiffBlock(oldJson, newJson) {
  let before = {},
    after = {};
  try {
    before = typeof oldJson === "string" ? JSON.parse(oldJson) : oldJson || {};
  } catch {}
  try {
    after = typeof newJson === "string" ? JSON.parse(newJson) : newJson || {};
  } catch {}

  const keys = new Set([...Object.keys(before), ...Object.keys(after)]);
  if (!keys.size) return "";

  const fmtKey = (k) =>
    k.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());

  const rows = [...keys]
    .filter((k) => String(before[k] ?? "") !== String(after[k] ?? ""))
    .map((k) => {
      const fmt = (v) =>
        v === null || v === undefined || v === "" ? "—" : String(v);
      return `<div class="log-diff-row">
                <span class="log-diff-key">${escapeHtml(fmtKey(k))}</span>
                <span class="log-diff-old">${escapeHtml(fmt(before[k]))}</span>
                <i class="fas fa-arrow-right log-diff-arrow"></i>
                <span class="log-diff-new">${escapeHtml(fmt(after[k]))}</span>
            </div>`;
    })
    .join("");

  return rows ? `<div class="log-diff">${rows}</div>` : "";
}

function buildLogsTimeline(logs) {
  return logs
    .map((log) => {
      const meta = getLogMeta(log.activity_type);
      const diff = renderDiffBlock(log.old_value, log.new_value);
      const desc = log.description
        ? `<div class="log-desc">${escapeHtml(log.description)}</div>`
        : "";

      return `<div class="log-item ${meta.cls}">
            <div class="log-left">
                <span class="log-dot"></span>
                <div class="log-main">
                    <span class="log-label">${meta.label}</span>
                    ${desc}
                    ${diff}
                </div>
            </div>
            <span class="log-ts">${formatLogTimestamp(log.timestamp)}</span>
        </div>`;
    })
    .join("");
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

let logsCurrentPage = 1;
let logsStaffId = null;
let feedbackCurrentPage = 1;
let feedbackStaffId = null;
let feedbackRatingFilter = null;

function renderFeedbackRatingChips(summary = {}) {
  const container = document.getElementById("feedbackRatingFilters");
  if (!container) return;

  const starOptions = [
    { value: 5, label: "5 stars" },
    { value: 4, label: "4 stars" },
    { value: 3, label: "3 stars" },
    { value: 2, label: "2 stars" },
    { value: 1, label: "1 star" },
  ].filter(({ value }) => Number(summary[String(value)] ?? 0) > 0);

  if (starOptions.length === 0) {
    container.innerHTML = "";
    container.style.display = "none";
    feedbackRatingFilter = null;
    return;
  }

  container.style.display = "";

  if (feedbackRatingFilter !== null && !starOptions.some((o) => o.value === feedbackRatingFilter)) {
    feedbackRatingFilter = null;
  }

  const options = [{ value: null, label: "All" }, ...starOptions];

  container.innerHTML = options
    .map(({ value, label }) => {
      const rating = value === null ? "all" : String(value);
      const count = summary[rating] ?? 0;
      const activeClass = feedbackRatingFilter === value ? "active" : "";
      const starLabel = value === null ? "All" : `${label}`;
      return `
        <button class="feedback-rating-chip ${activeClass}" type="button" data-rating="${value === null ? "all" : value}">
          <span>${starLabel}</span>
          <span class="feedback-rating-chip-count">${count}</span>
        </button>
      `;
    })
    .join("");

  container.querySelectorAll(".feedback-rating-chip").forEach((chip) => {
    chip.addEventListener("click", () => {
      const value = chip.dataset.rating === "all" ? null : Number(chip.dataset.rating);
      feedbackRatingFilter = value;
      feedbackCurrentPage = 1;
      fetchStaffFeedback();
    });
  });
}

async function openStaffLogsModal(staffId) {
  logsStaffId = staffId;
  logsCurrentPage = 1;
  logsFilters = { search: "", date_from: "", date_to: "" };
  document.getElementById("logsSearchInput").value = "";
  document.getElementById("logsDateFrom").value = "";
  document.getElementById("logsDateTo").value = "";
  document.getElementById("logsSearchClear").style.opacity = "0";
  restrictFutureDates();
  linkDateRangeInputs("logsDateFrom", "logsDateTo");
  openModal("staffLogsModal");
  await fetchStaffLogs();
}

document.getElementById("logsSearchInput")?.addEventListener("input", function () {
  document.getElementById("logsSearchClear").style.opacity = this.value ? "1" : "0";
  clearTimeout(logsSearchDebounce);
  logsSearchDebounce = setTimeout(applyLogsFilters, 280);
});

function applyLogsFilters() {
  logsFilters.search = document.getElementById("logsSearchInput").value.trim();
  logsFilters.date_from = document.getElementById("logsDateFrom").value;
  logsFilters.date_to = document.getElementById("logsDateTo").value;
  logsCurrentPage = 1;
  fetchStaffLogs();
}

function clearLogsSearch() {
  document.getElementById("logsSearchInput").value = "";
  document.getElementById("logsSearchClear").style.opacity = "0";
  applyLogsFilters();
}

function clearLogsFilters() {
  document.getElementById("logsSearchInput").value = "";
  document.getElementById("logsDateFrom").value = "";
  document.getElementById("logsDateTo").value = "";
  document.getElementById("logsSearchClear").style.opacity = "0";
  logsFilters = { search: "", date_from: "", date_to: "" };
  logsCurrentPage = 1;
  fetchStaffLogs();
}

async function openStaffFeedbackModal(staffId) {
  if (!CAN_MANAGE || !CAN_DELETE) {
    showToast("Only administrators can view feedback.", "error");
    return;
  }

  feedbackStaffId = staffId;
  feedbackCurrentPage = 1;
  feedbackRatingFilter = null;

  const loading = document.getElementById("feedbackLoading");
  const empty = document.getElementById("feedbackEmpty");
  const list = document.getElementById("feedbackList");
  const pagBar = document.getElementById("feedbackPagination");
  if (loading) loading.style.display = "flex";
  if (empty) empty.style.display = "none";
  if (list) list.innerHTML = "";
  if (pagBar) pagBar.style.display = "none";
  openModal("staffFeedbackModal");

  await fetchStaffFeedback();
}

async function fetchStaffFeedback() {
  const loading = document.getElementById("feedbackLoading");
  const empty = document.getElementById("feedbackEmpty");
  const list = document.getElementById("feedbackList");
  const pagBar = document.getElementById("feedbackPagination");

  if (loading) loading.style.display = "flex";
  if (empty) empty.style.display = "none";
  if (list) list.innerHTML = "";
  if (pagBar) pagBar.style.display = "none";

  try {
    const res = await fetch(AJAX_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "get_staff_feedback",
        staff_id: feedbackStaffId,
        page: feedbackCurrentPage,
        limit: 5,
        rating: feedbackRatingFilter,
        csrf_token: CSRF_TOKEN,
      }),
    });
    const data = await res.json();

    if (!data.success) {
      showToast(data.message || "Failed to load staff feedback.", "error");
      return;
    }

    renderFeedbackRatingChips(data.rating_summary || {});

    const staff = data.staff || {};
    const badge = document.getElementById("feedbackTotalBadge");
    if (badge) badge.textContent = `${Number(data.total || 0)} feedback`;

    const avatar = document.getElementById("feedbackStaffAvatar");
    const name = document.getElementById("feedbackStaffName");
    const sub = document.getElementById("feedbackStaffSub");
    if (avatar) avatar.textContent = staff.full_name ? staff.full_name.charAt(0).toUpperCase() : "?";
    if (name) name.textContent = staff.full_name || "Unknown staff";
    if (sub) {
      const parts = [staff.role_name ? staff.role_name.charAt(0).toUpperCase() + staff.role_name.slice(1) : null, staff.office_name].filter(Boolean);
      sub.textContent = parts.join(" · ");
    }

    if (!data.feedback || !data.feedback.length) {
      if (loading) loading.style.display = "none";
      if (empty) empty.style.display = "flex";
      return;
    }

    list.innerHTML = data.feedback
      .map((item) => {
        const stars = Array.from({ length: 5 }, (_, idx) => `<span class="feedback-star-item ${idx < Number(item.rating) ? "filled" : ""}">★</span>`).join("");
        const comment = item.comment ? escapeHtml(item.comment) : "No additional comment provided.";

        return `
          <div class="feedback-item-card">
            <div class="feedback-item-header">
              <div class="feedback-item-stars">${stars}</div>
              <span class="feedback-item-date">${formatFeedbackDate(item.created_at)}</span>
            </div>
            <div class="feedback-item-comment">${comment}</div>
          </div>
        `;
      })
      .join("");

    if (data.totalPages > 1 || data.total > 0) {
      renderFeedbackPagination(data.page, data.totalPages, data.total, data.limit);
      if (pagBar) pagBar.style.display = "flex";
    }

    if (loading) loading.style.display = "none";
  } catch (e) {
    if (loading) loading.style.display = "none";
    showToast("Failed to load feedback.", "error");
  }
}

function renderFeedbackPagination(page, totalPages, total, limit) {
  const infoEl = document.getElementById("feedbackPageInfo");
  const ctrlEl = document.getElementById("feedbackPageControls");
  if (!infoEl || !ctrlEl) return;

  const from = total === 0 ? 0 : (page - 1) * limit + 1;
  const to = Math.min(page * limit, total);
  infoEl.textContent = total === 0 ? "No feedback" : `Showing ${from}–${to} of ${total}`;
  ctrlEl.innerHTML = "";

  const navBtn = (icon, title, disabled, onClick) => {
    const b = document.createElement("button");
    b.className = "page-nav-btn";
    b.title = title;
    b.disabled = disabled;
    b.innerHTML = `<i class="fas ${icon}"></i>`;
    if (!disabled) b.addEventListener("click", onClick);
    return b;
  };

  const pageBtn = (label, active, onClick) => {
    const b = document.createElement("button");
    b.className = "page-nav-btn" + (active ? " page-nav-active" : "");
    b.textContent = label;
    if (onClick) b.addEventListener("click", onClick);
    return b;
  };

  ctrlEl.appendChild(navBtn("fa-angle-double-left", "First", page <= 1, () => {
    feedbackCurrentPage = 1;
    fetchStaffFeedback();
  }));
  ctrlEl.appendChild(navBtn("fa-angle-left", "Previous", page <= 1, () => {
    feedbackCurrentPage = Math.max(1, page - 1);
    fetchStaffFeedback();
  }));

  const range = [];
  for (let p = 1; p <= totalPages; p++) {
    range.push(p);
  }
  range.forEach((p) => {
    ctrlEl.appendChild(pageBtn(p, p === page, () => {
      feedbackCurrentPage = p;
      fetchStaffFeedback();
    }));
  });

  ctrlEl.appendChild(navBtn("fa-angle-right", "Next", page >= totalPages, () => {
    feedbackCurrentPage = Math.min(totalPages, page + 1);
    fetchStaffFeedback();
  }));
  ctrlEl.appendChild(navBtn("fa-angle-double-right", "Last", page >= totalPages, () => {
    feedbackCurrentPage = totalPages;
    fetchStaffFeedback();
  }));
}

function formatFeedbackDate(dateString) {
  if (!dateString) return "—";
  const d = new Date(dateString.replace(" ", "T"));
  if (Number.isNaN(d.getTime())) return dateString;
  return d.toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
    hour12: true,
  });
}

async function fetchStaffLogs() {
  const loading = document.getElementById("logsLoading");
  const timeline = document.getElementById("logsTimeline");
  const empty = document.getElementById("logsEmpty");
  const pagBar = document.getElementById("logsPagination");

  loading.style.display = "flex";
  timeline.innerHTML = "";
  empty.style.display = "none";
  pagBar.style.display = "none";

  const res = await fetch(LOGS_API, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      staff_id: logsStaffId,
      page: logsCurrentPage,
      search: logsFilters.search,
      date_from: logsFilters.date_from,
      date_to: logsFilters.date_to,
      csrf_token: CSRF_TOKEN,
    }),
  });
  const data = await res.json();
  loading.style.display = "none";

  if (!data.success) {
    showToast(data.message || "Failed to load logs.", "error");
    return;
  }

  const s = data.staff;
  document.getElementById("logsStaffAvatar").textContent = s.full_name
    ? s.full_name.charAt(0).toUpperCase()
    : "?";
  document.getElementById("logsStaffName").textContent = s.full_name || "—";
  document.getElementById("logsStaffSub").textContent = [
    s.role_name
      ? s.role_name.charAt(0).toUpperCase() + s.role_name.slice(1)
      : null,
    s.office_name,
  ]
    .filter(Boolean)
    .join(" · ");
  const badge = document.getElementById("logsTotalBadge");
  badge.textContent = data.count + " log" + (data.count !== 1 ? "s" : "");
  badge.style.display = "inline-flex";

  if (!data.logs.length) {
    empty.style.display = "flex";
    return;
  }

  timeline.innerHTML = buildLogsTimeline(data.logs);

  if (data.totalPages > 1) {
    renderLogsPagination(data.page, data.totalPages, data.count, data.limit);
    pagBar.style.display = "flex";
  }
}

function renderLogsPagination(page, totalPages, total, limit) {
  const infoEl = document.getElementById("logsPageInfo");
  const ctrlEl = document.getElementById("logsPageControls");

  infoEl.textContent = `${(page - 1) * limit + 1}–${Math.min(page * limit, total)} of ${total}`;
  ctrlEl.innerHTML = "";

  const navBtn = (icon, title, disabled, onClick) => {
    const b = document.createElement("button");
    b.className = "page-nav-btn";
    b.title = title;
    b.disabled = disabled;
    b.innerHTML = `<i class="fas ${icon}"></i>`;
    if (!disabled) b.addEventListener("click", onClick);
    return b;
  };
  const pageBtn = (label, active, onClick) => {
    const b = document.createElement("button");
    b.className = "page-nav-btn" + (active ? " page-nav-active" : "");
    b.textContent = label;
    if (onClick) b.addEventListener("click", onClick);
    return b;
  };

  ctrlEl.appendChild(
    navBtn("fa-angle-double-left", "First", page <= 1, () => goLogsPage(1)),
  );
  ctrlEl.appendChild(
    navBtn("fa-angle-left", "Previous", page <= 1, () => goLogsPage(page - 1)),
  );
  buildPageList(page, totalPages).forEach((p) => {
    if (p === "…") {
      const e = document.createElement("span");
      e.className = "page-nav-btn page-ellipsis";
      e.textContent = "…";
      e.disabled = true;
      ctrlEl.appendChild(e);
    } else {
      ctrlEl.appendChild(
        pageBtn(p, p === page, p !== page ? () => goLogsPage(p) : null),
      );
    }
  });
  ctrlEl.appendChild(
    navBtn("fa-angle-right", "Next", page >= totalPages, () =>
      goLogsPage(page + 1),
    ),
  );
  ctrlEl.appendChild(
    navBtn("fa-angle-double-right", "Last", page >= totalPages, () =>
      goLogsPage(totalPages),
    ),
  );
}

async function goLogsPage(page) {
  logsCurrentPage = page;
  document.getElementById("logsBody").scrollTop = 0;
  await fetchStaffLogs();
}