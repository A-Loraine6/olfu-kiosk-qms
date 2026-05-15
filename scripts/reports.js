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
  ["dateFrom", "dateTo"].forEach((id) => {
    const el = document.getElementById(id);
    if (el) {
      el.value = today;
      el.max = today;
    }
  });
})();

function showToast(message, type = "success") {
  let toast = document.getElementById("reportsToast");
  if (!toast) {
    toast = document.createElement("div");
    toast.id = "reportsToast";
    toast.className = "reports-toast";
    toast.innerHTML = `<div class="toast-dot"></div><span id="reportsToastMsg"></span>`;
    document.body.appendChild(toast);
  }
  toast.className = `reports-toast toast-${type}`;
  document.getElementById("reportsToastMsg").textContent = message;
  void toast.offsetWidth;
  toast.classList.add("show");
  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => toast.classList.remove("show"), 3500);
}

function setRange(preset) {
  const today = new Date();
  const fmt = (d) =>
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;

  let from, to;

  const dayOfWeek = today.getDay();
  const diffToMon = (dayOfWeek + 6) % 7;

  switch (preset) {
    case "today":
      from = to = fmt(today);
      break;
    case "yesterday": {
      const y = new Date(today);
      y.setDate(today.getDate() - 1);
      from = to = fmt(y);
      break;
    }
    case "week": {
      const mon = new Date(today);
      mon.setDate(today.getDate() - diffToMon);
      from = fmt(mon);
      to = fmt(today);
      break;
    }
    case "lastweek": {
      const lMon = new Date(today);
      lMon.setDate(today.getDate() - diffToMon - 7);
      const lSun = new Date(today);
      lSun.setDate(today.getDate() - diffToMon - 1);
      from = fmt(lMon);
      to = fmt(lSun);
      break;
    }
    case "month": {
      const m = new Date(today.getFullYear(), today.getMonth(), 1);
      from = `${m.getFullYear()}-${String(m.getMonth() + 1).padStart(2, "0")}-${String(m.getDate()).padStart(2, "0")}`;
      to = fmt(today);
      break;
    }
    default:
      return;
  }

  const fromEl = document.getElementById("dateFrom");
  const toEl = document.getElementById("dateTo");
  if (fromEl) fromEl.value = from;
  if (toEl) toEl.value = to;

  document.querySelectorAll(".range-pill").forEach((p) => {
    p.classList.toggle("active", p.dataset.range === preset);
  });

  if (IS_PERSONAL_VIEW) {
    applyStaffFilters();
  } else {
    applyFilters();
  }
}

function skeletonCard() {
  return `
    <div class="skeleton-card">
      <div class="skel skel-sm" style="width:55%"></div>
      <div class="skel skel-lg" style="width:45%"></div>
      <div class="skel skel-sm" style="width:75%"></div>
      <div class="skel skel-sm" style="width:40%"></div>
    </div>`;
}

function showSkeletonRow(containerId, cols = 4) {
  const el = document.getElementById(containerId);
  if (!el) return;
  el.innerHTML = Array(cols).fill(skeletonCard()).join("");
  el.className = "skeleton-row";
  el.style.display = "";
}

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

function trendBadge(pct) {
  if (pct === 0 || pct === null || pct === undefined)
    return `<span class="trend-badge trend-neutral"><i class="fas fa-minus"></i> No change</span>`;
  const up = pct > 0;
  const icon = up ? "fa-arrow-up" : "fa-arrow-down";
  const cls = up ? "trend-up" : "trend-down";
  return `<span class="trend-badge ${cls}"><i class="fas ${icon}"></i> ${Math.abs(pct)}% vs prev period</span>`;
}

