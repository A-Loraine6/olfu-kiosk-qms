const AJAX_URL = "api/staff_actions.php";

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

let searchDebounce;

function applyFilters() {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(fetchFilteredRows, 250);
}

document.getElementById("searchInput").addEventListener("input", function () {
  document.getElementById("searchClear").style.opacity = this.value ? "1" : "0";
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(fetchFilteredRows, 280);
});

function clearSearch() {
  document.getElementById("searchInput").value = "";
  document.getElementById("searchClear").style.opacity = "0";
  fetchFilteredRows();
}

let currentRequest = 0;

async function fetchFilteredRows() {
  const requestId = ++currentRequest;

  setTableLoading(true);

  const data = await ajax({
    action: "get_staff_list",
    search: document.getElementById("searchInput").value,
    office: document.getElementById("filterOffice").value,
    status: document.getElementById("filterStatus").value,
  });

  if (requestId !== currentRequest) return;

  setTableLoading(false);

  if (data.success) updateTable(data.html);
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
  menu.style.minWidth = "170px";
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
    body: "staff_id=" + encodeURIComponent(staffId),
  });
  const data = await res.json();

  if (data.success) {
    const s = data.staff;
    document.getElementById("edit_staff_id").value = s.id;
    document.getElementById("edit_full_name").value = s.full_name;
    document.getElementById("edit_email").value = s.email;
    document.getElementById("edit_office").value = s.office ?? "";

    await loadWindowOptions("edit_office", "edit_window", "edit_window_group");
    if (s.window_number) {
      document.getElementById("edit_window").value = s.window_number;
    }
    
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
  setBtnLoading("addStaffBtn", true, '<i class="fas fa-plus"></i> Add Staff');
  const data = await ajax({
    action: "add_staff",
    full_name: document.getElementById("add_full_name").value,
    email: document.getElementById("add_email").value,
    password: document.getElementById("add_password").value,
    office: document.getElementById("add_office").value,
    window_number: document.getElementById("add_window").value,
  });
  setBtnLoading("addStaffBtn", false, '<i class="fas fa-plus"></i> Add Staff');

  if (data.success) {
    closeModal("addStaffModal");
    ["add_full_name", "add_email", "add_password"].forEach(
      (id) => (document.getElementById(id).value = ""),
    );
    document.getElementById("add_office").selectedIndex = 0;
    document.getElementById("add_window").innerHTML =
      '<option value="" disabled selected>Select window</option>';
    document.getElementById("add_window_group").style.display = "none";

    updateTable(data.tableHtml);
    updateStats(data);
    showToast(data.message, "success");
  } else {
    showToast(data.message, "error");
  }
}

async function submitEditStaff() {
  setBtnLoading(
    "editStaffBtn",
    true,
    '<i class="fas fa-save"></i> Save Changes',
  );
  const data = await ajax({
    action: "edit_staff",
    staff_id: document.getElementById("edit_staff_id").value,
    full_name: document.getElementById("edit_full_name").value,
    email: document.getElementById("edit_email").value,
    office: document.getElementById("edit_office").value,
    window_number: document.getElementById("edit_window").value,
  });
  setBtnLoading(
    "editStaffBtn",
    false,
    '<i class="fas fa-save"></i> Save Changes',
  );

  if (data.success) {
    closeModal("editStaffModal");
    updateTable(data.tableHtml);
    updateStats(data);
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
    updateTable(data.tableHtml);
    updateStats(data);
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
    updateTable(data.tableHtml);
    updateStats(data);
    showToast(data.message, "success");
  } else {
    showToast(data.message, "error");
  }
}

async function loadWindowOptions(officeSelectId, windowSelectId, groupId) {
  const officeVal = document.getElementById(officeSelectId).value;
  const windowSel = document.getElementById(windowSelectId);
  const group = document.getElementById(groupId);

  windowSel.innerHTML =
    '<option value="" disabled selected>Select window</option>';

  if (!officeVal) {
    group.style.display = "none";
    return;
  }

  const data = await ajax({
    action: "get_windows_for_office",
    office: officeVal,
  });

  if (!data.success || data.window_count < 1) {
    group.style.display = "none";
    return;
  }

  for (let i = 1; i <= data.window_count; i++) {
    const opt = document.createElement("option");
    opt.value = i;
    opt.textContent = `Window ${i}`;
    windowSel.appendChild(opt);
  }

  group.style.display = "";
}
