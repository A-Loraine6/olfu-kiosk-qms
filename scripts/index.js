let state = {
  screen: 1,
  office: null,
  service: null,
  history: [1],
};

function updateClocks() {
  const now = new Date();
  const time = now.toLocaleTimeString("en-PH", {
    hour: "numeric",
    minute: "2-digit",
    hour12: true,
    timeZone: "Asia/Manila",
  });
  const date = now.toLocaleDateString("en-PH", {
    weekday: "long",
    month: "long",
    day: "numeric",
    year: "numeric",
    timeZone: "Asia/Manila",
  });
  document.getElementById("topTime").textContent = time;
  document.getElementById("topDate").textContent = date;
  document.getElementById("idleTime").textContent = time;
  document.getElementById("idleDate").textContent = date;
}
updateClocks();
setInterval(updateClocks, 1000);

function showScreen(n) {
  const cur = document.getElementById("screen" + state.screen);
  const nxt = document.getElementById("screen" + n);

  cur.classList.remove("active");
  cur.classList.add("exit");
  setTimeout(() => cur.classList.remove("exit"), 250);

  nxt.classList.add("active");
  const body = nxt.querySelector(".screen-body");
  if (body) body.scrollTop = 0;

  state.screen = n;
  resetIdle();
}

function goBack() {
  state.history.pop();
  const prev = state.history[state.history.length - 1] || 1;
  showScreen(prev);
}

const ICONS = {
  REG: "fa-scroll",
  ACC: "fa-coins",
  ADM: "fa-user-graduate",
};

function renderOffices() {
  const grid = document.getElementById("officeGrid");
  grid.innerHTML = "";
  OFFICES.forEach((o) => {
    const w = o.waiting;
    const icon = ICONS[o.code] || "fa-building";
    const btn = document.createElement("button");
    btn.className = "office-card";
    btn.innerHTML = `
            <div class="office-icon"><i class="fas ${icon}"></i></div>
            <div class="office-name">${esc(o.name)}</div>
            <div class="office-badge ${w > 0 ? "badge-active" : "badge-empty"}">
                ${w > 0 ? '<span class="pulse"></span>' + w + " waiting" : '<i class="fas fa-check" style="font-size:10px"></i> No queue'}
            </div>`;
    btn.addEventListener("click", () => selectOffice(o));
    grid.appendChild(btn);
  });
}

function selectOffice(o) {
  state.office = o;
  state.service = null;
  state.history = [1];

  if (o.mode === "service-based" && o.services && o.services.length) {
    renderServices(o);
    state.history.push(2);
    showScreen(2);
  } else {
    prepareConfirm();
    state.history.push(3);
    showScreen(3);
  }
}

function renderServices(o) {
  document.getElementById("s2OfficeName").textContent = o.name;
  const grid = document.getElementById("serviceGrid");
  grid.innerHTML = "";

  o.services.forEach((s) => {
    const btn = document.createElement("button");
    btn.className = "service-card";
    btn.innerHTML = `
            <div class="svc-name">${esc(s.name)}</div>
            ${s.description ? `<div class="svc-desc">${esc(s.description)}</div>` : ""}`;
    btn.addEventListener("click", () => selectService(s));
    grid.appendChild(btn);
  });
}

function selectService(s) {
  state.service = s;
  prepareConfirm();
  state.history.push(3);
  showScreen(3);
}

function prepareConfirm() {
  const o = state.office,
    s = state.service;
  document.getElementById("s3OfficeName").textContent = o.name;
  document.getElementById("s3ServiceName").textContent = s
    ? s.name
    : "General Queue";
  document.getElementById("s3QueuePreview").textContent = "—";

  fetch("api/preview_ticket.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `office_id=${o.id}`,
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        document.getElementById("s3QueuePreview").textContent =
          data.preview_ticket;
      }
    })
    .catch(() => {
      document.getElementById("s3QueuePreview").textContent = "—";
    });

  const est = o.waiting * 5;
  document.getElementById("s3WaitEstimate").innerHTML =
    o.waiting === 0
      ? "<strong>You're next!</strong> No wait time."
      : `Estimated wait: <strong>~${est} min</strong> (${o.waiting} ahead)`;
}

