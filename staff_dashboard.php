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

if (strtolower(trim((string) ($currentUser['role'] ?? ''))) !== 'staff') {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$staffOfficeId = $currentUser['office_id'] ?? null;

$officeInfo      = $staffOfficeId ? getOfficeInfo($db, $staffOfficeId) : null;
$staffOfficeName = $officeInfo['name'] ?? 'Unassigned Office';

$staffSummary = getStaffQueueSummary($db, (int) $currentUser['id'], $staffOfficeId ? (int) $staffOfficeId : null);
$staffTrend   = getStaffQueueTrend($db, (int) $currentUser['id'], $staffOfficeId ? (int) $staffOfficeId : null);

$waiting = (int) ($staffSummary['waiting'] ?? 0);
$served  = (int) ($staffSummary['served'] ?? 0);
$skipped = (int) ($staffSummary['skipped'] ?? 0);

$hasTrendData  = is_array($staffTrend) && array_sum(array_column($staffTrend, 'count')) > 0;
$trendChartData = $hasTrendData ? json_encode(array_values($staffTrend)) : '[]';

date_default_timezone_set('Asia/Manila');
$currentTime = date('l, F j, Y \a\t g:i A');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
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
        include 'includes/empty_state.php';
        ?>

        <div class="dashboard-body">

            <header class="dashboard-header">
                <div class="header-left">
                    <h2 class="header-page-title">
                        <i class="fas fa-chart-pie"></i> Staff Dashboard
                    </h2>
                    <span class="office-badge">
                        <i class="fas fa-building"></i>
                        <?php echo htmlspecialchars($staffOfficeName); ?>
                    </span>
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
                        <i class="fas fa-layer-group"></i> Queue Overview — Today
                    </h2>

                    <div class="staff-cards">

                        <div class="card card-queued">
                            <div class="card-header">
                                <div class="card-icon"><i class="fas fa-user-clock"></i></div>
                                <div class="card-label">Currently Waiting</div>
                            </div>
                            <div class="card-value" id="waitingCount"><?php echo $waiting; ?></div>
                            <div class="card-description">Tickets waiting for your window</div>
                            <div class="live-dot">Live</div>
                        </div>

                        <div class="card card-served">
                            <div class="card-header">
                                <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                                <div class="card-label">Total Served</div>
                            </div>
                            <div class="card-value"><?php echo $served; ?></div>
                            <div class="card-description">Students processed today</div>
                        </div>

                        <div class="card card-skipped">
                            <div class="card-header">
                                <div class="card-icon"><i class="fas fa-ban"></i></div>
                                <div class="card-label">Total Skipped</div>
                            </div>
                            <div class="card-value"><?php echo $skipped; ?></div>
                            <div class="card-description">Skipped tickets today</div>
                        </div>

                    </div>

                    <h2 class="section-title">
                        <i class="fas fa-chart-bar"></i> Analytics
                    </h2>

                    <div class="chart-container-full">
                        <div style="display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:18px;">
                            <div>
                                <h3 class="chart-title" style="margin-bottom:0;">
                                    <i class="fas fa-chart-line"></i> Your Completed Tickets by Hour
                                </h3>
                                <p class="refresh-note">
                                    <i class="fas fa-sync-alt"></i>
                                    Page auto-refreshes every 60 seconds
                                </p>
                            </div>
                            <span class="office-badge" style="margin-top:2px;">
                                <i class="fas fa-building"></i>
                                <?php echo htmlspecialchars($staffOfficeName); ?>
                            </span>
                        </div>

                        <div class="chart-canvas" style="max-height:340px;">
                            <?php if ($hasTrendData): ?>
                                <canvas id="trendChart"></canvas>
                            <?php else:
                                echo renderEmptyChartState(
                                    'fas fa-chart-line',
                                    'No Trend Data Yet',
                                    'Your completed tickets by hour will appear once you serve tickets today.'
                                );
                            endif; ?>
                        </div>
                    </div>

                </div>
            </main>

        </div>
    </div>

    <script>
        function updateTime() {
            const now = new Date();
            const day = now.toLocaleDateString('en-PH', {
                weekday: 'long',
                timeZone: 'Asia/Manila'
            });
            const date = now.toLocaleDateString('en-PH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                timeZone: 'Asia/Manila'
            });
            const time = now.toLocaleTimeString('en-PH', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
                timeZone: 'Asia/Manila'
            });
            document.getElementById('currentTime').textContent = `${day}, ${date} at ${time}`;
        }
        updateTime();
        setInterval(updateTime, 1000);

        Chart.defaults.font.family = "'DM Sans', sans-serif";
        Chart.defaults.color = '#7a9387';

        <?php if ($hasTrendData): ?>
                (function() {
                    var trendData = <?php echo $trendChartData; ?>;
                    var labels = trendData.map(function(d) {
                        return d.hour || d.label || '';
                    });
                    var counts = trendData.map(function(d) {
                        return parseInt(d.count || d.total || 0, 10);
                    });

                    new Chart(document.getElementById('trendChart'), {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Completed Tickets',
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
                                            size: 12
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

        setTimeout(function() {
            location.reload();
        }, 60000);
    </script>

</body>

</html>