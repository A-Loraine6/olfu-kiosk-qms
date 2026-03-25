function togglePwd(id, btn) {
  const f = document.getElementById(id),
    i = btn.querySelector("i");
  const show = f.type === "password";
  f.type = show ? "text" : "password";
  i.className = show ? "fas fa-eye-slash" : "fas fa-eye";
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