function confirmQueue() {
  const btn = document.getElementById("confirmBtn");
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

  const o = state.office,
    s = state.service;

  const apiUrl = "api/issue_ticket.php";

  console.log("[v0] Initiating fetch to:", apiUrl);
  console.log(
    "[v0] Payload - office_id:",
    o.id,
    "service_name:",
    s ? s.name : "",
  );

  fetch(apiUrl, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `office_id=${o.id}&service_name=${encodeURIComponent(s ? s.name : "")}`,
  })
    .then((r) => {
      console.log("[v0] Response received:", r.status, r.statusText);
      if (!r.ok) {
        throw new Error(`HTTP ${r.status}: ${r.statusText}`);
      }
      return r.json();
    })
    .then((data) => {
      console.log("[v0] Response data:", data);
      btn.disabled = false;
      btn.innerHTML =
        '<i class="fas fa-check-circle"></i> Confirm &amp; Get Ticket';
      if (data.success) {
        const oo = OFFICES.find((x) => x.id == o.id);
        if (oo) oo.waiting = data.waiting_count ?? oo.waiting + 1;
        showTicket(data);
      } else {
        alert(data.message || "Could not issue ticket. Please try again.");
      }
    })
    .catch((err) => {
      console.error("[v0] Fetch error:", err);
      btn.disabled = false;
      btn.innerHTML =
        '<i class="fas fa-check-circle"></i> Confirm &amp; Get Ticket';
      alert("Network error: " + err.message);
    });
}

const TOTAL_CD = 15;
let cdTimer = null,
  cdSecs = TOTAL_CD;

function showTicket(data) {
  const o = state.office,
    s = state.service;
  document.getElementById("s4OfficeName").textContent = o.name;
  document.getElementById("s4ServiceName").textContent = s
    ? s.name
    : "General Queue";
  document.getElementById("s4TicketNumber").textContent = data.ticket_number;
  document.getElementById("s4PeopleAhead").textContent =
    data.people_ahead ?? "—";

  const mins = (data.people_ahead ?? 0) * 5;
  document.getElementById("s4WaitTime").textContent =
    mins === 0 ? "Now" : `~${mins}m`;

  document.getElementById("qrCode").innerHTML = "";
  new QRCode(document.getElementById("qrCode"), {
    text: JSON.stringify({
      ticket: data.ticket_number,
      office: o.name,
      service: s ? s.name : "",
      issued: new Date().toISOString(),
    }),
    width: 160,
    height: 160,
    colorDark: "#006633",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.M,
  });

  state.history.push(4);
  showScreen(4);
  startCountdown();
}

function startCountdown() {
  clearInterval(cdTimer);
  cdSecs = TOTAL_CD;
  renderCountdown();
  cdTimer = setInterval(() => {
    cdSecs--;
    renderCountdown();
    if (cdSecs <= 0) {
      clearInterval(cdTimer);
      resetKiosk();
    }
  }, 1000);
}

function renderCountdown() {
  document.getElementById("countdownNum").textContent = cdSecs;
  document.getElementById("countdownBar").style.width =
    (cdSecs / TOTAL_CD) * 100 + "%";
}

function extendTime() {
  cdSecs = TOTAL_CD;
  renderCountdown();
}

function resetKiosk() {
  clearInterval(cdTimer);
  for (let i = 1; i <= 4; i++) {
    const s = document.getElementById("screen" + i);
    s.classList.remove("active", "exit");
    s.style.cssText = "";
  }
  document.getElementById("screen1").classList.add("active");
  state = {
    screen: 1,
    office: null,
    service: null,
    history: [1],
  };
  renderOffices();
  resetIdle();
}

let idleTimer = null;
const IDLE_MS = 60000;

function resetIdle() {
  clearTimeout(idleTimer);
  document.getElementById("idleOverlay").classList.remove("show");
  idleTimer = setTimeout(() => {
    if (state.screen !== 4) {
      document.getElementById("idleOverlay").classList.add("show");
      resetKiosk();
    }
  }, IDLE_MS);
}

function dismissIdle() {
  document.getElementById("idleOverlay").classList.remove("show");
  resetIdle();
}

["click", "touchstart", "keydown"].forEach((e) =>
  document.addEventListener(e, resetIdle, {
    passive: true,
  }),
);

function esc(str) {
  const d = document.createElement("div");
  d.textContent = str;
  return d.innerHTML;
}

renderOffices();
resetIdle();
