let lastPeopleAhead = null;
let notifiedNearTurn = false;
let notifPermission = Notification.permission;
let pollInterval = null;
let lastStatus = null;
let secondsTillRefresh = 8;
const _soundPref = localStorage.getItem("qms_sound_enabled");
let soundEnabled = _soundPref === null ? true : _soundPref === "true";
let isOffline = false;

let audioContext = null;
let pendingSound = null;
let userHasInteracted = false;
let swRegistration = null;

let currentTab = "ticket";
let confettiFired = sessionStorage.getItem("confetti_fired") === "true";

function switchTab(tab) {
  currentTab = tab;
  document
    .querySelectorAll(".tab-pane")
    .forEach((el) => el.classList.remove("active"));
  document
    .querySelectorAll(".nav-item")
    .forEach((el) => el.classList.remove("active"));

  document.getElementById("tab-" + tab).classList.add("active");
  document.getElementById("nav-" + tab).classList.add("active");

  if (tab === "map") {
    if (window.CampusMap) window.CampusMap.init();
    setTimeout(() => {
      if (window._leafletMap) window._leafletMap.invalidateSize();
    }, 50);
  }
}

function ensureAudio() {
  if (!audioContext) {
    try {
      audioContext = new (window.AudioContext || window.webkitAudioContext)();
    } catch (e) {
      return false;
    }
  }
  return true;
}

function playNotificationSound(type = "alert") {
  if (!soundEnabled || !userHasInteracted) {
    if (!userHasInteracted) pendingSound = type;
    return;
  }
  if (!ensureAudio()) return;
  const play = () => {
    try {
      if (type === "alert") {
        const osc = audioContext.createOscillator(),
          gain = audioContext.createGain();
        osc.connect(gain);
        gain.connect(audioContext.destination);
        osc.type = "sine";
        osc.frequency.setValueAtTime(1000, audioContext.currentTime);
        gain.gain.setValueAtTime(1.0, audioContext.currentTime);
        gain.gain.exponentialRampToValueAtTime(
          0.001,
          audioContext.currentTime + 0.8,
        );
        osc.start(audioContext.currentTime);
        osc.stop(audioContext.currentTime + 0.8);
      } else if (type === "turn") {
        [0, 0.35, 0.7].forEach((offset) => {
          const osc = audioContext.createOscillator(),
            gain = audioContext.createGain();
          osc.connect(gain);
          gain.connect(audioContext.destination);
          osc.type = "sine";
          osc.frequency.setValueAtTime(1200, audioContext.currentTime + offset);
          gain.gain.setValueAtTime(1.0, audioContext.currentTime + offset);
          gain.gain.exponentialRampToValueAtTime(
            0.001,
            audioContext.currentTime + offset + 0.25,
          );
          osc.start(audioContext.currentTime + offset);
          osc.stop(audioContext.currentTime + offset + 0.25);
        });
      } else if (type === "complete") {
        [523, 659, 784].forEach((freq, i) => {
          const osc = audioContext.createOscillator(),
            gain = audioContext.createGain();
          osc.connect(gain);
          gain.connect(audioContext.destination);
          osc.type = "sine";
          osc.frequency.setValueAtTime(
            freq,
            audioContext.currentTime + i * 0.2,
          );
          gain.gain.setValueAtTime(1.0, audioContext.currentTime + i * 0.2);
          gain.gain.exponentialRampToValueAtTime(
            0.001,
            audioContext.currentTime + i * 0.2 + 0.4,
          );
          osc.start(audioContext.currentTime + i * 0.2);
          osc.stop(audioContext.currentTime + i * 0.2 + 0.4);
        });
      }
    } catch (e) {
      console.warn("Audio error:", e);
    }
  };
  if (audioContext.state === "suspended") audioContext.resume().then(play);
  else play();
}

function triggerHaptic(pattern = "medium") {
  if (!navigator.vibrate || !userHasInteracted) return;
  const p = {
    light: [10],
    medium: [30],
    strong: [50],
    alert: [20, 10, 20],
    celebration: [20, 10, 20, 10, 30],
  };
  navigator.vibrate(p[pattern] || p.medium);
}

window.addEventListener("online", () => {
  isOffline = false;
  document.getElementById("retryCard").style.display = "none";
  poll();
});
window.addEventListener("offline", () => {
  isOffline = true;
  document.getElementById("retryCard").style.display = "flex";
});

