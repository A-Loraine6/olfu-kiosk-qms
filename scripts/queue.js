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
let transferTargetQueueMode = "unified";
let sessionExpired = false;
let modeReloadPending = false;

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
  const filterStorageKey =
    QUEUE_MODE === "level-based" ? "queueLevelFilter" : "queueTypeFilter";
  typeFilterId = localStorage.getItem(filterStorageKey) || "";
  const typeFilterEl = document.getElementById("typeFilter");
  if (typeFilterEl) {
    const hasSavedOption = Array.from(typeFilterEl.options).some(
      (option) => option.value === typeFilterId,
    );
    if (typeFilterId && !hasSavedOption) {
      typeFilterId = "";
      localStorage.setItem(filterStorageKey, "");
    }
    typeFilterEl.value = typeFilterId;
  }

  const activeTicketPromise = CAN_MANAGE_QUEUE
    ? loadActiveTicket()
    : Promise.resolve();
  activeTicketPromise.then(() => {
    if (CAN_MANAGE_QUEUE) loadOverflowState();
    loadAllTabCounts();
    loadQueue();
    loadStats();
  });
}

function addQueueFilter(params) {
  if (!typeFilterId) return;
  if (QUEUE_MODE === "level-based") {
    params.level_filter = typeFilterId;
  } else if (QUEUE_MODE === "service-based") {
    params.type_filter = typeFilterId;
  }
}

async function loadAllTabCounts() {
  try {
    const tabs = ["waiting", "on-hold", "skipped", "cancelled", "done"];
    const promises = tabs.map(async (tab) => {
      const params = {
        action: "get_queue",
        office_id: OFFICE_ID,
        tab: tab,
      };
      addQueueFilter(params);

      const res = await apiFetch("api/queue_handler.php", params);
      if (res.success) {
        const count = res.tab_count ?? res.tickets?.length ?? 0;
        updateTabCount(tab, count);
      }
    });
    await Promise.all(promises);
  } catch (e) {
    console.warn("loadAllTabCounts failed:", e);
  }
}

function updateClock() {
  const el = document.getElementById("currentTime");
  if (!el) return;
  const now = new Date();
  el.innerHTML = `<i class="fas fa-clock"></i> ${now.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit", second: "2-digit" })}`;
}

function registerKeyboardShortcuts() {
  document.addEventListener("keydown", (e) => {
    const transferModal = document.getElementById("transferModal");
    const transferModalOpen = transferModal?.classList.contains("show");
    if (transferModalOpen && !e.ctrlKey && !e.altKey && !e.metaKey) {
      if (e.key.toUpperCase() === "D") {
        document.getElementById("transferTargetTypeSelect").value = "department";
        onTransferTargetTypeChange();
        document.getElementById("transferOfficeSelect")?.focus();
        e.preventDefault();
        return;
      }
      if (e.key.toUpperCase() === "W") {
        document.getElementById("transferTargetTypeSelect").value = "window";
        onTransferTargetTypeChange();
        document.getElementById("transferWindowSelect")?.focus();
        e.preventDefault();
        return;
      }
      if (/^[1-9]$/.test(e.key)) {
        const windowSelect = document.getElementById("transferWindowSelect");
        if (windowSelect?.querySelector(`option[value="${e.key}"]`)) {
          document.getElementById("transferTargetTypeSelect").value = "window";
          onTransferTargetTypeChange();
          windowSelect.value = e.key;
          e.preventDefault();
          return;
        }
      }
      return;
    }

    if (
      e.target.tagName === "INPUT" ||
      e.target.tagName === "SELECT" ||
      e.target.tagName === "TEXTAREA"
    )
      return;

    const modalOpen = document
      .getElementById("confirmModal")
      ?.classList.contains("show");
    if (modalOpen) {
      if (e.key === "Escape") {
        closeModal("confirmModal");
        e.preventDefault();
        return;
      }
      if (e.key === "Enter") {
        confirmAction();
        e.preventDefault();
        return;
      }
      if (e.key === "Tab") {
        const cancelBtn = document.getElementById("confirmCancelBtn");
        const confirmBtn = document.getElementById("confirmModalBtn");
        const focused = document.activeElement;
        if (focused === confirmBtn) {
          cancelBtn.focus();
        } else {
          confirmBtn.focus();
        }
        e.preventDefault();
        return;
      }
      if (e.key === " ") {
        if (
          document.activeElement === document.getElementById("confirmModalBtn")
        ) {
          confirmAction();
        } else {
          closeModal("confirmModal");
        }
        e.preventDefault();
        return;
      }
    }

    switch (e.key.toUpperCase()) {
      case "N":
        if (!currentTicket && CAN_MANAGE_QUEUE) callNext();
        break;
      case "C":
        if (currentTicket && CAN_MANAGE_QUEUE) completeTicket();
        break;
      case "H":
        if (currentTicket && CAN_MANAGE_QUEUE) holdTicket();
        break;
      case "S":
        if (currentTicket && CAN_MANAGE_QUEUE) skipTicket();
        break;
      case "T":
        if (currentTicket && CAN_MANAGE_QUEUE) openTransferModal(currentTicket.id);
        break;
      case "1":
        switchQueueTabByShortcut("waiting");
        break;
      case "2":
        switchQueueTabByShortcut("on-hold");
        break;
      case "3":
        switchQueueTabByShortcut("skipped");
        break;
      case "4":
        switchQueueTabByShortcut("cancelled");
        break;
      case "5":
        switchQueueTabByShortcut("done");
        break;
      case "R":
        refreshQueue();
        break;
    }
  });
}