function statCard(label, value, sub, colorClass, isMuted, isNa, trendPct) {
  const mutedClass = isMuted ? " stat-muted" : "";
  const naClass = isNa ? " stat-na" : "";
  const trend =
    trendPct !== undefined && trendPct !== null ? trendBadge(trendPct) : "";
  return `
    <div class="summary-stat ${colorClass}">
      <div class="summary-stat-label">${label}</div>
      <div class="summary-stat-value${mutedClass}${naClass}">${value}</div>
      <div class="summary-stat-sub">${sub}</div>
      ${trend}
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

  document.getElementById("staffResultsLoading").style.display = "none";
  const cards = document.getElementById("staffResultsCards");
  cards.className = "daily-summary-row";
  cards.style.display = "";

  if (!hasData) {
    cards.innerHTML = `
      <div class="no-data-notice" style="grid-column:1/-1;margin-bottom:0;">
        <i class="fas fa-info-circle"></i> No transactions found for this period.
      </div>`;
  } else {
    cards.innerHTML = renderStaffCards(d);
  }
}

function renderDailyReport(data) {
  const block = document.getElementById("block-daily");
  if (!block) return;

  const hasData = data.total_queued > 0;
  const pill = block.querySelector(".pill");
  pill.textContent = hasData ? "Has Data" : "No Data";
  pill.className = "pill " + (hasData ? "pill-green" : "pill-orange");

  const container = block.querySelector(".daily-summary-row");
  if (container) {
    container.style.display = "";
    container.innerHTML =
      statCard(
        "Total Queued",
        data.total_queued,
        "Students in queue",
        "stat-green",
        false,
        false,
        data.queued_trend,
      ) +
      statCard(
        "Total Served",
        data.total_served,
        "Completed transactions",
        "stat-green",
        false,
        false,
        data.served_trend,
      ) +
      statCard(
        "Total Skipped",
        data.total_skipped,
        "No-show tickets",
        "stat-orange",
        false,
        false,
      ) +
      statCard(
        "Completion Rate",
        Math.round(data.completion_rate) + "%",
        "Served / Total queued",
        "stat-blue",
        false,
        false,
      );
  }
}

let _officeSortCol = -1;
let _officeSortAsc = false;
let _officeData = [];

function renderOfficePerformance(data) {
  const block = document.getElementById("block-office");
  if (!block) return;

  _officeData = data;
  _officeSortCol = -1;
  _officeSortAsc = false;

  const hasData = data.some((d) => d.total_transactions > 0);
  const table = block.querySelector(".perf-table");
  const emptyState = block.querySelector(".empty-state");
  const pill = block.querySelector(".pill");

  pill.textContent = hasData ? "Has Data" : "No Data";
  pill.className = "pill " + (hasData ? "pill-green" : "pill-orange");

  if (emptyState) emptyState.style.display = hasData ? "none" : "";

  if (table) {
    table.style.display = hasData ? "" : "none";
    if (hasData) {
      _buildOfficeTable(table, _officeData, _officeSortCol, _officeSortAsc);

      table.querySelectorAll("thead th").forEach((th, i) => {
        const newTh = th.cloneNode(true);
        if (!newTh.querySelector(".sort-icon")) {
          newTh.innerHTML += ` <span class="sort-icon">↕</span>`;
        }
        newTh.addEventListener("click", () => {
          if (_officeSortCol === i) _officeSortAsc = !_officeSortAsc;
          else {
            _officeSortCol = i;
            _officeSortAsc = false;
          }
          _buildOfficeTable(table, _officeData, _officeSortCol, _officeSortAsc);
        });
        th.parentNode.replaceChild(newTh, th);
      });
    }

    table.querySelectorAll("thead th").forEach((th, i) => {
      const newTh = th.cloneNode(true);
      if (!newTh.querySelector(".sort-icon")) {
        newTh.innerHTML += ` <span class="sort-icon">↕</span>`;
      }
      newTh.addEventListener("click", () => {
        if (_officeSortCol === i) _officeSortAsc = !_officeSortAsc;
        else {
          _officeSortCol = i;
          _officeSortAsc = false;
        }
        _buildOfficeTable(table, _officeData, _officeSortCol, _officeSortAsc);
      });
      th.parentNode.replaceChild(newTh, th);
    });
  }
}

function _buildOfficeTable(table, data, sortCol, sortAsc) {
  table.querySelectorAll("thead th").forEach((th, i) => {
    const icon = th.querySelector(".sort-icon");
    if (!icon) return;
    th.classList.toggle("sorted", i === sortCol);
    icon.textContent = i === sortCol ? (sortAsc ? "↑" : "↓") : "↕";
  });

  let rows = [...data];
  if (sortCol >= 0) {
    rows.sort((a, b) => {
      const keys = [
        "name",
        "served",
        "skipped",
        "avg_service_time",
        "volume_share",
        "total_transactions",
      ];
      const key = keys[sortCol] ?? "name";
      const av = a[key];
      const bv = b[key];

      let cmp;
      if (typeof av === "number" && typeof bv === "number") {
        cmp = av - bv;
      } else {
        cmp = String(av).localeCompare(String(bv));
      }

      return sortAsc ? cmp : -cmp;
    });
  }

  const tbody = table.querySelector("tbody");
  tbody.innerHTML = "";
  rows.forEach((office) => {
    let statusClass, statusLabel;
    if (office.total_transactions === 0) {
      statusClass = "status-inactive";
      statusLabel = "Inactive";
    } else if (office.volume_share > 30) {
      statusClass = "status-high";
      statusLabel = "High";
    } else if (office.volume_share > 15) {
      statusClass = "status-medium";
      statusLabel = "Normal";
    } else {
      statusClass = "status-low";
      statusLabel = "Low";
    }

    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${office.name}</td>
      <td>${office.served}</td>
      <td>${office.skipped}</td>
      <td>${office.avg_service_time > 0 ? office.avg_service_time + " min" : "—"}</td>
      <td>${office.volume_share.toFixed(1)}%</td>
      <td><span class="status-badge ${statusClass}">${statusLabel}</span></td>`;
    tbody.appendChild(tr);
  });
}