function createConfetti() {
  const icon = document.getElementById("doneIcon");
  icon.style.transform = "scale(0)";
  icon.style.transition = "transform .5s cubic-bezier(.34,1.56,.64,1)";
  setTimeout(() => {
    icon.style.transform = "scale(1)";
  }, 50);

  const colors = [
    "#e07b1a",
    "#008b43",
    "#1565c0",
    "#e53935",
    "#f5c518",
    "#9c27b0",
    "#00acc1",
    "#ff6f91",
  ];
  const canvas = document.createElement("canvas");
  canvas.style.cssText =
    "position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999";
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
  document.body.appendChild(canvas);
  const ctx = canvas.getContext("2d");

  const pieces = Array.from({ length: 120 }, () => ({
    x: Math.random() * canvas.width,
    y: -20 - Math.random() * 200,
    w: 8 + Math.random() * 6,
    h: 16 + Math.random() * 10,
    color: colors[Math.floor(Math.random() * colors.length)],
    vx: (Math.random() - 0.5) * 4,
    vy: 3 + Math.random() * 4,
    rot: Math.random() * Math.PI * 2,
    rotV: (Math.random() - 0.5) * 0.15,
    wobble: Math.random() * Math.PI * 2,
    wobbleV: 0.05 + Math.random() * 0.05,
  }));

  let frame;
  const animate = () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    let alive = false;
    pieces.forEach((p) => {
      p.x += p.vx;
      p.y += p.vy;
      p.vy += 0.1;
      p.rot += p.rotV;
      p.wobble += p.wobbleV;
      p.vx += Math.sin(p.wobble) * 0.05;
      if (p.y < canvas.height + 20) alive = true;
      ctx.save();
      ctx.translate(p.x, p.y);
      ctx.rotate(p.rot);
      ctx.fillStyle = p.color;
      ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
      ctx.restore();
    });
    if (alive) frame = requestAnimationFrame(animate);
    else canvas.remove();
  };
  animate();
  setTimeout(() => {
    cancelAnimationFrame(frame);
    canvas.remove();
  }, 4000);
}

window.addEventListener("DOMContentLoaded", () => {
  if ("serviceWorker" in navigator) {
    navigator.serviceWorker
      .register("scripts/sw.js")
      .then((reg) => {
        swRegistration = reg;
      })
      .catch((e) => console.warn("SW failed:", e));
  }

  initNotifCard();
  initControls();
  poll();
  startPollTimer();

  if (!navigator.onLine) {
    isOffline = true;
    document.getElementById("retryCard").style.display = "flex";
  }
});

function initControls() {
  const onFirstInteraction = () => {
    if (userHasInteracted) return;
    userHasInteracted = true;
    try {
      if (!audioContext)
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
      if (audioContext.state === "suspended") audioContext.resume();
      const osc = audioContext.createOscillator(),
        gain = audioContext.createGain();
      osc.connect(gain);
      gain.connect(audioContext.destination);
      gain.gain.setValueAtTime(0.001, audioContext.currentTime);
      osc.start(audioContext.currentTime);
      osc.stop(audioContext.currentTime + 0.1);
    } catch (e) {}
    if (pendingSound) {
      const s = pendingSound;
      pendingSound = null;
      setTimeout(() => playNotificationSound(s), 200);
    }
  };
  document.addEventListener("click", onFirstInteraction);
  document.addEventListener("touchstart", onFirstInteraction);

  let touchStartY = 0;
  document.addEventListener("touchstart", (e) => {
    touchStartY = e.touches[0].clientY;
  });
  document.addEventListener("touchend", (e) => {
    if (e.changedTouches[0].clientY > touchStartY && window.scrollY === 0)
      manualRetry();
  });
}

function manualRetry() {
  const btn = document.getElementById("manualRetryBtn");
  btn.disabled = true;
  btn.textContent = "Retrying…";
  poll().finally(() => {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-redo"></i> Retry';
  });
}

function startPollTimer() {
  clearInterval(pollInterval);
  secondsTillRefresh = 8;
  updateRefreshHint();
  pollInterval = setInterval(() => {
    secondsTillRefresh--;
    if (secondsTillRefresh <= 0) {
      poll();
      secondsTillRefresh = 8;
    }
    updateRefreshHint();
  }, 1000);
}

