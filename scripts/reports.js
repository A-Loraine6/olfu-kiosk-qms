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

function renderDailyReport(data) {
  const block = document.getElementById("block-daily");
  if (!block) return;

  const hasData = data.total_queued > 0;
  const container = block.querySelector(".daily-summary-row");
  const pill = block.querySelector(".pill");

  pill.textContent = hasData ? "Has Data" : "No Data";
  pill.className = "pill " + (hasData ? "pill-green" : "pill-orange");

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

function renderOfficePerformance(data) {
  const block = document.getElementById("block-office");
  if (!block) return;

  const hasData = data.some((d) => d.total_transactions > 0);
  const table = block.querySelector(".perf-table");
  const emptyState = block.querySelector(".empty-state");
  const pill = block.querySelector(".pill");

  pill.textContent = hasData ? "Has Data" : "No Data";
  pill.className = "pill " + (hasData ? "pill-green" : "pill-orange");

  if (emptyState) emptyState.style.display = hasData ? "none" : "";

  if (table) {
    table.style.display = "";
    const tbody = table.querySelector("tbody");
    tbody.innerHTML = "";
    data.forEach((office) => {
      const row = document.createElement("tr");
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

      row.innerHTML = `
        <td>${office.name}</td>
        <td>${office.served}</td>
        <td>${office.skipped}</td>
        <td>${office.avg_service_time > 0 ? office.avg_service_time + " min" : "—"}</td>
        <td>${office.volume_share.toFixed(1)}%</td>
        <td><span class="status-badge ${statusClass}">
          ${statusLabel}
        </span></td>
      `;
      tbody.appendChild(row);
    });

    // Add sorting capability to table headers
    const headers = table.querySelectorAll("thead th");
    headers.forEach((header, index) => {
      header.style.cursor = "pointer";
      header.title = "Click to sort";
      header.addEventListener("click", () => sortTable(table, index));
    });
  }
}

function sortTable(table, columnIndex) {
  const tbody = table.querySelector("tbody");
  const rows = Array.from(tbody.querySelectorAll("tr"));

  rows.sort((a, b) => {
    const aVal = a.querySelectorAll("td")[columnIndex].textContent.trim();
    const bVal = b.querySelectorAll("td")[columnIndex].textContent.trim();

    // Try numeric sort first
    const aNum = parseFloat(aVal);
    const bNum = parseFloat(bVal);
    if (!isNaN(aNum) && !isNaN(bNum)) {
      return bNum - aNum;
    }
    // Fall back to string sort
    return aVal.localeCompare(bVal);
  });

  rows.forEach((row) => tbody.appendChild(row));
}

let peakHoursChart = null;
let deptChart = null;

function renderPeakHours(data) {
  const block = document.getElementById("block-peaks");
  if (!block) return;

  const hasData = data.some((d) => d > 0);
  const canvas = block.querySelector("#peakHoursChart");
  const emptyState = canvas
    .closest(".sub-chart-box")
    .querySelector(".empty-state");
  const pill = block.querySelector(".pill");

  if (pill) {
    pill.textContent = hasData ? "Has Data" : "No Data";
    pill.className = "pill " + (hasData ? "pill-green" : "pill-orange");
  }

  if (!hasData) {
    if (emptyState) emptyState.style.display = "";
    if (canvas) canvas.style.display = "none";
  } else {
    if (emptyState) emptyState.style.display = "none";
    if (canvas) canvas.style.display = "";

    const hours = Array.from({ length: 24 }, (_, i) => {
      const h = String(i).padStart(2, "0");
      return h + ":00";
    });

    const ctx = canvas.getContext("2d");
    if (peakHoursChart) peakHoursChart.destroy();
    peakHoursChart = new Chart(ctx, {
      type: "line",
      data: {
        labels: hours,
        datasets: [
          {
            label: "Queue Volume",
            data: data,
            borderColor: "#008b43",
            backgroundColor: "rgba(0, 139, 67, 0.1)",
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
        plugins: {
          legend: { display: false },
        },
        scales: {
          y: { beginAtZero: true },
        },
      },
    });
  }
}

function renderDepartmentDemand(data) {
  const block = document.getElementById("block-peaks");
  if (!block) return;

  const hasData = data.some((d) => d.count > 0);
  const canvas = block.querySelector("#deptChart");
  const emptyState = canvas
    .closest(".sub-chart-box")
    .querySelector(".empty-state");
  const pill = block.querySelector(".pill");

  if (pill) {
    pill.textContent = hasData ? "Has Data" : "No Data";
    pill.className = "pill " + (hasData ? "pill-green" : "pill-orange");
  }

  if (!hasData) {
    if (emptyState) emptyState.style.display = "";
    if (canvas) canvas.style.display = "none";
  } else {
    if (emptyState) emptyState.style.display = "none";
    if (canvas) canvas.style.display = "";

    // Green shades from the dashboard primary color
    const greenShades = [
      "#008b43",
      "#00a652",
      "#00c166",
      "#10b981",
      "#14b8a6",
      "#06b6d4",
    ];
    const borderShades = [
      "#006633",
      "#007a3d",
      "#009950",
      "#0e9060",
      "#0f9e7e",
      "#0396a8",
    ];

    const ctx = canvas.getContext("2d");
    if (deptChart) deptChart.destroy();
    deptChart = new Chart(ctx, {
      type: "bar",
      data: {
        labels: data.map((d) => d.name),
        datasets: [
          {
            label: "Queue Demand",
            data: data.map((d) => d.count),
            backgroundColor: data.map(
              (_, i) => greenShades[i % greenShades.length],
            ),
            borderColor: data.map(
              (_, i) => borderShades[i % borderShades.length],
            ),
            borderWidth: 0,
            borderRadius: 4,
          },
        ],
      },
      options: {
        indexAxis: "y",
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { display: false },
          datalabels: { display: false },
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: { stepSize: 1 },
            grid: { drawBorder: false, display: false },
          },
          y: {
            categoryPercentage: 0.5,
            barPercentage: 0.6,
            grid: { display: false, drawBorder: false },
            ticks: { font: { size: 11, weight: "600" } },
          },
        },
      },
    });
  }
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
    if (data.success) {
      renderDailyReport(data.report);
    }
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
    if (data.success) {
      renderOfficePerformance(data.performance);
    }
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
    if (data.success) {
      renderPeakHours(data.peak_hours);
    }
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
    if (data.success) {
      renderDepartmentDemand(data.department_demand);
    }
  } catch (e) {
    console.error("Error fetching department demand:", e);
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

async function fetchAndRenderDayBreakdown(from, to, officeId = null) {
  try {
    const res = await fetch("api/reports_actions.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "day_of_week_volume", // make sure this matches
        date_from: from,
        date_to: to,
        office_id: officeId,
        csrf_token: CSRF_TOKEN,
      }),
    });
    const data = await res.json();
    console.log("day_of_week_volume response:", data); // add this to debug
    if (
      data.success &&
      data.day_of_week_volume &&
      data.day_of_week_volume.length > 0
    ) {
      renderDayBreakdown(data.day_of_week_volume);
    } else {
      const canvas = document.getElementById("busyDaysChart");
      const emptyState = canvas
        ?.closest(".sub-chart-box")
        ?.querySelector(".empty-state");
      if (emptyState) emptyState.style.display = "";
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
    if (data.success && data.transaction_types.length > 0) {
      renderTransactionTypes(data.transaction_types);
    }
  } catch (e) {
    console.error("Error fetching transaction types:", e);
  }
}

