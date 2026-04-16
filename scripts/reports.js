Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.color = "#7a9387";

function updateTime() {
  const opts = { timeZone: "Asia/Manila" };
  const now = new Date();
  const day = now.toLocaleDateString("en-PH", { weekday: "long", ...opts });
  const date = now.toLocaleDateString("en-PH", {
    year: "numeric",
    month: "long",
    day: "numeric",
    ...opts,
  });
  const time = now.toLocaleTimeString("en-PH", {
    hour: "numeric",
    minute: "2-digit",
    hour12: true,
    ...opts,
  });
  const el = document.getElementById("currentTime");
  if (el) el.textContent = `${day}, ${date} at ${time}`;
}
updateTime();
setInterval(updateTime, 1000);

(function () {
  const today = new Date().toISOString().split("T")[0];
  const from = document.getElementById("dateFrom");
  const to = document.getElementById("dateTo");
  if (from) from.value = today;
  if (to) to.value = today;
})();

function formatLabel(from, to) {
  const fmt = (d) => {
    const [y, m, day] = d.split("-");
    return new Date(y, m - 1, day).toLocaleDateString("en-PH", {
      month: "long",
      day: "numeric",
      year: "numeric",
    });
  };
  return from === to ? fmt(from) : `${fmt(from)} — ${fmt(to)}`;
}

function statCard(label, value, sub, colorClass, isMuted, isNa) {
  const mutedClass = isMuted ? " stat-muted" : "";
  const naClass = isNa ? " stat-na" : "";
  return `
        <div class="summary-stat ${colorClass}">
            <div class="summary-stat-label">${label}</div>
            <div class="summary-stat-value${mutedClass}${naClass}">${value}</div>
            <div class="summary-stat-sub">${sub}</div>
        </div>`;
}

function renderStaffCards(d) {
  const total = d.served + d.skipped;
  const rate = total > 0 ? Math.round((d.served / total) * 100) + "%" : "N/A";
  const avg = d.avg_minutes !== null ? d.avg_minutes + " min" : "N/A";
  const noData = total === 0;

  return (
    statCard(
      "Students Served",
      d.served,
      "Tickets completed",
      "stat-green",
      noData,
      false,
    ) +
    statCard(
      "Students Skipped",
      d.skipped,
      "No-shows on your watch",
      "stat-orange",
      noData,
      false,
    ) +
    statCard(
      "Avg. Processing Time",
      avg,
      "Per transaction handled",
      "stat-blue",
      avg === "N/A",
      avg === "N/A",
    ) +
    statCard(
      "Completion Rate",
      rate,
      "Served / total called",
      "stat-green",
      rate === "N/A",
      rate === "N/A",
    )
  );
}

function setStaffBlock(d, from, to) {
  const total = d.served + d.skipped;
  const hasData = total > 0;

  document.getElementById("staffResultsLabel").textContent =
    `Summary — ${formatLabel(from, to)}`;

  const pill = document.getElementById("staffResultsPill");
  pill.textContent = hasData ? "Has Data" : "No Data";
  pill.className = "pill " + (hasData ? "pill-green" : "pill-orange");

  const loading = document.getElementById("staffResultsLoading");
  const cards = document.getElementById("staffResultsCards");
  loading.style.display = "none";
  cards.style.display = "";

  if (!hasData) {
    cards.innerHTML = `
            <div class="no-data-notice" style="grid-column:1/-1; margin-bottom:0;">
                <i class="fas fa-info-circle"></i>
                No transactions found for this period.
            </div>`;
  } else {
    cards.innerHTML = renderStaffCards(d);
  }
}

async function applyStaffFilters() {
  const from = document.getElementById("dateFrom").value;
  const to = document.getElementById("dateTo").value;

  if (!from || !to) {
    alert("Please select both a start and end date.");
    return;
  }
  if (from > to) {
    alert("'Date From' cannot be after 'Date To'.");
    return;
  }

  const btn = document.getElementById("applyBtn");
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading…';
  btn.disabled = true;

  const loading = document.getElementById("staffResultsLoading");
  const cards = document.getElementById("staffResultsCards");
  loading.style.display = "";
  cards.style.display = "none";
  document.getElementById("staffResultsLabel").textContent = "Loading…";
  document.getElementById("staffResultsPill").textContent = "";

  let data;
  try {
    const res = await fetch("api/reports_actions.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "staff_metrics",
        date_from: from,
        date_to: to,
        csrf_token: CSRF_TOKEN,
      }),
    });
    data = await res.json();
  } catch (e) {
    btn.innerHTML = '<i class="fas fa-search"></i> Apply';
    btn.disabled = false;
    alert("Network error. Please try again.");
    return;
  }

  btn.innerHTML = '<i class="fas fa-search"></i> Apply';
  btn.disabled = false;

  if (!data.success) {
    alert(data.message ?? "Failed to load metrics.");
    return;
  }

  setStaffBlock(data.metrics, from, to);
}

function applyFilters() {
  const btn = document.querySelector(".btn-apply");
  if (!btn) return;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying…';
  btn.disabled = true;
  setTimeout(() => {
    btn.innerHTML = '<i class="fas fa-search"></i> Apply';
    btn.disabled = false;
  }, 1000);
}

function exportReport(type) {
  if (type === "pdf") window.print();
}

if (IS_STAFF) {
  const today = new Date().toISOString().split("T")[0];
  fetch("api/reports_actions.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      action: "staff_metrics",
      date_from: today,
      date_to: today,
      csrf_token: CSRF_TOKEN,
    }),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) setStaffBlock(data.metrics, today, today);
    })
    .catch(() => {
      document.getElementById("staffResultsLoading").textContent =
        "Failed to load. Try refreshing.";
    });
}