function updateRefreshHint() {
  const icon = document.getElementById("refreshIcon");
  const text = document.getElementById("refreshText");
  if (!icon || !text) return;
  if (secondsTillRefresh <= 0) {
    icon.classList.add("spin");
    text.textContent = "Refreshing…";
  } else {
    icon.classList.remove("spin");
    text.textContent = `Updates in ${secondsTillRefresh}s`;
  }
}

function poll() {
  return fetch(API_URL)
    .then((r) => r.json())
    .then((data) => {
      document.getElementById("loadingState").style.display = "none";
      document.getElementById("mainState").style.display = "block";
      if (!data.success) {
        showError(data.message || "Could not load queue info.");
        return;
      }
      render(data);
    })
    .catch(() => {
      if (navigator.onLine) {
        isOffline = true;
        document.getElementById("retryCard").style.display = "flex";
      }
      const t = document.getElementById("refreshText");
      if (t) t.textContent = "Connection error — retrying…";
    });
}

function render(data) {
  const {
    ticket_number,
    office_name,
    service_name,
    status,
    people_ahead,
    now_serving,
    est_wait_mins,
    expired,
    building_hint,
  } = data;

  document.getElementById("officeName").textContent = office_name;
  document.getElementById("serviceName").textContent = service_name;
  document.getElementById("ticketNumber").textContent = ticket_number;
  document
    .getElementById("ticketNumber")
    .setAttribute("aria-label", `Your ticket number is ${ticket_number}`);

  renderStatusPill(status, people_ahead);

  const stripe = document.getElementById("ticketStripe");
  stripe.className = "ticket-stripe";
  if (status === "serving") stripe.classList.add("stripe-serving");
  if (status === "completed") stripe.classList.add("stripe-completed");
  if (status === "skipped") stripe.classList.add("stripe-skipped");

  const numEl = document.getElementById("ticketNumber");
  numEl.className = "ticket-number";
  if (status === "serving") numEl.classList.add("serving-num");
  if (status === "completed" || status === "skipped")
    numEl.classList.add("done-num");

  if (window.CampusMap)
    window.CampusMap.setDestination(office_name, building_hint || "");

  if (expired || status === "completed" || status === "skipped") {
    renderDone(status);
    clearInterval(pollInterval);
    document.getElementById("refreshHint").style.display = "none";
    triggerHaptic("celebration");
    playNotificationSound("complete");
    if (!confettiFired) {
      confettiFired = true;
      sessionStorage.setItem("confetti_fired", "true");
      createConfetti();
    }
    return;
  }

  document.getElementById("doneState").style.display = "none";
  document.getElementById("ticketCard").style.display = "block";

  const statRow = document.getElementById("statRow");
  if (status === "serving") {
    statRow.style.display = "none";
  } else {
    statRow.style.display = "flex";
    const aheadEl = document.getElementById("peopleAhead");
    aheadEl.textContent = people_ahead === 0 ? "You're next!" : people_ahead;
    aheadEl.setAttribute(
      "aria-label",
      people_ahead === 0 ? "You are next" : `${people_ahead} people ahead`,
    );
    document.getElementById("estWait").textContent =
      est_wait_mins === 0 ? "Now" : `~${est_wait_mins}m`;
  }

  const nsBanner = document.getElementById("nowServingBanner");
  if (now_serving) {
    nsBanner.style.display = "flex";
    document.getElementById("nowServingNum").textContent = now_serving;
  } else {
    nsBanner.style.display = "none";
  }

  renderAlerts(status, people_ahead);

  if (lastStatus !== null && lastStatus !== "serving" && status === "serving") {
    sendNotification(
      "🔔 It's your turn!",
      `${ticket_number} — please proceed to the window now.`,
    );
  }
  checkNearTurnNotification(people_ahead);

  lastPeopleAhead = people_ahead;
  lastStatus = status;
}

function renderStatusPill(status, peopleAhead) {
  const pill = document.getElementById("statusPill");
  const map = {
    waiting:
      peopleAhead === 0
        ? ["pill-serving", "You're next!"]
        : ["pill-waiting", "Waiting"],
    serving: ["pill-serving", "Now serving you"],
    completed: ["pill-completed", "Completed"],
    skipped: ["pill-skipped", "Skipped"],
    "on-hold": ["pill-onhold", "On hold"],
  };
  const [cls, label] = map[status] || ["pill-waiting", status];
  pill.innerHTML = `<span class="status-pill ${cls}"><span class="pill-dot"></span>${label}</span>`;
}