let peakHoursChart = null;

function renderPeakHours(data, openHour = 8, closeHour = 17) {
  const block = document.getElementById("block-peaks");
  if (!block) return;

  const hasData = data.some((d) => d > 0);
  const canvas = block.querySelector("#peakHoursChart");
  const emptyState = canvas
    ?.closest(".sub-chart-box")
    ?.querySelector(".empty-state");
  const pill = block.querySelector(".pill");

  if (pill) {
    pill.textContent = hasData ? "Has Data" : "No Data";
    pill.className = "pill " + (hasData ? "pill-green" : "pill-orange");
  }

  if (!hasData) {
    if (emptyState) emptyState.style.display = "";
    if (canvas) canvas.style.display = "none";
    return;
  }

  if (emptyState) emptyState.style.display = "none";
  if (canvas) canvas.style.display = "";

  const hours = Array.from(
    { length: closeHour - openHour + 1 },
    (_, i) => String(openHour + i).padStart(2, "0") + ":00",
  );
  const ctx = canvas.getContext("2d");
  if (peakHoursChart) peakHoursChart.destroy();
  peakHoursChart = new Chart(ctx, {
    type: "line",
    data: {
      labels: hours,
      datasets: [
        {
          label: "Queue Volume",
          data,
          borderColor: "#008b43",
          backgroundColor: "rgba(0,139,67,0.1)",
          tension: 0.3,
          fill: true,
          pointRadius: 4,
          pointBackgroundColor: "#008b43",
          pointBorderColor: "#008b43",
          pointBorderWidth: 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } },
      },
    },
  });
}

let deptChart = null;

function renderDayBreakdown(data) {
  const dayOrder = [
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
    "Sunday",
  ];
  data = [...data].sort(
    (a, b) => dayOrder.indexOf(a.day) - dayOrder.indexOf(b.day),
  );

  const canvas = document.getElementById("busyDaysChart");
  if (!canvas) return;

  const box = canvas.closest(".sub-chart-box");
  const existing = box?.querySelector(".day-breakdown-list");
  if (existing) existing.remove();
  const emptyState = box?.querySelector(".empty-state");
  if (emptyState) emptyState.style.display = "none";
  canvas.style.display = "";

  const ctx = canvas.getContext("2d");
  if (window.busyDaysChartInstance) window.busyDaysChartInstance.destroy();

  window.busyDaysChartInstance = new Chart(ctx, {
    type: "bar",
    data: {
      labels: data.map((d) => d.day.substring(0, 3)),
      datasets: [
        {
          label: "Queue Volume",
          data: data.map((d) => d.count),
          backgroundColor: "rgba(0,139,67,0.8)",
          borderColor: "#008b43",
          borderWidth: 0,
          borderRadius: 6,
          maxBarThickness: 40,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        title: {
          display: true,
          text: "Queue Volume by Day",
          font: { size: 11, weight: "700" },
          color: "#7a9387",
          padding: { bottom: 10 },
        },
        tooltip: { callbacks: { label: (ctx) => ` ${ctx.parsed.y} tickets` } },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1 },
          grid: { color: "rgba(0,0,0,0.05)" },
        },
        x: {
          grid: { display: false },
          ticks: { font: { size: 11, weight: "600" } },
        },
      },
    },
  });
}

