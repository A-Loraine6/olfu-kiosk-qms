let currentTicket = null;
let currentTab = "waiting";
let typeFilterId = "";
let timerInterval = null;
let serveStartTime = null;
let autoRefreshTimer = null;
let pendingAction = null;
let refreshCountdown = 0;
let refreshRingInterval = null;
let newBadgeTimeout = null;

const AUTO_REFRESH_MS = 15000;
const TIMER_WARN_SECS = 300;

document.addEventListener("DOMContentLoaded", () => {
  updateClock();
  setInterval(updateClock, 1000);
  registerKeyboardShortcuts();
  initQueue();
  startRefreshCountdown();
});

function initQueue() {
  loadQueue();
  loadStats();
  loadActiveTicket();
}

function updateClock() {
  const el = document.getElementById("currentTime");
  if (!el) return;
  const now = new Date();
  el.innerHTML = `<i class="fas fa-clock"></i> ${now.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit", second: "2-digit" })}`;
}

function registerKeyboardShortcuts() {
  document.addEventListener("keydown", (e) => {
    if (
      e.target.tagName === "INPUT" ||
      e.target.tagName === "SELECT" ||
      e.target.tagName === "TEXTAREA"
    )
      return;
    if (document.getElementById("confirmModal")?.classList.contains("show"))
      return;

    switch (e.key.toUpperCase()) {
      case "N":
        if (!currentTicket) callNext();
        break;
      case "C":
        if (currentTicket) completeTicket();
        break;
      case "S":
        if (currentTicket) skipTicket();
        break;
      case "R":
        refreshQueue();
        break;
    }
  });
}

function startRefreshCountdown() {
  clearInterval(refreshRingInterval);
  clearTimeout(autoRefreshTimer);

  refreshCountdown = AUTO_REFRESH_MS / 1000;
  const circumference = 69.12;
  const progressEl = document.getElementById("refreshRingProgress");
  const barFill = document.getElementById("autoRefreshBarFill");

  if (barFill) {
    barFill.style.transition = "none";
    barFill.style.transform = "scaleX(1)";
    requestAnimationFrame(() => {
      barFill.style.transition = `transform ${AUTO_REFRESH_MS}ms linear`;
      barFill.style.transform = "scaleX(0)";
    });
  }

  refreshRingInterval = setInterval(() => {
    refreshCountdown--;
    if (progressEl) {
      const fraction = refreshCountdown / (AUTO_REFRESH_MS / 1000);
      progressEl.style.strokeDashoffset = circumference * (1 - fraction);
    }
    if (refreshCountdown <= 0) clearInterval(refreshRingInterval);
  }, 1000);

  autoRefreshTimer = setTimeout(() => {
    loadQueue();
    loadStats();
    startRefreshCountdown();
  }, AUTO_REFRESH_MS);
}