function switchQueueTabByShortcut(tab) {
  const tabButton = document.querySelector(`.queue-tab[data-tab="${tab}"]`);
  if (tabButton) switchTab(tab, tabButton);
}

function startRefreshCountdown() {
  if (sessionExpired) return;
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
  const html = `<i class="fas fa-check-circle" style="color:var(--primary)"></i> Updated ${timeStr}`;
  const el1 = document.getElementById("lastRefreshedText");
  const el2 = document.getElementById("queueLastRefreshed");
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
    console.warn("loadActiveTicket failed:", e);
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
    addQueueFilter(params);

    const res = await apiFetch("api/queue_handler.php", params);
    showQueueLoading(false);

    if (res.success) {
      if (res.queue_mode && res.queue_mode !== QUEUE_MODE && !modeReloadPending) {
        modeReloadPending = true;
        window.location.reload();
        return;
      }
      renderQueueList(res.tickets || []);

      const count = res.tab_count ?? res.tickets?.length ?? 0;
      const badge = document.getElementById("queueCountBadge");
      if (badge) {
        badge.textContent = count;
        badge.classList.toggle("zero", count === 0);
      }

      updateTabCount(currentTab, count);
      updateWaitingCount(res.waiting_count ?? 0);
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
      ...(QUEUE_MODE === "level-based" && typeFilterId
        ? { level_filter: typeFilterId }
        : {}),
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
    /* walang gagawin */
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
    "on-hold": "tabCountOnHold",
    skipped: "tabCountSkipped",
    cancelled: "tabCountCancelled",
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
  const labelSpan = btn.querySelector("span:first-of-type");
  const origLabel = labelSpan ? labelSpan.textContent : "Call Next Student";
  if (labelSpan) labelSpan.textContent = "Calling…";
  const iconEl = btn.querySelector("i");
  if (iconEl) {
    iconEl.classList.remove("fa-bell");
    iconEl.classList.add("fa-spinner", "fa-spin");
  }

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
    if (labelSpan) labelSpan.textContent = origLabel;
    if (iconEl) {
      iconEl.classList.remove("fa-spinner", "fa-spin");
      iconEl.classList.add("fa-bell");
    }
    loadStats();
  }
}

function openTransferModal(ticketId = null) {
  const transferTicketId = ticketId ?? currentTicket?.id ?? null;
  const select = document.getElementById("transferOfficeSelect");
  const confirmBtn = document.getElementById("transferConfirmBtn");
  if (!select || !confirmBtn) return;

  if (!transferTicketId) {
    showToast("Select a ticket to transfer first.", "error");
    return;
  }

  confirmBtn.dataset.ticketId = String(transferTicketId);
  document.getElementById("transferTargetTypeSelect").value = "department";
  select.value = "";
  document.getElementById("transferModeSelect").value = "top";
  document.getElementById("transferWindowSelect").value = "";
  onTransferTargetTypeChange();
  resetTransferServiceField();
  openModal("transferModal");
}

