function togglePwd(id, btn) {
  const f = document.getElementById(id),
    i = btn.querySelector("i");
  const show = f.type === "password";
  f.type = show ? "text" : "password";
  i.className = show ? "fas fa-eye-slash" : "fas fa-eye";
}

function showToast(message, type = "success") {
  const container = document.getElementById("toast-container");
  if (!container || !message) return;

  const toast = document.createElement("div");
  const icon = type === "success" ? "fa-check-circle" : "fa-exclamation-circle";
  toast.className = `toast toast-${type}`;

  const iconElement = document.createElement("i");
  iconElement.className = `fas ${icon}`;
  const messageElement = document.createElement("span");
  messageElement.textContent = message;
  const closeButton = document.createElement("button");
  closeButton.className = "toast-close";
  closeButton.type = "button";
  closeButton.innerHTML = "&times;";
  closeButton.addEventListener("click", () => toast.remove());

  toast.append(iconElement, messageElement, closeButton);
  container.appendChild(toast);
  requestAnimationFrame(() => toast.classList.add("show"));

  setTimeout(() => {
    toast.style.transition = "opacity 0.4s, transform 0.4s";
    toast.style.opacity = "0";
    toast.style.transform = "translateX(100%)";
    setTimeout(() => toast.remove(), 400);
  }, 4000);
}

if (window.loginToast) {
  showToast(window.loginToast.message, window.loginToast.type);
}

(function () {
  const f = document.getElementById("password");
  const sw = document.getElementById("sw");
  if (!f || !sw) return;
  const fill = document.getElementById("sf"),
    lbl = document.getElementById("sl");
  const levels = [
    {
      pct: "20%",
      c: "#d32f2f",
      t: "Very weak",
    },
    {
      pct: "40%",
      c: "#f57c00",
      t: "Weak",
    },
    {
      pct: "60%",
      c: "#fbc02d",
      t: "Fair",
    },
    {
      pct: "80%",
      c: "#388e3c",
      t: "Strong",
    },
    {
      pct: "100%",
      c: "#1b5e20",
      t: "Very strong",
    },
  ];
  f.addEventListener("input", function () {
    const v = this.value;
    if (!v) {
      sw.classList.remove("visible");
      return;
    }
    sw.classList.add("visible");
    let s = 0;
    if (v.length >= 8) s++;
    if (v.length >= 12) s++;
    if (/[A-Z]/.test(v)) s++;
    if (/[0-9]/.test(v)) s++;
    if (/[\W_]/.test(v)) s++;
    const l = levels[Math.min(s - 1, 4)] || levels[0];
    fill.style.width = l.pct;
    fill.style.background = l.c;
    lbl.textContent = l.t;
    lbl.style.color = l.c;
  });
})();