function setLastRefreshed() {
  const now = new Date();
  const timeStr = now.toLocaleTimeString("en-US", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
  const el1 = document.getElementById("lastRefreshedText");
  const el2 = document.getElementById("queueLastRefreshed");
  const html = `<i class="fas fa-check-circle" style="color:var(--primary)"></i> Updated ${timeStr}`;
  if (el1) el1.innerHTML = html;
  if (el2) el2.innerHTML = html;
}

async function loadActiveTicket() {
  try {
    const res = await apiFetch("api/queue_handler.php", {
      action: "get_active",
      office_id: OFFICE_ID,
    });
    if (res.success && res.ticket) {
      currentTicket = res.ticket;
      serveStartTime = res.ticket.served_at
        ? new Date(res.ticket.served_at.replace(" ", "T"))
        : new Date();
      renderServingCard(res.ticket, false);
      startTimer();
    }
  } catch (e) {
    // walang gagawin
  }
}

async function loadQueue() {
  showQueueLoading(true);
  try {
    const params = {
      action: "get_queue",
      office_id: OFFICE_ID,
      tab: currentTab,
    };
    if (typeFilterId) params.type_filter = typeFilterId;

    const res = await apiFetch("api/queue_handler.php", params);
    showQueueLoading(false);

    if (res.success) {
      renderQueueList(res.tickets || []);

      const count = res.tickets?.length ?? 0;
      const badge = document.getElementById("queueCountBadge");
      if (badge) {
        badge.textContent = count;
        badge.classList.toggle("zero", count === 0);
      }

      updateTabCount(currentTab, count);

      const waiting = res.waiting_count ?? 0;
      updateWaitingCount(waiting);

      setLastRefreshed();
    } else {
      showQueueError(res.message || "Failed to load queue.");
    }
  } catch (e) {
    showQueueLoading(false);
    showQueueError("Network error. Check your connection.");
  }
}

async function loadStats() {
  try {
    const res = await apiFetch("api/queue_handler.php", {
      action: "get_stats",
      office_id: OFFICE_ID,
    });
    if (res.success) {
      setStatVal("statServed", res.served ?? 0);
      setStatVal("statSkipped", res.skipped ?? 0);
      setStatVal("statWaiting", res.waiting ?? 0);

      const avgEl = document.getElementById("statAvgTime");
      if (avgEl) {
        const newVal = res.avg_time ? formatDuration(res.avg_time) : "—";
        if (avgEl.textContent !== newVal) {
          avgEl.textContent = newVal;
          avgEl.classList.remove("updated");
          void avgEl.offsetWidth;
          avgEl.classList.add("updated");
        }
      }

      updateWaitingCount(res.waiting ?? 0);
    }
  } catch (e) {
    /* ignore */
  }
}

function setStatVal(id, val) {
  const el = document.getElementById(id);
  if (!el) return;
  const display = String(val);
  if (el.textContent !== display) {
    el.textContent = display;
    el.classList.remove("updated");
    void el.offsetWidth;
    el.classList.add("updated");
  }
}

function updateWaitingCount(waiting) {
  const countEl = document.getElementById("callNextCount");
  const btn = document.getElementById("callNextBtn");
  if (countEl) countEl.textContent = `${waiting} waiting`;
  if (btn) btn.classList.toggle("has-waiting", waiting > 0);
}

function updateTabCount(tab, count) {
  const map = {
    waiting: "tabCountWaiting",
    skipped: "tabCountSkipped",
    done: "tabCountDone",
  };
  const el = document.getElementById(map[tab]);
  if (el) el.textContent = count;
}

async function callNext() {
  if (currentTicket) {
    showToast("Please complete or skip the current ticket first.", "error");
    return;
  }

  const btn = document.getElementById("callNextBtn");
  btn.disabled = true;
  btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> <span>Calling…</span>`;

  try {
    const params = { action: "call_next", office_id: OFFICE_ID };
    if (typeFilterId) params.type_filter = typeFilterId;
    const res = await apiFetch("api/queue_handler.php", params);

    if (res.success && res.ticket) {
      currentTicket = res.ticket;
      serveStartTime = new Date();
      renderServingCard(res.ticket, true);
      startTimer();
      showToast(`Now serving ${res.ticket.ticket_number}`, "success");
      loadQueue();
      loadStats();
    } else {
      showToast(res.message || "No tickets in queue.", "error");
    }
  } catch (e) {
    showToast("Failed to call next ticket.", "error");
  } finally {
    btn.disabled = false;
    btn.innerHTML = `<i class="fas fa-bell"></i> <span>Call Next Student</span> <span class="call-next-count" id="callNextCount">0 waiting</span>`;
    loadStats();
  }
}

function completeTicket() {
  if (!currentTicket) return;
  openConfirmModal(
    "Complete Transaction",
    `Mark <strong>${escHtml(currentTicket.ticket_number)}</strong> as completed?`,
    "btn-primary",
    { action: "complete", ticketId: currentTicket.id },
  );
}

function skipTicket() {
  if (!currentTicket) return;
  openConfirmModal(
    "Skip Ticket",
    `Skip <strong>${escHtml(currentTicket.ticket_number)}</strong>? The student can be recalled from the Skipped tab.`,
    "btn-warning",
    { action: "skip", ticketId: currentTicket.id },
  );
}

async function confirmAction() {
  if (!pendingAction) return;
  closeModal("confirmModal");

  const { action, ticketId } = pendingAction;
  pendingAction = null;

  disableServingActions(true);

  try {
    const res = await apiFetch("api/queue_handler.php", {
      action: action === "complete" ? "complete_ticket" : "skip_ticket",
      ticket_id: ticketId,
      office_id: OFFICE_ID,
    });

    if (res.success) {
      stopTimer();
      currentTicket = null;
      serveStartTime = null;
      clearServingCard();
      showToast(
        action === "complete" ? "Transaction completed!" : "Ticket skipped.",
        "success",
      );
      loadQueue();
      loadStats();
    } else {
      showToast(res.message || "Action failed.", "error");
      disableServingActions(false);
    }
  } catch (e) {
    showToast("Network error.", "error");
    disableServingActions(false);
  }
}

async function recallTicket(ticketId) {
  if (currentTicket) {
    showToast("Complete the current ticket before recalling another.", "error");
    return;
  }
  try {
    const res = await apiFetch("api/queue_handler.php", {
      action: "recall_ticket",
      ticket_id: ticketId,
      office_id: OFFICE_ID,
    });
    if (res.success && res.ticket) {
      currentTicket = res.ticket;
      serveStartTime = new Date();
      renderServingCard(res.ticket, true);
      startTimer();
      showToast(`Recalling ${res.ticket.ticket_number}`, "success");
      loadQueue();
      loadStats();
    } else {
      showToast(res.message || "Failed to recall ticket.", "error");
    }
  } catch (e) {
    showToast("Network error.", "error");
  }
}

function renderServingCard(ticket, showNew = false) {
  const ticketEl = document.getElementById("servingTicket");
  const studentEl = document.getElementById("servingStudent");
  const typeEl = document.getElementById("servingType");
  const timerEl = document.getElementById("servingTimer");
  const actionsEl = document.getElementById("servingActions");
  const cardEl = document.getElementById("servingCard");
  const badgeEl = document.getElementById("newBadge");

  ticketEl.textContent = ticket.ticket_number;
  ticketEl.classList.remove("is-idle", "pulse");
  void ticketEl.offsetWidth;
  ticketEl.classList.add("pulse");

  cardEl.classList.add("has-ticket");

  if (badgeEl) {
    clearTimeout(newBadgeTimeout);
    if (showNew) {
      badgeEl.style.display = "";
      badgeEl.style.animation = "none";
      void badgeEl.offsetWidth;
      badgeEl.style.animation = "";
      newBadgeTimeout = setTimeout(() => {
        badgeEl.style.display = "none";
      }, 2400);
    } else {
      badgeEl.style.display = "none";
    }
  }

  studentEl.innerHTML = `<i class="fas fa-user"></i> ${escHtml(ticket.student_id || "Walk-in")}`;

  if (ticket.transaction_type) {
    typeEl.innerHTML = `<i class="fas fa-tag" style="font-size:10px;opacity:0.7;"></i> ${escHtml(ticket.transaction_type)}`;
    typeEl.style.display = "";
  } else {
    typeEl.style.display = "none";
  }

  timerEl.style.display = "";
  actionsEl.style.display = "grid";
  disableServingActions(false);
}

function clearServingCard() {
  const ticketEl = document.getElementById("servingTicket");
  const cardEl = document.getElementById("servingCard");

  ticketEl.textContent = "No ticket";
  ticketEl.classList.remove("pulse");
  ticketEl.classList.add("is-idle");

  cardEl.classList.remove("has-ticket");

  document.getElementById("servingStudent").innerHTML =
    `<i class="fas fa-user"></i> Waiting for next student…`;
  document.getElementById("servingType").style.display = "none";
  document.getElementById("servingTimer").style.display = "none";
  document.getElementById("servingActions").style.display = "none";
  document.getElementById("timerDisplay").textContent = "00:00";
  document.getElementById("servingTimer").classList.remove("timer-warn");

  const badgeEl = document.getElementById("newBadge");
  if (badgeEl) badgeEl.style.display = "none";
}

function disableServingActions(state) {
  const c = document.getElementById("completeBtn");
  const s = document.getElementById("skipBtn");
  if (c) c.disabled = state;
  if (s) s.disabled = state;
}

function renderQueueList(tickets) {
  const listEl = document.getElementById("queueList");
  const emptyEl = document.getElementById("queueEmpty");

  if (!tickets.length) {
    listEl.style.display = "none";
    emptyEl.style.display = "";
    return;
  }

  emptyEl.style.display = "none";
  listEl.style.display = "";
  listEl.innerHTML = tickets.map((t, i) => renderTicketRow(t, i)).join("");
}

function renderTicketRow(t, index) {
  const isServing = currentTicket && currentTicket.id === t.id;
  const isSkipped = t.status === "skipped";
  const isDone = t.status === "completed";
  const isFirst = !isServing && !isSkipped && !isDone && index === 0;

  let rowClass = "ticket-row";
  if (isServing) rowClass += " is-serving";
  if (isSkipped) rowClass += " is-skipped";
  if (isDone) rowClass += " is-done";

  const pos = isServing
    ? `<span class="ticket-pos" style="background:var(--primary);color:#fff;border-color:var(--primary);font-size:9px;">NOW</span>`
    : isDone || isSkipped
      ? ""
      : `<span class="ticket-pos${isFirst ? " pos-first" : ""}">${index + 1}</span>`;

  const typeHtml = t.transaction_type
    ? `<div class="ticket-type"><i class="fas fa-tag" style="font-size:9px;margin-right:3px;opacity:0.6;"></i>${escHtml(t.transaction_type)}</div>`
    : "";

  const statusChip = isDone
    ? `<span class="ticket-status-chip chip-done"><i class="fas fa-check"></i> Done</span>`
    : isSkipped
      ? `<span class="ticket-status-chip chip-skipped"><i class="fas fa-forward"></i> Skipped</span>`
      : "";

  let actionsHtml = "";
  if (!isDone) {
    if (isSkipped) {
      actionsHtml = `
                <div class="ticket-actions-inline">
                    <button class="ticket-btn" title="Recall this ticket (bring back to serving)" onclick="recallTicket(${t.id})">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>`;
    } else if (!isServing) {
      actionsHtml = `
                <div class="ticket-actions-inline">
                    <button class="ticket-btn btn-danger" title="Skip this ticket" onclick="manualSkip(${t.id}, '${escHtml(t.ticket_number)}')">
                        <i class="fas fa-forward"></i>
                    </button>
                </div>`;
    }
  }

  const timeAgo = t.created_at ? timeAgoShort(t.created_at) : "";

  return `
    <div class="${rowClass}" id="ticketRow_${t.id}">
        ${pos}
        <span class="ticket-num">${escHtml(t.ticket_number)}</span>
        <div class="ticket-info">
            <div class="ticket-student">${escHtml(t.student_id || "Walk-in")}</div>
            ${typeHtml}
        </div>
        ${statusChip}
        <div class="ticket-time">${timeAgo}</div>
        ${actionsHtml}
    </div>`;
}

function switchTab(tab, el) {
  currentTab = tab;
  document
    .querySelectorAll(".queue-tab")
    .forEach((t) => t.classList.remove("active"));
  el.classList.add("active");
  loadQueue();
}

function applyTypeFilter() {
  typeFilterId = document.getElementById("typeFilter")?.value || "";
  loadQueue();
}

function refreshQueue() {
  const btn = document.getElementById("refreshBtn");
  const icon = document.getElementById("refreshIcon");
  btn.classList.add("spinning");
  Promise.all([loadQueue(), loadStats()]).finally(() => {
    setTimeout(() => btn.classList.remove("spinning"), 600);
    startRefreshCountdown();
  });
}

function manualSkip(ticketId, ticketNum) {
  openConfirmModal(
    "Skip Ticket",
    `Skip ticket <strong>${escHtml(ticketNum)}</strong> from the queue?`,
    "btn-warning",
    { action: "skip", ticketId },
  );
}

function startTimer() {
  stopTimer();
  document.getElementById("servingTimer").style.display = "";
  timerInterval = setInterval(() => {
    if (!serveStartTime) return;
    const elapsed = Math.floor((Date.now() - serveStartTime) / 1000);
    document.getElementById("timerDisplay").textContent =
      formatSeconds(elapsed);

    const timerEl = document.getElementById("servingTimer");
    timerEl.classList.toggle("timer-warn", elapsed >= TIMER_WARN_SECS);
  }, 1000);
}

function stopTimer() {
  clearInterval(timerInterval);
  timerInterval = null;
}

function openConfirmModal(title, body, btnClass, action) {
  pendingAction = action;
  document.getElementById("confirmModalTitle").innerHTML =
    `<i class="fas fa-question-circle"></i> ${title}`;
  document.getElementById("confirmModalBody").innerHTML = body;
  const btn = document.getElementById("confirmModalBtn");
  btn.className = `btn ${btnClass}`;
  btn.textContent = "Confirm";
  document.getElementById("confirmModal").classList.add("show");
}

function closeModal(id) {
  document.getElementById(id).classList.remove("show");
  pendingAction = null;
}

function showQueueLoading(show) {
  document.getElementById("queueLoading").style.display = show
    ? "flex"
    : "none";
  if (show) {
    document.getElementById("queueList").style.display = "none";
    document.getElementById("queueEmpty").style.display = "none";
  }
}

function showQueueError(msg) {
  const listEl = document.getElementById("queueList");
  listEl.style.display = "";
  listEl.innerHTML = `<div style="padding:32px;text-align:center;font-size:13px;color:var(--text-3);">
        <i class="fas fa-exclamation-circle" style="color:#c62828;margin-right:6px;"></i>${escHtml(msg)}
    </div>`;
}

async function apiFetch(url, params = {}) {
  const body = new URLSearchParams({ ...params, csrf_token: CSRF_TOKEN });
  const res = await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: body.toString(),
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

function showToast(message, type = "success") {
  const container = document.getElementById("toast-container");
  const toast = document.createElement("div");
  const icon = type === "success" ? "fa-check-circle" : "fa-exclamation-circle";
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<i class="fas ${icon}"></i><span>${escHtml(message)}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>`;
  container.appendChild(toast);
  requestAnimationFrame(() => toast.classList.add("show"));
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => toast.remove(), 350);
  }, 3500);
}

function escHtml(str) {
  return String(str ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function formatSeconds(s) {
  const m = Math.floor(s / 60)
    .toString()
    .padStart(2, "0");
  const sec = (s % 60).toString().padStart(2, "0");
  return `${m}:${sec}`;
}

function formatDuration(seconds) {
  if (seconds < 60) return `${Math.round(seconds)}s`;
  const m = Math.floor(seconds / 60);
  const s = Math.round(seconds % 60);
  return s ? `${m}m ${s}s` : `${m}m`;
}

function timeAgoShort(dateStr) {
  const d = new Date(dateStr.replace(" ", "T"));
  const diff = Math.floor((Date.now() - d) / 1000);
  if (diff < 60) return `${diff}s ago`;
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  return `${Math.floor(diff / 3600)}h ago`;
}
