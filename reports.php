<?php
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/queue_functions.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$currentUser     = getCurrentUser();
$csrf_token      = getCsrfToken();

$isFullView      = ($_SESSION['role_id'] ?? null) === 1
    || ($_SESSION['role'] ?? '') === 'admin'
    || hasPermission('view_reports');
$isScopedView    = !$isFullView && hasPermission('view_reports_office');
$isPersonalView  = !$isFullView && !$isScopedView && hasPermission('view_own_metrics');

if (!$isPersonalView && !$isScopedView && !$isFullView) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$scopedOfficeId   = null;
$scopedOfficeName = null;
$offices          = [];

if ($isPersonalView || $isScopedView) {
    $scopedOfficeId   = (int) ($currentUser['office_id'] ?? 0);
    $officeInfo       = getOfficeInfo($db, $scopedOfficeId);
    $scopedOfficeName = $officeInfo['name'] ?? 'My Office';
} else {
    $result = $db->query("SELECT id, name FROM offices ORDER BY name ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) $offices[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>
        <?php
        if ($isPersonalView)     echo 'My Reports &amp; Metrics';
        elseif ($isScopedView)   echo htmlspecialchars($scopedOfficeName) . ' — Reports';
        else                     echo 'Reports &amp; Analytics';
        ?>
    </title>
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/dashboard.css" />
    <link rel="stylesheet" href="css/reports.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>

<body>
    <div class="dashboard-container">

        <?php
        include 'includes/sidebar.php';
        include 'includes/empty_state.php';
        ?>

        <div class="dashboard-body">

            <header class="dashboard-header">
                <div class="header-left">
                    <h2 class="header-page-title">
                        <i class="fas fa-chart-bar"></i>
                        <?php
                        if ($isPersonalView)     echo 'My Reports &amp; Metrics';
                        elseif ($isScopedView)   echo htmlspecialchars($scopedOfficeName) . ' &mdash; Reports';
                        else                     echo 'Reports &amp; Analytics';
                        ?>
                    </h2>
                    <?php if ($isPersonalView || $isScopedView): ?>
                        <span class="office-badge">
                            <i class="fas fa-building"></i>
                            <?php echo htmlspecialchars($scopedOfficeName); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="header-right">
                    <div class="header-timestamp" id="currentTime"></div>
                    <div class="export-progress-wrap">
                        <button class="btn-filter btn-export" onclick="exportReport('pdf')">
                            <i class="fas fa-file-pdf"></i> Export as PDF
                        </button>
                        <div class="export-progress-bar"></div>
                    </div>
                </div>
            </header>

            <main class="dashboard-main">
                <div class="dashboard-content">

                    <?php if ($isPersonalView): ?>

                        <!-- ── Quick ranges ── -->
                        <div class="quick-ranges">
                            <button class="range-pill active" data-range="today" onclick="setRange('today')">Today</button>
                            <button class="range-pill" data-range="yesterday" onclick="setRange('yesterday')">Yesterday</button>
                            <button class="range-pill" data-range="week" onclick="setRange('week')">This week</button>
                            <button class="range-pill" data-range="lastweek" onclick="setRange('lastweek')">Last week</button>
                            <button class="range-pill" data-range="month" onclick="setRange('month')">This month</button>
                        </div>

                        <div class="filter-bar">
                            <div class="filter-group">
                                <label><i class="fas fa-calendar-alt"></i> Date From</label>
                                <input type="date" id="dateFrom" />
                            </div>
                            <div class="filter-group">
                                <label><i class="fas fa-calendar-alt"></i> Date To</label>
                                <input type="date" id="dateTo" />
                            </div>
                            <div class="filter-actions">
                                <button class="btn-filter btn-apply" id="applyBtn" onclick="applyStaffFilters()">
                                    <i class="fas fa-search"></i> Apply
                                </button>
                                <button class="btn-filter" onclick="refreshReports()" title="Refresh data">
                                    <i class="fas fa-redo"></i> Refresh
                                </button>
                            </div>
                        </div>

                        <div id="staffResults" class="report-block">
                            <div class="report-block-header">
                                <div class="report-block-title">
                                    <div class="report-block-icon"><i class="fas fa-receipt"></i></div>
                                    <span id="staffResultsLabel">Loading…</span>
                                </div>
                                <span class="pill pill-orange" id="staffResultsPill"></span>
                            </div>
                            <div class="report-block-body">
                                <div class="report-block-loading" id="staffResultsLoading">
                                    <i class="fas fa-spinner fa-spin"></i> Loading your stats…
                                </div>
                                <div id="staffResultsCards" class="daily-summary-row" style="display:none;"></div>
                            </div>
                        </div>

                    <?php else: ?>

                        <!-- ── WEEK-OVER-WEEK COMPARISON ── -->
                        <h2 class="section-title"><i class="fas fa-calendar-week"></i> This Week vs Last Week</h2>

                        <div class="report-block" id="block-wow">
                            <div class="report-block-header">
                                <div class="report-block-title">
                                    <div class="report-block-icon"><i class="fas fa-exchange-alt"></i></div>
                                    Weekly Comparison
                                </div>
                                <span class="pill pill-orange" id="wowPill">No Data</span>
                            </div>
                            <div class="report-block-body">
                                <div class="report-block-loading" id="wowLoading">
                                    <i class="fas fa-spinner fa-spin"></i> Loading…
                                </div>
                                <div id="wowContent" style="display:none;">
                                    <div class="wow-grid" id="wowCards"></div>
                                    <div class="sub-chart-box" style="margin-top:14px;">
                                        <canvas id="wowChart" style="max-height:220px;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="quick-ranges">
                            <button class="range-pill active" data-range="today" onclick="setRange('today')">Today</button>
                            <button class="range-pill" data-range="yesterday" onclick="setRange('yesterday')">Yesterday</button>
                            <button class="range-pill" data-range="week" onclick="setRange('week')">This week</button>
                            <button class="range-pill" data-range="lastweek" onclick="setRange('lastweek')">Last week</button>
                            <button class="range-pill" data-range="month" onclick="setRange('month')">This month</button>
                        </div>

                        <div class="filter-bar">
                            <div class="filter-group">
                                <label><i class="fas fa-calendar-alt"></i> From</label>
                                <input type="date" id="dateFrom" />
                            </div>
                            <div class="filter-group">
                                <label><i class="fas fa-calendar-alt"></i> To</label>
                                <input type="date" id="dateTo" />
                            </div>
                            <?php if ($isFullView): ?>
                                <div class="filter-group">
                                    <label><i class="fas fa-building"></i> Department</label>
                                    <select id="officeFilter">
                                        <option value="">All</option>
                                        <?php foreach ($offices as $office): ?>
                                            <option value="<?= $office['id']; ?>">
                                                <?= htmlspecialchars($office['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div class="filter-actions">
                                <button class="btn-filter btn-apply" onclick="applyFilters()">
                                    <i class="fas fa-search"></i> View Report
                                </button>
                            </div>
                        </div>

                        <h2 class="section-title" style="margin-top:28px;"><i class="fas fa-calendar-day"></i> Daily Transaction Report</h2>

                        <div class="report-block" id="block-daily">
                            <div class="report-block-header">
                                <div class="report-block-title">
                                    <div class="report-block-icon"><i class="fas fa-receipt"></i></div>
                                    Queue Volume Summary
                                </div>
                                <span class="pill pill-orange">No Data</span>
                            </div>
                            <div class="report-block-body">
                                <div class="daily-summary-row">
                                    <div class="summary-stat stat-green">
                                        <div class="summary-stat-label">Total Queued</div>
                                        <div class="summary-stat-value stat-muted">0</div>
                                        <div class="summary-stat-sub">Students in queue today</div>
                                    </div>
                                    <div class="summary-stat stat-green">
                                        <div class="summary-stat-label">Total Served</div>
                                        <div class="summary-stat-value stat-muted">0</div>
                                        <div class="summary-stat-sub">Completed transactions</div>
                                    </div>
                                    <div class="summary-stat stat-orange">
                                        <div class="summary-stat-label">Total Skipped</div>
                                        <div class="summary-stat-value stat-muted">0</div>
                                        <div class="summary-stat-sub">No-show tickets</div>
                                    </div>
                                    <div class="summary-stat stat-blue">
                                        <div class="summary-stat-label">Completion Rate</div>
                                        <div class="summary-stat-value stat-muted stat-na">N/A</div>
                                        <div class="summary-stat-sub">Served / Total queued</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h2 class="section-title" style="margin-top:28px;"><i class="fas fa-building"></i> Department Performance</h2>

                        <div class="report-block" id="block-office">
                            <div class="report-block-header">
                                <div class="report-block-title">
                                    <div class="report-block-icon"><i class="fas fa-trophy"></i></div>
                                    How Each Department is Performing
                                </div>
                                <span class="pill pill-orange">No Data</span>
                            </div>
                            <div class="report-block-body">
                                <div class="empty-state" id="emptyOffice" style="display:none;">
                                    <div class="empty-state-icon"><i class="fas fa-building"></i></div>
                                    <div class="empty-state-title">No department data</div>
                                    <div class="empty-state-message">Try a wider date range to see department performance.</div>
                                </div>
                                <table class="perf-table" style="display:none;" id="officeTable">
                                    <thead>
                                        <tr>
                                            <th>Office</th>
                                            <th>Served</th>
                                            <th>Skipped</th>
                                            <th>Avg. Service Time</th>
                                            <th>Share of Volume</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        <h2 class="section-title" style="margin-top:28px;"><i class="fas fa-clock"></i> When Are We Busiest?</h2>

                        <div class="report-block" id="block-peaks">
                            <div class="report-block-header">
                                <div class="report-block-title">
                                    <div class="report-block-icon"><i class="fas fa-fire-alt"></i></div>
                                    Peak Hours &amp; Days
                                </div>
                                <span class="pill pill-orange">No Data</span>
                            </div>
                            <div class="report-block-body">
                                <div class="charts-2col">
                                    <div class="sub-chart-box">
                                        <canvas id="peakHoursChart" style="display:none;"></canvas>
                                        <div class="empty-state" id="emptyPeakHours">
                                            <div class="empty-state-icon"><i class="fas fa-chart-line"></i></div>
                                            <div class="empty-state-title">No Data</div>
                                            <div class="empty-state-message">Hourly trends will appear here.</div>
                                        </div>
                                    </div>
                                    <div class="sub-chart-box">
                                        <canvas id="busyDaysChart" style="display:none;"></canvas>
                                        <div class="empty-state" id="emptyBusyDays">
                                            <div class="empty-state-icon"><i class="fas fa-calendar-week"></i></div>
                                            <div class="empty-state-title">No Data</div>
                                            <div class="empty-state-message">Daily trends will appear here.</div>
                                        </div>
                                    </div>
                                    <?php if ($isFullView): ?>
                                        <div class="sub-chart-box" style="grid-column: 1 / -1;">
                                            <canvas id="deptChart" style="display:none;"></canvas>
                                            <div class="empty-state" id="emptyDept">
                                                <div class="empty-state-icon"><i class="fas fa-layer-group"></i></div>
                                                <div class="empty-state-title">No Data</div>
                                                <div class="empty-state-message">Department demand will appear here.</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

    <script>
        const IS_PERSONAL_VIEW = <?php echo json_encode($isPersonalView); ?>;
        const IS_SCOPED_VIEW = <?php echo json_encode($isScopedView); ?>;
        const SCOPED_OFFICE_ID = <?php echo json_encode($scopedOfficeId); ?>;
        const CSRF_TOKEN = <?php echo json_encode($csrf_token); ?>;
    </script>
    <script src="scripts/reports.js"></script>
</body>

</html>