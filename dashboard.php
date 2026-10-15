<?php
require_once __DIR__ . '/includes/config_loader.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/queue_functions.php';


if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$currentUser = getCurrentUser();

if (strtolower(trim((string) ($currentUser['role'] ?? ''))) === 'staff') {
    header('Location: ' . BASE_URL . 'staff_dashboard.php');
    exit;
}

$isAdminUser = (($_SESSION['role_id'] ?? null) === 1) || (($_SESSION['role'] ?? '') === 'admin');
$isDepartmentHead = in_array(strtolower(trim((string) ($currentUser['role'] ?? ''))), [
    'department head',
    'department_head',
    'department-head',
], true);
$scopeOfficeId = (!$isAdminUser && !empty($currentUser['office_id'])) ? (int) $currentUser['office_id'] : null;
$scopeOfficeName = null;
if ($scopeOfficeId) {
    $officeInfo = getOfficeInfo($db, $scopeOfficeId);
    $scopeOfficeName = $officeInfo['name'] ?? 'My Office';
}

$queueSummary   = getTodayQueueSummary($db, $scopeOfficeId);
$queueByOffice  = $isDepartmentHead ? [] : getQueueByOffice($db, $scopeOfficeId);
$queueTrendData = getQueueTrendData($db, $scopeOfficeId);

$hasOfficeData = !$isDepartmentHead && is_array($queueByOffice) && array_sum(array_column($queueByOffice, 'count')) > 0;
$hasTrendData  = is_array($queueTrendData) && array_sum(array_column($queueTrendData, 'count')) > 0;

$officeChartData = $hasOfficeData ? json_encode(array_values($queueByOffice)) : '[]';
$trendChartData  = $hasTrendData  ? json_encode(array_values($queueTrendData)) : '[]';

date_default_timezone_set('Asia/Manila');
$currentTime = date('l, F j, Y \a\t g:i A');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>

