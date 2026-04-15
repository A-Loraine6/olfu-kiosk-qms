(function () {
    const today = new Date().toISOString().split("T")[0];
    if (document.getElementById("dateFrom")) document.getElementById("dateFrom").value = today;
    if (document.getElementById("dateTo"))   document.getElementById("dateTo").value   = today;
})();

async function applyStaffFilters() {
    const from = document.getElementById("dateFrom").value;
    const to   = document.getElementById("dateTo").value;
    if (!from || !to) return;

    const btn = document.querySelector(".btn-apply");
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    btn.disabled  = true;

    const res  = await fetch("api/reports_actions.php", {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ action: "staff_metrics", date_from: from, date_to: to, csrf_token: CSRF_TOKEN }),
    });
    const data = await res.json();

    btn.innerHTML = '<i class="fas fa-search"></i> Apply';
    btn.disabled  = false;

    if (!data.success) return;

    const d      = data.metrics;
    const total  = d.served + d.skipped;
    const rate   = total > 0 ? Math.round((d.served / total) * 100) + "%" : "—";
    const avg    = d.avg_minutes !== null ? d.avg_minutes + " min" : "—";
    const label  = from === to ? from : `${from} → ${to}`;

    document.getElementById("staffFilteredLabel").textContent = `Summary: ${label}`;
    document.getElementById("staffFilteredPill").textContent  = d.served > 0 ? "Has Data" : "No Data";
    document.getElementById("staffFilteredPill").className    = "pill " + (d.served > 0 ? "pill-green" : "pill-orange");

    document.getElementById("staffFilteredCards").innerHTML = `
        <div class="summary-stat stat-green">
            <div class="summary-stat-label">Students Served</div>
            <div class="summary-stat-value">${d.served}</div>
            <div class="summary-stat-sub">For selected period</div>
        </div>
        <div class="summary-stat stat-orange">
            <div class="summary-stat-label">Students Skipped</div>
            <div class="summary-stat-value">${d.skipped}</div>
            <div class="summary-stat-sub">For selected period</div>
        </div>
        <div class="summary-stat stat-blue">
            <div class="summary-stat-label">Avg. Processing Time</div>
            <div class="summary-stat-value">${avg}</div>
            <div class="summary-stat-sub">Per transaction handled</div>
        </div>
        <div class="summary-stat stat-green">
            <div class="summary-stat-label">Completion Rate</div>
            <div class="summary-stat-value">${rate}</div>
            <div class="summary-stat-sub">Served / total called</div>
        </div>`;

    document.getElementById("staffFilteredStats").style.display = "block";
}
function updateTime() {
  const opts = {
    timeZone: "Asia/Manila",
  };
  const now = new Date();
  const day = now.toLocaleDateString("en-PH", {
    weekday: "long",
    ...opts,
  });
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
  document.getElementById("currentTime").textContent =
    `${day}, ${date} at ${time}`;
}
updateTime();
setInterval(updateTime, 1000);

(function () {
  const today = new Date().toISOString().split("T")[0];
  document.getElementById("dateFrom").value = today;
  document.getElementById("dateTo").value = today;
})();

function applyFilters() {
  const from = document.getElementById("dateFrom").value;
  const to = document.getElementById("dateTo").value;
  const office = document.getElementById("officeFilter").value;

  const btn = document.querySelector(".btn-apply");
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';

  setTimeout(() => {
    btn.innerHTML = '<i class="fas fa-search"></i> Apply';
  }, 1000);
}

Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.color = "#7a9387";