function onTransferTargetTypeChange() {
  const targetType = document.getElementById("transferTargetTypeSelect")?.value;
  const departmentGroup = document.getElementById("transferOfficeSelect")?.closest(".form-group");
  const windowGroup = document.getElementById("transferWindowGroup");
  const serviceGroup = document.getElementById("transferServiceGroup");
  const levelGroup = document.getElementById("transferLevelGroup");
  const isWindowTransfer = targetType === "window";

  if (departmentGroup) departmentGroup.style.display = isWindowTransfer ? "none" : "";
  if (windowGroup) windowGroup.style.display = isWindowTransfer ? "" : "none";
  if (serviceGroup && isWindowTransfer) serviceGroup.style.display = "none";
  if (levelGroup && isWindowTransfer) levelGroup.style.display = "none";
}

function resetTransferServiceField() {
  transferTargetQueueMode = "unified";
  const group = document.getElementById("transferServiceGroup");
  const serviceSelect = document.getElementById("transferServiceSelect");
  const levelGroup = document.getElementById("transferLevelGroup");
  const levelSelect = document.getElementById("transferLevelSelect");
  if (group) group.style.display = "none";
  if (levelGroup) levelGroup.style.display = "none";
  if (serviceSelect) {
    serviceSelect.innerHTML = '<option value="">Select service</option>';
    serviceSelect.value = "";
  }
  if (levelSelect) levelSelect.value = "";
}

async function onTransferOfficeChange() {
  const targetOfficeId = Number(document.getElementById("transferOfficeSelect").value);
  resetTransferServiceField();
  if (!targetOfficeId) return;

  const group = document.getElementById("transferServiceGroup");
  const serviceSelect = document.getElementById("transferServiceSelect");

  try {
    const res = await apiFetch("api/queue_handler.php", {
      action: "get_office_services",
      office_id: OFFICE_ID, // staff's own office (required by the auth check)
      target_office_id: targetOfficeId,
    });

    if (!res.success) {
      showToast(res.message || "Failed to load destination services.", "error");
      return;
    }

    transferTargetQueueMode = res.queue_mode || "unified";

    if (transferTargetQueueMode === "service-based") {
      serviceSelect.innerHTML =
        '<option value="">Select service</option>' +
        (res.services || [])
          .map((s) => `<option value="${s.id}">${escHtml(s.name)}</option>`)
          .join("");
      group.style.display = "";
    } else if (transferTargetQueueMode === "level-based") {
      const levelGroup = document.getElementById("transferLevelGroup");
      if (levelGroup) levelGroup.style.display = "";
    }
  } catch (e) {
    showToast("Network error while loading destination services.", "error");
  }
}