<body>
    <div class="dashboard-container">

        <?php
        include 'includes/sidebar.php';
        include 'includes/empty_state.php'
        ?>

        <div class="dashboard-body">

            <header class="dashboard-header">
                <div class="header-left">
                    <h2 class="header-page-title">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </h2>
                    <?php if ($scopeOfficeName): ?>
                        <span class="office-badge">
                            <i class="fas fa-building"></i>
                            <?php echo htmlspecialchars($scopeOfficeName); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="header-right">
                    <div class="header-timestamp" id="currentTime">
                        <?php echo $currentTime; ?>
                    </div>
                </div>
            </header>

            <main class="dashboard-main">
                <div class="dashboard-content">

                    <h2 class="section-title">
                        <i class="fas fa-layer-group"></i> Queue Summary — Today
                    </h2>

                    <div class="summary-cards">
                        <div class="card card-queued">
                            <div class="card-header">
                                <div class="card-icon"><i class="fas fa-users"></i></div>
                                <div class="card-label">Total Queued</div>
                            </div>

                            <div class="card-value"><?php echo intval($queueSummary['total_queued']); ?></div>
                            <div class="card-description">Students currently in queue</div>
                        </div>

                        <div class="card card-served">
                            <div class="card-header">
                                <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                                <div class="card-label">Total Served</div>
                            </div>
                            <div class="card-value"><?php echo intval($queueSummary['total_served']); ?></div>
                            <div class="card-description">Students processed today</div>
                        </div>

                        <div class="card card-skipped">
                            <div class="card-header">
                                <div class="card-icon"><i class="fas fa-ban"></i></div>
                                <div class="card-label">Total Skipped</div>
                            </div>
                            <div class="card-value"><?php echo intval($queueSummary['total_skipped']); ?></div>
                            <div class="card-description">Skipped tickets today</div>
                        </div>
                    </div>

                    <h2 class="section-title">
                        <i class="fas fa-chart-bar"></i> Analytics
                    </h2>

                    <div class="charts-grid<?php echo $isDepartmentHead ? ' charts-grid-single' : ''; ?>">

                        <?php if (!$isDepartmentHead): ?>
                        <div class="chart-container">
                            <h3 class="chart-title">
                                <i class="fas fa-building"></i> Queue by Office
                            </h3>
                            <div class="chart-canvas">
                                <?php
                                if ($hasOfficeData): ?>
                                    <canvas id="officeChart"></canvas>
                                <?php else:
                                    echo renderEmptyChartState(
                                        'fas fa-chart-pie',
                                        'No Office Data Yet',
                                        'Distribution will appear once students start queuing through the kiosk.'
                                    );
                                endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="chart-container">
                            <h3 class="chart-title">
                                <i class="fas fa-chart-line"></i> Peak Hours Trend
                            </h3>
                            <div class="chart-canvas">
                                <?php
                                if ($hasTrendData): ?>
                                    <canvas id="trendChart"></canvas>
                                <?php else:
                                    echo renderEmptyChartState(
                                        'fas fa-chart-line',
                                        'No Trend Data Yet',
                                        'Hourly queue volume will appear once the system starts recording activity.'
                                    );
                                endif; ?>
                            </div>
                        </div>

                    </div>

                </div>
            </main>

        </div>

    </div>

    <script>
        function updateTime() {
            const now = new Date();

            const dayName = now.toLocaleDateString('en-PH', {
                weekday: 'long',
                timeZone: 'Asia/Manila'
            });
            const monthDayYear = now.toLocaleDateString('en-PH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                timeZone: 'Asia/Manila'
            });

            const timeString = now.toLocaleTimeString('en-PH', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
                timeZone: 'Asia/Manila'
            });

            document.getElementById('currentTime').textContent = `${dayName}, ${monthDayYear} at ${timeString}`;
        }

        updateTime();
        setInterval(updateTime, 1000);

        Chart.defaults.font.family = "'DM Sans', sans-serif";
        Chart.defaults.color = '#7a9387';

        <?php if ($hasOfficeData): ?>
                (function() {
                    var officeData = <?php echo $officeChartData; ?>;
                    var labels = officeData.map(function(d) {
                        return d.office_name || d.name || 'Unknown';
                    });
                    var counts = officeData.map(function(d) {
                        return parseInt(d.count || d.total || 0, 10);
                    });

                    new Chart(document.getElementById('officeChart'), {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: counts,
                                backgroundColor: ['#008b43', '#2a9d5c', '#00c45a', '#48c774', '#006633'],
                                borderColor: '#ffffff',
                                borderWidth: 3,
                                hoverOffset: 6,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '72%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 12,
                                        usePointStyle: true,
                                        pointStyleWidth: 10,
                                        font: {
                                            size: 10
                                        }
                                    }
                                }
                            }
                        }
                    });
                })();
        <?php endif; ?>

        <?php if ($hasTrendData): ?>
                (function() {
                    var trendData = <?php echo $trendChartData; ?>;
                    var labels = trendData.map(function(d) {
                        var parts = d.time.split(':');
                        var h = parseInt(parts[0], 10);
                        var suffix = h >= 12 ? 'PM' : 'AM';
                        var h12 = h % 12 || 12;
                        return h12 + ':00 ' + suffix;
                    });
                    var counts = trendData.map(function(d) {
                        return parseInt(d.count || d.total || 0, 10);
                    });

                    new Chart(document.getElementById('trendChart'), {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Queue Volume',
                                data: counts,
                                borderColor: '#008b43',
                                backgroundColor: 'rgba(0,139,67,0.08)',
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 5,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: '#008b43',
                                pointBorderWidth: 2,
                                pointHoverRadius: 7,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: '#111c17',
                                    titleFont: {
                                        size: 12,
                                        weight: '600'
                                    },
                                    bodyFont: {
                                        size: 12
                                    },
                                    padding: 10,
                                    cornerRadius: 8,
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0,100,50,0.07)'
                                    },
                                    ticks: {
                                        font: {
                                            size: 12
                                        },
                                        padding: 8,
                                        stepSize: 1,
                                        precision: 0
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 10
                                        },
                                        padding: 4,
                                        maxRotation: 0,
                                        minRotation: 0,
                                        maxTicksLimit: 7,
                                    }
                                }
                            }
                        }
                    });
                })();
        <?php endif; ?>
    </script>

</body>

</html>