function renderTransactionTypes(data) {
  const block = document.getElementById("block-types");
  if (!block) return;

  const hasData = data.length > 0 && data.some((d) => d.total > 0);
  const pill = document.getElementById("typesPill");
  const content = document.getElementById("typesContent");
  const emptyState = document.getElementById("emptyTypes");

  pill.textContent = hasData ? "Has Data" : "No Data";
  pill.className = "pill " + (hasData ? "pill-green" : "pill-orange");

  if (!hasData) {
    if (content) content.style.display = "none";
    if (emptyState) emptyState.style.display = "";
    return;
  }

  if (content) content.style.display = "";
  if (emptyState) emptyState.style.display = "none";

  const canvas = document.getElementById("transactionTypesChart");
  if (canvas) {
    const ctx = canvas.getContext("2d");
    if (window.transactionTypesChart) window.transactionTypesChart.destroy();
    window.transactionTypesChart = new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: data.map((d) => d.type),
        datasets: [
          {
            data: data.map((d) => d.total),
            backgroundColor: [
              "#ec4899",
              "#f59e0b",
              "#10b981",
              "#3b82f6",
              "#8b5cf6",
              "#06b6d4",
            ],
            borderColor: "#fff",
            borderWidth: 2,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { position: "bottom" } },
      },
    });
  }

  const tbody = document.querySelector("#typesTable tbody");
  if (tbody) {
    tbody.innerHTML = "";
    data.forEach((d) => {
      const row = document.createElement("tr");
      row.innerHTML = `
        <td>${d.type || "—"}</td>
        <td>${d.total}</td>
        <td>${d.completed}</td>
        <td>${d.skipped}</td>
        <td>${d.waiting}</td>`;
      tbody.appendChild(row);
    });
  }
}

let wowChart = null;

async function fetchAndRenderWoW(officeId = null) {
  const block = document.getElementById("block-wow");
  const loading = document.getElementById("wowLoading");
  const content = document.getElementById("wowContent");
  const pill = document.getElementById("wowPill");
  if (!block) return;

  loading.style.display = "";
  content.style.display = "none";

  const todayDate = new Date();
  const dayOfWeek = todayDate.getDay();
  const diffToMon = (dayOfWeek + 6) % 7;

  const thisMonday = new Date(todayDate);
  thisMonday.setDate(todayDate.getDate() - diffToMon);
  const thisWeekFrom = thisMonday.toISOString().split("T")[0];
  const thisWeekTo = todayDate.toISOString().split("T")[0];

  const lastMonday = new Date(thisMonday);
  lastMonday.setDate(thisMonday.getDate() - 7);
  const lastSunday = new Date(thisMonday);
  lastSunday.setDate(thisMonday.getDate() - 1);
  const lastWeekFrom = lastMonday.toISOString().split("T")[0];
  const lastWeekTo = lastSunday.toISOString().split("T")[0];

  try {
    const [thisRes, lastRes] = await Promise.all([
      fetch("api/reports_actions.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "daily_report",
          date_from: thisWeekFrom,
          date_to: thisWeekTo,
          office_id: officeId,
          csrf_token: CSRF_TOKEN,
        }),
      }),
      fetch("api/reports_actions.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "daily_report",
          date_from: lastWeekFrom,
          date_to: lastWeekTo,
          office_id: officeId,
          csrf_token: CSRF_TOKEN,
        }),
      }),
    ]);

    const thisData = await thisRes.json();
    const lastData = await lastRes.json();
    if (!thisData.success || !lastData.success) {
      loading.style.display = "none";
      return;
    }

    const tw = thisData.report;
    const lw = lastData.report;
    loading.style.display = "none";
    content.style.display = "";

    const hasAny = tw.total_queued > 0 || lw.total_queued > 0;
    pill.textContent = hasAny ? "Has Data" : "No Data";
    pill.className = "pill " + (hasAny ? "pill-green" : "pill-orange");

    const delta = (a, b) =>
      b > 0 ? Math.round(((a - b) / b) * 100) : a > 0 ? 100 : 0;
    const diffSign = (n) =>
      n > 0
        ? `<span class="wow-delta wow-up">▲ ${n}%</span>`
        : n < 0
          ? `<span class="wow-delta wow-down">▼ ${Math.abs(n)}%</span>`
          : `<span class="wow-delta wow-flat">— 0%</span>`;

    const metrics = [
      {
        label: "Total Queued",
        icon: "fa-users",
        tw: tw.total_queued,
        lw: lw.total_queued,
        color: "stat-green",
      },
      {
        label: "Total Served",
        icon: "fa-check-circle",
        tw: tw.total_served,
        lw: lw.total_served,
        color: "stat-green",
      },
      {
        label: "Total Skipped",
        icon: "fa-user-slash",
        tw: tw.total_skipped,
        lw: lw.total_skipped,
        color: "stat-orange",
      },
      {
        label: "Completion Rate",
        icon: "fa-percentage",
        tw: Math.round(tw.completion_rate) + "%",
        lw: Math.round(lw.completion_rate) + "%",
        twRaw: tw.completion_rate,
        lwRaw: lw.completion_rate,
        color: "stat-blue",
        isRate: true,
      },
    ];

    document.getElementById("wowCards").innerHTML = metrics
      .map((m) => {
        const pct = m.isRate
          ? delta(m.twRaw, m.lwRaw)
          : delta(
              typeof m.tw === "string" ? parseInt(m.tw) : m.tw,
              typeof m.lw === "string" ? parseInt(m.lw) : m.lw,
            );
        return `
        <div class="wow-card ${m.color}">
          <div class="wow-card-icon"><i class="fas ${m.icon}"></i></div>
          <div class="wow-card-label">${m.label}</div>
          <div class="wow-weeks">
            <div class="wow-week"><div class="wow-week-label">This Week</div><div class="wow-week-value">${m.tw}</div></div>
            <div class="wow-divider"></div>
            <div class="wow-week"><div class="wow-week-label">Last Week</div><div class="wow-week-value wow-muted">${m.lw}</div></div>
          </div>
          <div class="wow-trend">${diffSign(pct)}</div>
        </div>`;
      })
      .join("");

    await renderWowChart(
      thisWeekFrom,
      thisWeekTo,
      lastWeekFrom,
      lastWeekTo,
      officeId,
    );
  } catch (e) {
    console.error("WoW fetch error:", e);
    loading.style.display = "none";
  }
}

