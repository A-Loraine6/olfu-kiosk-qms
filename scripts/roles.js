const AJAX_URL = "api/role_actions.php";

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

function setBtnLoading(btnId, loading, defaultHtml) {
  const btn = document.getElementById(btnId);
  if (!btn) return;
  btn.disabled = loading;
  btn.innerHTML = loading
    ? '<i class="fas fa-spinner fa-spin"></i> Please wait…'
    : defaultHtml;
}

async function submitAddRole() {
  setBtnLoading("addRoleBtn", true, '<i class="fas fa-plus"></i> Add Role');
  const data = await ajax({
    action: "add_role",
    name: document.getElementById("add_role_name").value,
  });
  setBtnLoading("addRoleBtn", false, '<i class="fas fa-plus"></i> Add Role');

  if (data.success) {
    closeModal("addRoleModal");
    showToast(data.message, "success");
    setTimeout(() => location.reload(), 800);
  } else {
    showToast(data.message, "error");
  }
}

function openEditRoleModal(role) {
  document.getElementById("edit_role_id").value = role.id;
  document.getElementById("edit_role_name").value = role.name;
  openModal("editRoleModal");
}

async function submitEditRole() {
  setBtnLoading(
    "editRoleBtn",
    true,
    '<i class="fas fa-save"></i> Save Changes',
  );
  const data = await ajax({
    action: "edit_role",
    id: document.getElementById("edit_role_id").value,
    name: document.getElementById("edit_role_name").value,
  });
  setBtnLoading(
    "editRoleBtn",
    false,
    '<i class="fas fa-save"></i> Save Changes',
  );

  if (data.success) {
    closeModal("editRoleModal");
    showToast(data.message, "success");
    setTimeout(() => location.reload(), 800);
  } else {
    showToast(data.message, "error");
  }
}

function openDeleteRoleModal(id) {
  document.getElementById("delete_role_id").value = id;
  openModal("deleteRoleModal");
}

async function submitDeleteRole() {
  setBtnLoading("deleteRoleBtn", true, '<i class="fas fa-trash"></i> Delete');
  const data = await ajax({
    action: "delete_role",
    id: document.getElementById("delete_role_id").value,
  });
  setBtnLoading("deleteRoleBtn", false, '<i class="fas fa-trash"></i> Delete');

  if (data.success) {
    closeModal("deleteRoleModal");
    showToast(data.message, "success");
    setTimeout(() => location.reload(), 800);
  } else {
    showToast(data.message, "error");
  }
}

async function togglePermission(checkbox) {
  const roleId = checkbox.dataset.role;
  const permKey = checkbox.dataset.perm;
  const enable = checkbox.checked;

  const data = await ajax({
    action: "toggle_permission",
    role_id: roleId,
    perm_key: permKey,
    enable: enable,
  });

  if (data.success) {
    if (data.affected && data.affected.length > 1) {
      data.affected.forEach((key) => {
        if (key === permKey) return;
        const el = document.querySelector(
          `input[data-role="${roleId}"][data-perm="${key}"]`,
        );
        if (el) el.checked = enable;
      });
    }
    showToast(
      enable ? "Permission granted." : "Permission removed.",
      "success",
    );
  } else {
    checkbox.checked = !enable;
    showToast(data.message, "error");
  }
}