function renderAlerts(status, peopleAhead) {
  const area = document.getElementById("alertArea");
  area.innerHTML = "";
  if (status === "serving") {
    area.innerHTML = `<div class="alert-strip alert-warn serving-alert">
      <i class="fas fa-circle-exclamation"></i>
      <span>It's your turn! Please proceed to the window now.</span></div>`;
    triggerHaptic("strong");
    if (lastStatus !== "serving") playNotificationSound("turn");
    return;
  }
  if (status === "on-hold") {
    area.innerHTML = `<div class="alert-strip alert-info">
      <i class="fas fa-pause-circle"></i>
      <span>Your ticket is on hold. Please wait for further updates.</span></div>`;
    return;
  }
  if (peopleAhead <= 5 && peopleAhead > 0) {
    area.innerHTML = `<div class="alert-strip alert-info">
      <i class="fas fa-bell"></i>
      <span><strong>${peopleAhead} ${peopleAhead === 1 ? "person" : "people"} ahead</strong> — head to the office now.</span></div>`;
    if (lastPeopleAhead === null || peopleAhead < lastPeopleAhead) {
      triggerHaptic("alert");
      playNotificationSound("alert");
    }
  }
}

function renderDone(status) {
  document.getElementById("ticketCard").style.display = "none";
  const doneEl = document.getElementById("doneState");
  doneEl.style.display = "block";
  doneEl.classList.add("completion");
  document.getElementById("alertArea").innerHTML = "";
  const title = document.getElementById("doneTitle");
  const sub = document.getElementById("doneSub");
  if (status === "completed") {
    title.textContent = "Transaction complete!";
    sub.innerHTML =
      "Your transaction has been completed.<br>Thank you for visiting OLFU.";
  } else if (status === "skipped") {
    title.textContent = "Ticket skipped";
    sub.innerHTML =
      "Your number was called but was skipped.<br>Please approach a staff member for assistance.";
  } else {
    title.textContent = "Queue closed";
    sub.innerHTML = "This ticket is no longer active.";
  }
}

function showError(msg) {
  document.getElementById("ticketCard").style.display = "none";
  document.getElementById("alertArea").innerHTML =
    `<div class="alert-strip alert-warn"><i class="fas fa-triangle-exclamation"></i> ${msg}</div>`;
}

function initNotifCard() {
  if (!("Notification" in window)) return;
  if (Notification.permission === "granted") {
    document.getElementById("notifCard").style.display = "none";
    return;
  }
  if (Notification.permission === "denied") return;
  document.getElementById("notifCard").style.display = "flex";
}

function requestNotifPermission() {
  if (!("Notification" in window)) return;
  const btn = document.getElementById("notifBtn");
  btn.disabled = true;
  btn.textContent = "Asking…";
  Notification.requestPermission().then((perm) => {
    notifPermission = perm;
    if (perm === "granted") {
      document.getElementById("notifCard").style.display = "none";
    } else {
      btn.disabled = false;
      btn.textContent = "Blocked";
    }
  });
}

function checkNearTurnNotification(peopleAhead) {
  if (
    notifiedNearTurn ||
    Notification.permission !== "granted" ||
    lastPeopleAhead === null
  )
    return;
  if (lastPeopleAhead > 5 && peopleAhead <= 5) {
    notifiedNearTurn = true;
    sendNotification(
      "⏰ Almost your turn!",
      `Only ${peopleAhead} ${peopleAhead === 1 ? "person" : "people"} ahead. Please make your way to the office.`,
    );
  }
}

function sendNotification(title, body) {
  if (Notification.permission !== "granted") return;
  if (swRegistration) {
    swRegistration
      .showNotification(title, {
        body,
        icon: "img/olfu-logo.png",
        badge: "img/olfu-logo.png",
        tag: "queue-tracker",
        renotify: true,
        vibrate: [200, 100, 200],
      })
      .catch((e) => console.warn("SW notif failed:", e));
    return;
  }
  try {
    const n = new Notification(title, {
      body,
      icon: "img/olfu-logo.png",
      tag: "queue-tracker",
      renotify: true,
    });
    n.onclick = () => {
      window.focus();
      n.close();
    };
  } catch (e) {}
}