async function renderWowChart(twFrom, twTo, lwFrom, lwTo, officeId) {
  const canvas = document.getElementById("wowChart");
  if (!canvas) return;

  const fetchBreakdown = async (from, to) => {
    const res = await fetch("api/reports_actions.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "day_of_week_volume",
        date_from: from,
        date_to: to,
        office_id: officeId,
        csrf_token: CSRF_TOKEN,
      }),
    });
    const data = await res.json();
    return data.success ? data.day_of_week_volume : [];
  };

  const [twDays, lwDays] = await Promise.all([
    fetchBreakdown(twFrom, twTo),
    fetchBreakdown(lwFrom, lwTo),
  ]);

  const dayOrder = [
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
    "Sunday",
  ];
  const toMap = (arr) => Object.fromEntries(arr.map((d) => [d.day, d.count]));
  const twMap = toMap(twDays),
    lwMap = toMap(lwDays);
  const labels = dayOrder.map((d) => d.substring(0, 3));
  const twCounts = dayOrder.map((d) => twMap[d] ?? 0);
  const lwCounts = dayOrder.map((d) => lwMap[d] ?? 0);

  const ctx = canvas.getContext("2d");
  if (wowChart) wowChart.destroy();

  wowChart = new Chart(ctx, {
    type: "bar",
    data: {
      labels,
      datasets: [
        {
          label: "This Week",
          data: twCounts,
          backgroundColor: "rgba(0,139,67,0.85)",
          borderColor: "#008b43",
          borderWidth: 0,
          borderRadius: 5,
          maxBarThickness: 32,
        },
        {
          label: "Last Week",
          data: lwCounts,
          backgroundColor: "rgba(0,139,67,0.22)",
          borderColor: "rgba(0,139,67,0.4)",
          borderWidth: 1,
          borderRadius: 5,
          maxBarThickness: 32,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          position: "top",
          labels: { boxWidth: 12, font: { size: 11 } },
        },
        tooltip: { mode: "index", intersect: false },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1 },
          grid: { color: "rgba(0,0,0,0.05)" },
        },
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
      },
    },
  });
}

