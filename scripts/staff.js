document.querySelectorAll(".dropdown-toggle").forEach((button) => {
  button.addEventListener("click", function (e) {
    e.stopPropagation();

    const dropdown = this.closest(".dropdown");

    document.querySelectorAll(".dropdown").forEach((d) => {
      if (d !== dropdown) d.classList.remove("active");
    });

    dropdown.classList.toggle("active");
  });
});

document.addEventListener("click", function () {
  document.querySelectorAll(".dropdown").forEach((d) => {
    d.classList.remove("active");
  });
});

function openAddStaffModal() {
  document.getElementById("addStaffModal").classList.add("show");
}

async function openEditStaffModal(staffId) {
  const xhr = new XMLHttpRequest();
  xhr.open("POST", "api/get_staff.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  xhr.onload = function () {
    if (this.status === 200) {
      try {
        const response = JSON.parse(this.responseText);
        if (response.success) {
          const s = response.staff;
          document.getElementById("edit_staff_id").value = s.id;
          document.getElementById("edit_full_name").value = s.full_name;
          document.getElementById("edit_email").value = s.email;
          document.getElementById("edit_office").value = s.office ?? "";
          document.getElementById("editStaffModal").classList.add("show");
        } else {
          alert(response.message || "Failed to load staff details.");
        }
      } catch (e) {
        console.error("Error parsing response:", e);
      }
    }
  };

  xhr.send("staff_id=" + encodeURIComponent(staffId));
}

function openResetPasswordModal(staffId) {
  document.getElementById("reset_staff_id").value = staffId;
  document.getElementById("resetPasswordModal").classList.add("show");
}

function closeModal(modalId) {
  document.getElementById(modalId).classList.remove("show");
}

function togglePassword(fieldId) {
  const field = document.getElementById(fieldId);
  const button = event.target.closest(".btn-toggle-password");
  const icon = button.querySelector("i");

  if (field.type === "password") {
    field.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    field.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  }
}

function openDeleteModal(staffId) {
  document.getElementById("delete_staff_id").value = staffId;
  document.getElementById("deleteStaffModal").classList.add("show");
}

function openDeactivateModal(staffId) {
  document.getElementById("deactivate_staff_id").value = staffId;
  document.getElementById("deactivateStaffModal").classList.add("show");
}

function confirmActivate(staffId) {
  if (confirm("Activate this staff member?")) {
    const form = document.createElement("form");
    form.method = "POST";
    form.innerHTML = `
                    <input type="hidden" name="action" value="activate_staff">
                    <input type="hidden" name="staff_id" value="${staffId}">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                `;
    document.body.appendChild(form);
    form.submit();
  }
}

document.addEventListener("click", function (event) {
  document.querySelectorAll(".dropdown").forEach((d) => {
    if (!d.contains(event.target)) {
      d.classList.remove("active");
    }
  });

  document.querySelectorAll(".modal.show").forEach((modal) => {
    if (event.target === modal) {
      modal.classList.remove("show");
    }
  });
});
