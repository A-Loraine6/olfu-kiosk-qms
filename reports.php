<?php
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/queue_functions.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$currentUser = getCurrentUser();
$isStaff     = ($currentUser['role'] ?? '') === 'staff';
$csrf_token  = getCsrfToken();

if ($isStaff) {
    $staffOfficeId   = (int) ($currentUser['office_id'] ?? 0);
    $officeInfo      = getOfficeInfo($db, $staffOfficeId);
    $staffOfficeName = $officeInfo['name'] ?? 'My Office';
    $offices         = [];
} else {
    $staffOfficeId   = null;
    $staffOfficeName = null;
    $offices         = [];
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
    <title><?php echo $isStaff ? 'My Reports & Metrics' : 'Reports & Analytics'; ?></title>
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
                        <?php echo $isStaff ? 'My Reports &amp; Metrics' : 'Reports &amp; Analytics'; ?>
                    </h2>
                    <?php if ($isStaff): ?>
                        <span class="office-badge">
                            <i class="fas fa-building"></i>
                            <?php echo htmlspecialchars($staffOfficeName); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="header-right">
                    <div class="header-timestamp" id="currentTime"></div>
                    <button class="btn-filter btn-export" onclick="exportReport('pdf')">
                        <i class="fas fa-file-pdf"></i> Export as PDF
                    </button>
                </div>
            </header>

            <main class="dashboard-main">
                <div class="dashboard-content">

                    <?php if ($isStaff): ?>

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
                                <button class="btn-filter btn-apply" onclick="applyFilters()">
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

                        <h3 class="section-title">
                            <i class="fas fa-filter"></i> Report Filters
                        </h3>
                        <div class="filter-bar">
                            <div class="filter-group">
                                <label><i class="fas fa-calendar-alt"></i> Date From</label>
                                <input type="date" id="dateFrom" />
                            </div>
                            <div class="filter-group">
                                <label><i class="fas fa-calendar-alt"></i> Date To</label>
                                <input type="date" id="dateTo" />
                            </div>
                            <div class="filter-group">
                                <label><i class="fas fa-building"></i> Office</label>
                                <select id="officeFilter">
                                    <option value="">All Offices</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?= $office['id']; ?>">
                                            <?= htmlspecialchars($office['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-actions">
                                <button class="btn-filter btn-apply" onclick="applyFilters()">
                                    <i class="fas fa-search"></i> Apply
                                </button>
                            </div>
                        </div>

                        <h2 class="section-title"><i class="fas fa-calendar-day"></i> Daily Transaction Report</h2>

                        <div class="report-block" id="block-daily">
                            <div class="report-block-header">
                                <div class="report-block-title">
                                    <div class="report-block-icon"><i class="fas fa-receipt"></i></div>
                                    Queue Volume Summary — Today
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

                        <h2 class="section-title" style="margin-top:28px;"><i class="fas fa-building"></i> Office Performance Report</h2>

                        <div class="report-block" id="block-office">
                            <div class="report-block-header">
                                <div class="report-block-title">
                                    <div class="report-block-icon"><i class="fas fa-trophy"></i></div>
                                    Transaction Count &amp; Avg. Service Time per Office
                                </div>
                                <span class="pill pill-orange">No Data</span>
                            </div>
                            <div class="report-block-body">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="fas fa-building"></i></div>
                                    <div class="empty-state-title">No Office Data Available</div>
                                    <div class="empty-state-message">Office-level performance metrics will appear here once transactions are recorded in the system.</div>
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

                        <h2 class="section-title" style="margin-top:28px;"><i class="fas fa-clock"></i> Peak Schedule Analysis</h2>

                        <div class="report-block" id="block-peaks">
                            <div class="report-block-header">
                                <div class="report-block-title">
                                    <div class="report-block-icon"><i class="fas fa-fire-alt"></i></div>
                                    Busiest Hours &amp; Days · Queue Demand per Department
                                </div>
                                <span class="pill pill-orange">No Data</span>
                            </div>
                            <div class="report-block-body">
                                <div class="charts-2col">
                                    <div class="sub-chart-box">
                                        <div class="sub-chart-label"><i class="fas fa-chart-line"></i> Hourly Queue Volume</div>
                                        <div class="empty-state">
                                            <div class="empty-state-icon"><i class="fas fa-chart-line"></i></div>
                                            <div class="empty-state-title">No Hourly Data Yet</div>
                                            <div class="empty-state-message">Peak hour identification will appear once the system records daily activity.</div>
                                        </div>
                                        <canvas id="peakHoursChart" style="display:none;"></canvas>
                                    </div>
                                    <div class="sub-chart-box">
                                        <div class="sub-chart-label"><i class="fas fa-calendar-week"></i> Volume by Day of Week</div>
                                        <div class="empty-state">
                                            <div class="empty-state-icon"><i class="fas fa-calendar-week"></i></div>
                                            <div class="empty-state-title">No Day-of-Week Data Yet</div>
                                            <div class="empty-state-message">This chart will display the busiest days once enough data is collected.</div>
                                        </div>
                                        <canvas id="busyDaysChart" style="display:none;"></canvas>
                                    </div>
                                    <div class="sub-chart-box" style="grid-column: 1 / -1;">
                                        <div class="sub-chart-label"><i class="fas fa-layer-group"></i> Queue Demand Comparison per Department</div>
                                        <div class="empty-state">
                                            <div class="empty-state-icon"><i class="fas fa-layer-group"></i></div>
                                            <div class="empty-state-title">No Department Comparison Data</div>
                                            <div class="empty-state-message">Department-level demand comparison will be shown here once transactions are logged across offices.</div>
                                        </div>
                                        <canvas id="deptChart" style="display:none;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

    <script>
        const IS_STAFF = <?php echo json_encode($isStaff); ?>;
        const CSRF_TOKEN = <?php echo json_encode($csrf_token); ?>;
    </script>
    <script src="scripts/reports.js"></script>
</body>

</html>