async function fetchAndRenderDailyReport(from, to, officeId = null) {
  try {
    const res = await fetch("api/reports_actions.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "daily_report",
        date_from: from,
        date_to: to,
        office_id: officeId,
        csrf_token: CSRF_TOKEN,
      }),
    });
    const data = await res.json();
    if (data.success) renderDailyReport(data.report);
  } catch (e) {
    console.error("Error fetching daily report:", e);
  }
}

async function fetchAndRenderOfficePerformance(from, to, officeId = null) {
  try {
    const res = await fetch("api/reports_actions.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "office_performance",
        date_from: from,
        date_to: to,
        office_id: officeId,
        csrf_token: CSRF_TOKEN,
      }),
    });
    const data = await res.json();
    if (data.success) renderOfficePerformance(data.performance);
  } catch (e) {
    console.error("Error fetching office performance:", e);
  }
}

async function fetchAndRenderPeakHours(from, to, officeId = null) {
  try {
    const res = await fetch("api/reports_actions.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "peak_hours",
        date_from: from,
        date_to: to,
        office_id: officeId,
        csrf_token: CSRF_TOKEN,
      }),
    });
    const data = await res.json();
    if (data.success)
      renderPeakHours(
        data.peak_hours,
        data.open_hour ?? 8,
        data.close_hour ?? 17,
      );
  } catch (e) {
    console.error("Error fetching peak hours:", e);
  }
}

async function fetchAndRenderDepartmentDemand(from, to, officeId = null) {
  try {
    const res = await fetch("api/reports_actions.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "department_demand",
        date_from: from,
        date_to: to,
        office_id: officeId,
        csrf_token: CSRF_TOKEN,
      }),
    });
    const data = await res.json();
    if (data.success) renderDepartmentDemand(data.department_demand);
  } catch (e) {
    console.error("Error fetching department demand:", e);
  }
}

async function fetchAndRenderDayBreakdown(from, to, officeId = null) {
  try {
    const res = await fetch("api/reports_actions.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "day_of_week_volume",
        date_from: from,
        date_to: to,
        office_id: officeId,
        csrf_token: CSRF_TOKEN,
      }),
    });
    const data = await res.json();
    if (data.success && data.day_of_week_volume?.length > 0) {
      renderDayBreakdown(data.day_of_week_volume);
    } else {
      const canvas = document.getElementById("busyDaysChart");
      const empty = canvas
        ?.closest(".sub-chart-box")
        ?.querySelector(".empty-state");
      if (empty) empty.style.display = "";
      if (canvas) canvas.style.display = "none";
    }
  } catch (e) {
    console.error("Error fetching day breakdown:", e);
  }
}

async function fetchAndRenderTransactionTypes(from, to, officeId = null) {
  try {
    const res = await fetch("api/reports_actions.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "transaction_types",
        date_from: from,
        date_to: to,
        office_id: officeId,
        csrf_token: CSRF_TOKEN,
      }),
    });
    const data = await res.json();
    if (data.success) renderTransactionTypes(data.transaction_types);
  } catch (e) {
    console.error("Error fetching transaction types:", e);
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
  if (btn) {
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading…';
    btn.disabled = true;
  }

  const loading = document.getElementById("staffResultsLoading");
  const cards = document.getElementById("staffResultsCards");
  if (loading) loading.style.display = "";
  if (cards) {
    cards.style.display = "none";
  }
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
    if (btn) {
      btn.innerHTML = '<i class="fas fa-search"></i> Apply';
      btn.disabled = false;
    }
    showToast("Network error. Please try again.", "error");
    return;
  }

  if (btn) {
    btn.innerHTML = '<i class="fas fa-search"></i> Apply';
    btn.disabled = false;
  }
  if (!data.success) {
    showToast(data.message ?? "Failed to load metrics.", "error");
    return;
  }
  setStaffBlock(data.metrics, from, to);
}

