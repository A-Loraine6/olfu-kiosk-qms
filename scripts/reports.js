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
