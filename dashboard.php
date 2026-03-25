<?php
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/queue_functions.php';


if (!isLoggedIn() || !checkSessionTimeout()) {
    header('Location: login.php');
    exit;
}

$currentUser    = getCurrentUser();
$queueSummary   = getTodayQueueSummary($db);
$queueByOffice  = getQueueByOffice($db);
$queueTrendData = getQueueTrendData($db);

$hasOfficeData = is_array($queueByOffice) && array_sum(array_column($queueByOffice, 'count')) > 0;
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
                </div>
                <div class="header-right">
                    <div class="header-timestamp">
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
                            <div class="card-icon"><i class="fas fa-users"></i></div>
                            <div class="card-label">Total Queued</div>
                            <div class="card-value"><?php echo intval($queueSummary['total_queued']); ?></div>
                            <div class="card-description">Students currently in queue</div>
                        </div>

                        <div class="card card-served">
                            <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="card-label">Total Served</div>
                            <div class="card-value"><?php echo intval($queueSummary['total_served']); ?></div>
                            <div class="card-description">Students processed today</div>
                        </div>

                        <div class="card card-skipped">
                            <div class="card-icon"><i class="fas fa-ban"></i></div>
                            <div class="card-label">Total Skipped</div>
                            <div class="card-value"><?php echo intval($queueSummary['total_skipped']); ?></div>
                            <div class="card-description">Skipped tickets today</div>
                        </div>
                    </div>

                    <h2 class="section-title">
                        <i class="fas fa-chart-bar"></i> Analytics
                    </h2>

                    <div class="charts-grid">

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
                            maintainAspectRatio: true,
                            cutout: '62%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true,
                                        pointStyleWidth: 10,
                                        font: {
                                            size: 12
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
                                        padding: 8
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
                                        padding: 4
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