function renderDayBreakdown(data) {
  data = [...data].sort((a, b) => b.count - a.count);
  
  const labels = data.map((d) => {
    if (d.date) {
      const dateObj = new Date(d.date + "T00:00:00");
      const dateStr = dateObj.toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
      });
      return `${d.day.substring(0, 3)} (${dateStr})`;
    } else {
      return d.day.substring(0, 3);
    }
  });
  const counts = data.map((d) => d.count);

  const canvas = document.getElementById("busyDaysChart");
  if (!canvas) return;

  const ctx = canvas.getContext("2d");
  if (window.busyDaysChartInstance) window.busyDaysChartInstance.destroy();

  window.busyDaysChartInstance = new Chart(ctx, {
    type: "bar",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Queue Volume by Day",
          data: counts,
          backgroundColor: [
            "#006633",
            "#007a3d",
            "#008b43",
            "#00a652",
            "#00c166",
            "#10b981",
            "#48c774",
          ],
          borderColor: "transparent",
          borderWidth: 0,
          borderRadius: 6,
          maxBarThickness: 48,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      indexAxis: "x",
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function (context) {
              return "Queue Volume: " + context.parsed.y;
            },
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1 },
          grid: { drawBorder: false, color: "rgba(0,0,0,0.05)" },
        },
        x: {
          grid: { display: false },
          ticks: { font: { size: 10, weight: "600" } },
        },
      },
    },
  });

  canvas.style.display = "";
  const emptyState = canvas
    .closest(".sub-chart-box")
    .querySelector(".empty-state");
  if (emptyState) emptyState.style.display = "none";
}