async function submitTransfer() {
  const targetType = document.getElementById("transferTargetTypeSelect")?.value || "department";
  const select = document.getElementById("transferOfficeSelect");
  const mode = document.getElementById("transferModeSelect");
  const confirmBtn = document.getElementById("transferConfirmBtn");
  const serviceSelect = document.getElementById("transferServiceSelect");
  const levelSelect = document.getElementById("transferLevelSelect");
  const windowSelect = document.getElementById("transferWindowSelect");
  if (!select || !mode || !confirmBtn || !windowSelect) return;

  const targetOfficeId = Number(select.value);
  const targetWindow = Number(windowSelect.value);
  const ticketId = Number(confirmBtn.dataset.ticketId || 0);
  const placement = mode.value || "top";

  if ((!targetOfficeId && targetType === "department") || (!targetWindow && targetType === "window") || !ticketId) {
    showToast("Please choose a department and ticket to transfer.", "error");
    return;
  }

  let targetServiceId = "";
  if (targetType === "department" && transferTargetQueueMode === "service-based") {
    targetServiceId = Number(serviceSelect?.value || 0);
    if (!targetServiceId) {
      showToast("Please choose a service at the destination department.", "error");
      return;
    }
  }
  const targetLevel =
    targetType === "department" && transferTargetQueueMode === "level-based"
      ? levelSelect?.value || ""
      : "";
  if (targetType === "department" && transferTargetQueueMode === "level-based" && !targetLevel) {
    showToast("Please choose SHS or College for the destination department.", "error");
    return;
  }

  confirmBtn.disabled = true;
  const prevText = confirmBtn.innerHTML;
  confirmBtn.innerHTML = '<span>Transferring…</span>';

  try {
    const params = {
      action: targetType === "window" ? "transfer_window" : "transfer_ticket",
      office_id: OFFICE_ID,
      ticket_id: ticketId,
      placement: placement,
      csrf_token: CSRF_TOKEN,
    };
    if (targetType === "window") {
      params.target_window = targetWindow;
    } else {
      params.target_office_id = targetOfficeId;
      if (targetServiceId) params.target_service_id = targetServiceId;
      if (targetLevel) params.target_level = targetLevel;
    }

    const res = await apiFetch("api/queue_handler.php", params);

    if (res.success) {
      closeModal("transferModal");
      showToast(res.message || "Ticket transferred.", "success");
      if (currentTicket && Number(currentTicket.id) === ticketId) {
        currentTicket = null;
        stopTimer();
        clearServingCard();
      }
      await Promise.all([loadQueue(), loadAllTabCounts(), loadStats()]);
      startRefreshCountdown();
    } else {
      showToast(res.message || "Transfer failed.", "error");
    }
  } catch (e) {
    showToast("Network error while transferring ticket.", "error");
  } finally {
    confirmBtn.disabled = false;
    confirmBtn.innerHTML = prevText;
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

function holdTicket() {
  if (!currentTicket) return;
  openConfirmModal(
    "Place Ticket on Hold",
    `Place <strong>${escHtml(currentTicket.ticket_number)}</strong> on hold? You can resume it later from the On Hold tab.`,
    "btn-warning",
    { action: "hold", ticketId: currentTicket.id },
  );
}

function skipTicket() {
  if (!currentTicket) return;
  openConfirmModal(
    "Skip Ticket",
    `Skip <strong>${escHtml(currentTicket.ticket_number)}</strong>? It can be recalled from the Skipped tab.`,
    "btn-warning",
    { action: "skip", ticketId: currentTicket.id },
  );
}

async function confirmAction() {
  if (!pendingAction) return;

  const { action, ticketId } = pendingAction;
  pendingAction = null;

  closeModal("confirmModal");

  const isServingTicket =
    currentTicket && String(currentTicket.id) === String(ticketId);

  if (isServingTicket) disableServingActions(true);

  const actionMap = {
    complete: "complete_ticket",
    hold: "hold_ticket",
    skip: "skip_ticket",
    serve: "serve_ticket",
  };
  const apiAction = actionMap[action] || "skip_ticket";

  console.log("[confirmAction]", {
    action,
    apiAction,
    ticketId,
    isServingTicket,
    currentTicketId: currentTicket?.id,
  });

  try {
    const res = await apiFetch("api/queue_handler.php", {
      action: apiAction,
      ticket_id: ticketId,
      office_id: OFFICE_ID,
    });

    console.log("[confirmAction] response:", res);

    if (res.success) {
      if (action === "serve") {
        currentTicket = res.ticket;
        serveStartTime = new Date();
        renderServingCard(res.ticket, true);
        startTimer();
      } else if (isServingTicket) {
        stopTimer();
        currentTicket = null;
        serveStartTime = null;
        clearServingCard();
      }
      const msgMap = {
        complete: "Transaction completed!",
        hold: "Ticket placed on hold.",
        skip: "Ticket skipped.",
        serve: `Now serving ${res.ticket?.ticket_number ?? ""}`.trim(),
      };
      showToast(msgMap[action] || "Action completed.", "success");
      await Promise.all([loadQueue(), loadAllTabCounts(), loadStats()]);
      startRefreshCountdown();
    } else {
      showToast(res.message || "Action failed.", "error");
      if (isServingTicket) disableServingActions(false);
    }
  } catch (e) {
    console.error("[confirmAction] network error:", e);
    showToast("Network error.", "error");
    if (isServingTicket) disableServingActions(false);
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
      await Promise.all([loadQueue(), loadAllTabCounts(), loadStats()]);
    } else {
      showToast(res.message || "Failed to recall ticket.", "error");
    }
  } catch (e) {
    showToast("Network error.", "error");
  }
}

async function resumeTicket(ticketId) {
  if (currentTicket) {
    showToast("Complete the current ticket before resuming another.", "error");
    return;
  }
  try {
    const res = await apiFetch("api/queue_handler.php", {
      action: "resume_ticket",
      ticket_id: ticketId,
      office_id: OFFICE_ID,
    });
    if (res.success && res.ticket) {
      currentTicket = res.ticket;

      const alreadySpent = parseInt(res.ticket.time_spent ?? 0, 10);
      serveStartTime = new Date(Date.now() - alreadySpent * 1000);

      renderServingCard(res.ticket, true);
      startTimer();
      showToast(`Resuming ${res.ticket.ticket_number}`, "success");
      await Promise.all([loadQueue(), loadAllTabCounts(), loadStats()]);
    } else {
      showToast(res.message || "Failed to resume ticket.", "error");
    }
  } catch (e) {
    showToast("Network error.", "error");
  }
}

function levelBadgeHtml(level) {
  if (level !== "shs" && level !== "college") return "";
  return `<span class="ticket-level-badge">${level === "shs" ? "SHS" : "College"}</span>`;
}

function renderServingCard(ticket, showNew = false) {
  const ticketEl = document.getElementById("servingTicket");
  const levelBadgeEl = document.getElementById("servingLevelBadge");
  const studentEl = document.getElementById("servingStudent");
  const typeEl = document.getElementById("servingType");
  const windowEl = document.getElementById("servingWindow");
  const timerEl = document.getElementById("servingTimer");
  const actionsEl = document.getElementById("servingActions");
  const cardEl = document.getElementById("servingCard");
  const badgeEl = document.getElementById("newBadge");

  ticketEl.textContent = ticket.ticket_number;
  ticketEl.classList.remove("is-idle", "pulse");
  void ticketEl.offsetWidth;
  ticketEl.classList.add("pulse");
  if (levelBadgeEl) {
    const hasLevel = ticket.student_level === "shs" || ticket.student_level === "college";
    levelBadgeEl.textContent = ticket.student_level === "shs" ? "SHS" : "College";
    levelBadgeEl.style.display = hasLevel ? "inline-flex" : "none";
  }

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

  const visitorName = ticket.visitor_name || ticket.student_id;
  if (visitorName) {
    studentEl.innerHTML = `<i class="fas fa-user"></i> ${escHtml(visitorName)}`;
    studentEl.style.display = "";
  } else {
    studentEl.style.display = "none";
  }

  if (ticket.transaction_type) {
    const serviceText = ticket.transaction_type
      ? `<i class="fas fa-tag" style="font-size:10px;opacity:0.7;"></i> ${escHtml(ticket.transaction_type)}`
      : "";
    typeEl.innerHTML = serviceText;
    typeEl.style.display = "";
  } else {
    typeEl.style.display = "none";
  }

  const windowNumber = ticket.window_number ?? ticket.window_id ?? null;
  if (windowNumber && Number(windowNumber) > 0) {
    windowEl.innerHTML = `<i class="fas fa-window-maximize" style="font-size:10px;opacity:0.7;"></i> Window ${Number(windowNumber)}`;
    windowEl.style.display = "";
  } else {
    windowEl.style.display = "none";
  }

  timerEl.style.display = "";
  actionsEl.style.display = "grid";
  disableServingActions(false);
}

function clearServingCard() {
  const ticketEl = document.getElementById("servingTicket");
  const levelBadgeEl = document.getElementById("servingLevelBadge");
  const cardEl = document.getElementById("servingCard");
  const studentEl = document.getElementById("servingStudent");

  ticketEl.textContent = "No ticket";
  if (levelBadgeEl) {
    levelBadgeEl.textContent = "";
    levelBadgeEl.style.display = "none";
  }
  ticketEl.classList.remove("pulse");
  ticketEl.classList.add("is-idle");
  cardEl.classList.remove("has-ticket");

  studentEl.innerHTML = `<i class="fas fa-user"></i> Waiting for next student…`;
  studentEl.style.display = "";

  document.getElementById("servingType").style.display = "none";
  document.getElementById("servingWindow").style.display = "none";
  document.getElementById("servingTimer").style.display = "none";
  document.getElementById("servingActions").style.display = "none";
  document.getElementById("timerDisplay").textContent = "00:00";
  document.getElementById("servingTimer").classList.remove("timer-warn");

  const badgeEl = document.getElementById("newBadge");
  if (badgeEl) badgeEl.style.display = "none";
}

function disableServingActions(state) {
  const c = document.getElementById("completeBtn");
  const h = document.getElementById("holdBtn");
  const s = document.getElementById("skipBtn");
  if (c) c.disabled = state;
  if (h) h.disabled = state;
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
  const isServing = currentTicket && String(currentTicket.id) === String(t.id);
  const isSkipped = t.status === "skipped";
  const isDone = t.status === "completed";
  const isOnHold = t.status === "on-hold";
  const isCancelled = t.status === "cancelled";
  const isFirst =
    !isServing &&
    !isSkipped &&
    !isDone &&
    !isOnHold &&
    !isCancelled &&
    index === 0;

  let rowClass = "ticket-row";
  if (isServing) rowClass += " is-serving";
  if (isSkipped) rowClass += " is-skipped";
  if (isDone) rowClass += " is-done";
  if (isOnHold) rowClass += " is-on-hold";
  if (isCancelled) rowClass += " is-cancelled";

  const pos = isServing
    ? `<span class="ticket-pos" style="background:var(--primary);color:#fff;border-color:var(--primary);font-size:9px;">NOW</span>`
    : isDone || isSkipped || isOnHold || isCancelled
      ? ""
      : `<span class="ticket-pos${isFirst ? " pos-first" : ""}">${index + 1}</span>`;

  const visitorName = t.visitor_name || t.student_id;
  const studentHtml = visitorName
    ? `<div class="ticket-student"><i class="fas fa-user"></i> ${escHtml(visitorName)}</div>`
    : "";

  const typeHtml = t.transaction_type
    ? `<div class="ticket-type">${t.transaction_type ? `<i class="fas fa-tag" style="font-size:9px;margin-right:3px;opacity:0.6;"></i>${escHtml(t.transaction_type)}` : ""}</div>`
    : "";
  const ticketNumberHtml = `
    <div class="ticket-number-level-wrap">
      <span class="ticket-num">${escHtml(t.ticket_number)}</span>
      ${levelBadgeHtml(t.student_level)}
    </div>`;

  const statusChip = isDone
    ? `<span class="ticket-status-chip chip-done"><i class="fas fa-check"></i> Done</span>`
    : isSkipped
      ? `<span class="ticket-status-chip chip-skipped"><i class="fas fa-forward"></i> Skipped</span>`
      : isOnHold
        ? `<span class="ticket-status-chip chip-on-hold"><i class="fas fa-pause"></i> On Hold</span>`
        : isCancelled
          ? `<span class="ticket-status-chip chip-cancelled"><i class="fas fa-ban"></i> Cancelled</span>`
          : "";

  let actionsHtml = "";
  if (CAN_MANAGE_QUEUE && !isDone && !isCancelled) {
    if (isSkipped) {
      actionsHtml = `
        <div class="ticket-actions-inline">
          <button class="ticket-btn" title="Recall this ticket" onclick="recallTicket(${t.id})">
            <i class="fas fa-redo"></i>
          </button>
        </div>`;
    } else if (isOnHold) {
      actionsHtml = `
        <div class="ticket-actions-inline">
          <button class="ticket-btn" title="Resume this ticket" onclick="resumeTicket(${t.id})">
            <i class="fas fa-play"></i>
          </button>
        </div>`;
    } else if (!isServing) {
      const serveBtn =
        CAN_MANAGE_QUEUE && t.status === "waiting"
          ? `<button class="ticket-btn ticket-btn-serve" title="Serve this ticket" onclick="serveSpecificTicket(${t.id}, '${escHtml(t.ticket_number)}')">
         <i class="fas fa-bell"></i>
       </button>`
          : "";
      const transferBtn = CAN_MANAGE_QUEUE
        ? `<button class="ticket-btn" title="Transfer this ticket" onclick="openTransferModal(${t.id})">
            <i class="fas fa-right-left"></i>
          </button>`
        : "";
      actionsHtml = `
      <div class="ticket-actions-inline">
        ${serveBtn}
        ${transferBtn}
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
      ${ticketNumberHtml}
      <div class="ticket-info">
        ${studentHtml}
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
  const filterStorageKey =
    QUEUE_MODE === "level-based" ? "queueLevelFilter" : "queueTypeFilter";
  localStorage.setItem(filterStorageKey, typeFilterId);
  loadQueue();
}

function refreshQueue() {
  const btn = document.getElementById("refreshBtn");
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

function serveSpecificTicket(ticketId, ticketNum) {
  if (currentTicket) {
    showToast(
      "Complete or skip your current ticket before serving another.",
      "error",
    );
    return;
  }
  openConfirmModal(
    "Serve This Ticket",
    `Start serving <strong>${escHtml(ticketNum)}</strong> now? This skips the normal queue order.`,
    "btn-primary",
    { action: "serve", ticketId },
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
    document
      .getElementById("servingTimer")
      .classList.toggle("timer-warn", elapsed >= TIMER_WARN_SECS);
  }, 1000);
}

function stopTimer() {
  clearInterval(timerInterval);
  timerInterval = null;
}

function openModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.add("show");
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
  const modal = document.getElementById(id);
  if (modal) modal.classList.remove("show");
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
  if (sessionExpired) return { success: false };

  const body = new URLSearchParams({ ...params, csrf_token: CSRF_TOKEN });
  const res = await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: body.toString(),
  });
  if (res.status === 401) {
    sessionExpired = true;
    clearTimeout(autoRefreshTimer);
    clearInterval(refreshRingInterval);
    stopTimer();
    autoRefreshTimer = null;
    refreshRingInterval = null;
    showToast("Session expired. Redirecting to login…", "error");
    setTimeout(() => {
      window.location.href = "login.php";
    }, 1500);
    return { success: false };
  }
  const data = await res.json();
  if (!res.ok && res.status !== 403) throw new Error(`HTTP ${res.status}`);
  return data;
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
  if (seconds < 60) return `${seconds}s`;
  const m = Math.floor(seconds / 60);
  const s = seconds % 60;
  return s > 0 ? `${m}m ${s}s` : `${m}m`;
}

function timeAgoShort(isoDateStr) {
  try {
    const d = new Date(isoDateStr.replace(" ", "T"));
    const now = new Date();
    const diff = Math.floor((now - d) / 1000);
    if (diff < 60) return `${diff}s ago`;
    const m = Math.floor(diff / 60);
    if (m < 60) return `${m}m ago`;
    const h = Math.floor(m / 60);
    return `${h}h ago`;
  } catch {
    return "—";
  }
}

async function loadOverflowState() {
  try {
    const res = await apiFetch("api/queue_handler.php", {
      action: "get_overflow_state",
      office_id: OFFICE_ID,
    });
    if (res.success) updateOverflowBtn(res.overflow_mode);
  } catch (e) {
    console.warn("loadOverflowState failed:", e);
  }
}

async function toggleOverflow() {
  const res = await apiFetch("api/queue_handler.php", {
    action: "toggle_overflow",
    office_id: OFFICE_ID,
  });
  if (res.success) {
    updateOverflowBtn(res.overflow_mode);
    showToast(res.message, "success");
  } else {
    showToast(res.message || "Failed to toggle overflow.", "error");
  }
}

function updateOverflowBtn(isOverflow) {
  const row = document.getElementById("overflowRow");
  const desc = document.getElementById("overflowDesc");
  const typeFilter = document.getElementById("typeFilter");
  if (!row) return;

  row.classList.toggle("overflow-active", isOverflow);
  desc.textContent = isOverflow
    ? "Pulling any waiting ticket in this office"
    : "Pull tickets from your assigned window only";

  if (typeFilter) {
    typeFilter.value = "";
    typeFilterId = "";
  }

  loadQueue();
  loadAllTabCounts();
}