function applyFilters() {
  const from = document.getElementById("dateFrom").value;
  const to = document.getElementById("dateTo").value;
  const officeId =
    SCOPED_OFFICE_ID ??
    (document.getElementById("officeFilter")?.value || null);

  if (!from || !to) {
    alert("Please select both a start and end date.");
    return;
  }
  if (from > to) {
    alert("'Date From' cannot be after 'Date To'.");
    return;
  }

  const btn = document.querySelector(".btn-apply");
  if (btn) {
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying…';
    btn.disabled = true;
  }

  Promise.all([
    fetchAndRenderWoW(officeId),
    fetchAndRenderDailyReport(from, to, officeId),
    fetchAndRenderOfficePerformance(from, to, officeId),
    fetchAndRenderPeakHours(from, to, officeId),
    fetchAndRenderDepartmentDemand(from, to, officeId),
    fetchAndRenderDayBreakdown(from, to, officeId),
    fetchAndRenderTransactionTypes(from, to, officeId),
  ]).finally(() => {
    if (btn) {
      btn.innerHTML = '<i class="fas fa-search"></i> View Report';
      btn.disabled = false;
    }
  });
}

async function exportReport(type) {
  if (type !== "pdf") return;

  const from =
    document.getElementById("dateFrom")?.value ||
    new Date().toISOString().split("T")[0];
  const to =
    document.getElementById("dateTo")?.value ||
    new Date().toISOString().split("T")[0];
  const officeId =
    SCOPED_OFFICE_ID ?? (document.getElementById("officeFilter")?.value || "");

  const btn = document.querySelector(".btn-export");
  const progWrap = btn?.querySelector(".export-progress-bar");

  if (btn) {
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating…';
    btn.disabled = true;
  }

  if (progWrap) {
    progWrap.style.display = "block";
    let w = 0;
    const iv = setInterval(() => {
      w = Math.min(w + Math.random() * 15, 90);
      progWrap.style.width = Math.round(w) + "%";
    }, 200);
    progWrap._interval = iv;
  }

  try {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "api/export_pdf.php";
    form.target = "_blank";
    Object.entries({
      date_from: from,
      date_to: to,
      office_id: officeId,
      csrf_token: CSRF_TOKEN,
    }).forEach(([k, v]) => {
      const inp = document.createElement("input");
      inp.type = "hidden";
      inp.name = k;
      inp.value = v;
      form.appendChild(inp);
    });
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    if (progWrap) {
      clearInterval(progWrap._interval);
      progWrap.style.width = "100%";
      setTimeout(() => {
        progWrap.style.width = "0%";
        progWrap.style.display = "none";
      }, 600);
    }
    showToast("PDF is being generated — check your downloads.", "success");
  } catch (e) {
    showToast("Export failed. Please try again.", "error");
  } finally {
    setTimeout(() => {
      if (btn) {
        btn.innerHTML = '<i class="fas fa-file-pdf"></i> Export as PDF';
        btn.disabled = false;
      }
    }, 1200);
  }
}

function refreshReports() {
  const from =
    document.getElementById("dateFrom")?.value ||
    new Date().toISOString().split("T")[0];
  const to =
    document.getElementById("dateTo")?.value ||
    new Date().toISOString().split("T")[0];
  const officeId =
    SCOPED_OFFICE_ID ??
    (document.getElementById("officeFilter")?.value || null);
  const btn = event?.target;
  if (btn) {
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
  }

  Promise.all([
    fetchAndRenderWoW(officeId),
    fetchAndRenderDailyReport(from, to, officeId),
    fetchAndRenderOfficePerformance(from, to, officeId),
    fetchAndRenderPeakHours(from, to, officeId),
    fetchAndRenderDepartmentDemand(from, to, officeId),
    fetchAndRenderDayBreakdown(from, to, officeId),
    fetchAndRenderTransactionTypes(from, to, officeId),
  ]).finally(() => {
    if (btn) {
      btn.innerHTML = '<i class="fas fa-redo"></i> Refresh';
      btn.disabled = false;
    }
    showToast("Report refreshed.", "success");
  });
}

if (IS_PERSONAL_VIEW) {
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
      const el = document.getElementById("staffResultsLoading");
      if (el) el.textContent = "Failed to load. Try refreshing.";
    });
} else {
  const today = new Date().toISOString().split("T")[0];
  const officeId = SCOPED_OFFICE_ID ?? null;
  fetchAndRenderWoW(officeId);
  fetchAndRenderDailyReport(today, today, officeId);
  fetchAndRenderOfficePerformance(today, today, officeId);
  fetchAndRenderPeakHours(today, today, officeId);
  fetchAndRenderDepartmentDemand(today, today, officeId);
  fetchAndRenderDayBreakdown(today, today, officeId);
  fetchAndRenderTransactionTypes(today, today, officeId);
}