function renderTransactionTypes(data) {
  const labels = data.map((d) => d.type);
  const totals = data.map((d) => d.total);

  const canvas = document.getElementById("transactionTypesChart");
  if (!canvas) {
    console.warn("Transaction types chart not found");
    return;
  }

  const ctx = canvas.getContext("2d");
  if (window.transactionTypesChart) window.transactionTypesChart.destroy();

  window.transactionTypesChart = new Chart(ctx, {
    type: "doughnut",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Transactions by Type",
          data: totals,
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
      plugins: {
        legend: {
          position: "bottom",
        },
      },
    },
  });

  canvas.style.display = "";
  const emptyState = canvas
    .closest(".sub-chart-box")
    .querySelector(".empty-state");
  if (emptyState) emptyState.style.display = "none";
}

function applyFilters() {
  const from = document.getElementById("dateFrom").value;
  const to = document.getElementById("dateTo").value;
  const officeId = document.getElementById("officeFilter")?.value || null;

  if (!from || !to) {
    alert("Please select both a start and end date.");
    return;
  }
  if (from > to) {
    alert("'Date From' cannot be after 'Date To'.");
    return;
  }

  const btn = document.querySelector(".btn-apply");
  if (!btn) return;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying…';
  btn.disabled = true;

  // Fetch all admin reports
  Promise.all([
    fetchAndRenderDailyReport(from, to, officeId),
    fetchAndRenderOfficePerformance(from, to, officeId),
    fetchAndRenderPeakHours(from, to, officeId),
    fetchAndRenderDepartmentDemand(from, to, officeId),
    fetchAndRenderDayBreakdown(from, to, officeId),
    fetchAndRenderTransactionTypes(from, to, officeId),
  ]).finally(() => {
    btn.innerHTML = '<i class="fas fa-search"></i> Apply';
    btn.disabled = false;
  });
}

function exportReport(type) {
  if (type === "pdf") window.print();
}

function refreshReports() {
  const from =
    document.getElementById("dateFrom")?.value ||
    new Date().toISOString().split("T")[0];
  const to =
    document.getElementById("dateTo")?.value ||
    new Date().toISOString().split("T")[0];
  const officeId = document.getElementById("officeFilter")?.value || null;

  const btn = event?.target;
  if (btn) {
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
  }

  Promise.all([
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
  });
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
} else {
  const today = new Date().toISOString().split("T")[0];
  fetchAndRenderDailyReport(today, today);
  fetchAndRenderOfficePerformance(today, today);
  fetchAndRenderPeakHours(today, today);
  fetchAndRenderDepartmentDemand(today, today);
  fetchAndRenderDayBreakdown(today, today);
  fetchAndRenderTransactionTypes(today, today);
}
