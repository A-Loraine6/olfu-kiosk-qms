<?php
require_once 'config/config.php';
require_once 'includes/db.php';

date_default_timezone_set('Asia/Manila');

$officesResult = $db->query("
    SELECT id, name, code, queue_mode, open_time, close_time, queue_prefix,
           break_start, break_end, queue_cutoff
    FROM offices
    ORDER BY name ASC
");
$offices = $officesResult->fetch_all(MYSQLI_ASSOC);

$today = date('Y-m-d');
$waitingStmt = $db->prepare("
    SELECT office_id, COUNT(*) AS cnt
    FROM tickets
    WHERE status IN ('waiting', 'serving')
      AND DATE(created_at) = ?
    GROUP BY office_id
");
$waitingStmt->bind_param('s', $today);
$waitingStmt->execute();
$waitingResult = $waitingStmt->get_result();
$waitingCounts = [];
foreach ($waitingResult->fetch_all(MYSQLI_ASSOC) as $row) {
    $waitingCounts[$row['office_id']] = (int)$row['cnt'];
}

$avgTimeStmt = $db->prepare("
    SELECT office_id,
           AVG(TIMESTAMPDIFF(SECOND, served_at, completed_at) + COALESCE(time_spent, 0)) AS avg_seconds
    FROM tickets
    WHERE status = 'completed'
      AND served_at IS NOT NULL
      AND completed_at IS NOT NULL
      AND DATE(created_at) = ?
    GROUP BY office_id
");
$today = date('Y-m-d');
$avgTimeStmt->bind_param('s', $today);
$avgTimeStmt->execute();
$avgTimeResult = $avgTimeStmt->get_result();
$avgTimes = [];
foreach ($avgTimeResult->fetch_all(MYSQLI_ASSOC) as $row) {
    $avgTimes[$row['office_id']] = (int) round($row['avg_seconds'] / 60); // convert to minutes
}

$servicesResult = $db->query("
    SELECT id, name, category, description, office_id
    FROM transaction_types
    WHERE is_active = 1 AND is_deleted = 0
    ORDER BY office_id, name ASC
");
$servicesByOffice = [];
foreach ($servicesResult->fetch_all(MYSQLI_ASSOC) as $svc) {
    $servicesByOffice[$svc['office_id']][] = $svc;
}

$officesForJs = [];
foreach ($offices as $office) {
    $officesForJs[] = [
        'id'           => $office['id'],
        'name'         => $office['name'],
        'code'         => $office['code'],
        'mode'         => $office['queue_mode'],
        'prefix'       => $office['queue_prefix'],
        'waiting'      => $waitingCounts[$office['id']] ?? 0,
        'services'     => $servicesByOffice[$office['id']] ?? [],
        'open_time'    => $office['open_time'],
        'close_time'   => $office['close_time'],
        'break_start'  => $office['break_start'],
        'break_end'    => $office['break_end'],
        'queue_cutoff' => $office['queue_cutoff'],
        'avg_minutes'  => $avgTimes[$office['id']] ?? null,
    ];
}

$officesJson = json_encode($officesForJs);

$kioskSettingsResult = $db->query("SELECT kiosk_enabled, idle_timeout FROM kiosk_settings WHERE id = 1 LIMIT 1");
$kioskSettings = $kioskSettingsResult->fetch_assoc();
$kioskEnabled = (bool)($kioskSettings['kiosk_enabled'] ?? true);
$idleTimeout  = (int)($kioskSettings['idle_timeout'] ?? 60);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Queue Kiosk</title>
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>

<body>

    <div class="mobile-block">
        <i class="fas fa-desktop"></i>
        <h2>Kiosk Only</h2>
        <p>This queue system is only available on the kiosk terminal.<br>Please visit the office to get your queue number.</p>
    </div>

    <div class="idle" id="idleOverlay" onclick="dismissIdle()">

        <div class="idle-content">

            <div class="idle-logo-wrap">
                <img src="img/olfu-logo.png" alt="OLFU"
                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <div class="idle-logo-fallback" style="display:none">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="idle-logo-ring-pulse"></div>
            </div>

            <div class="idle-school">Our Lady of Fatima University</div>
            <div class="idle-title">Queue Management System</div>

            <div class="idle-clock-wrap">
                <div class="idle-clock" id="idleTime"></div>
                <div class="idle-date" id="idleDate"></div>
            </div>

            <div class="idle-divider">
                <span class="idle-divider-dot"></span>
                <span class="idle-divider-line"></span>
                <span class="idle-divider-dot"></span>
            </div>

            <div class="idle-how-label">How it works</div>
            <div class="idle-steps">
                <div class="idle-step">
                    <div class="idle-step-icon">
                        <i class="fas fa-building"></i>
                        <span class="idle-step-num">1</span>
                    </div>
                    <div class="idle-step-text">Select an office</div>
                </div>
                <div class="idle-step-arrow"><i class="fas fa-chevron-right"></i></div>
                <div class="idle-step">
                    <div class="idle-step-icon">
                        <i class="fas fa-list-ul"></i>
                        <span class="idle-step-num">2</span>
                    </div>
                    <div class="idle-step-text">Choose a service</div>
                </div>
                <div class="idle-step-arrow"><i class="fas fa-chevron-right"></i></div>
                <div class="idle-step">
                    <div class="idle-step-icon">
                        <i class="fas fa-clipboard-check"></i>
                        <span class="idle-step-num">3</span>
                    </div>
                    <div class="idle-step-text">Confirm details</div>
                </div>
                <div class="idle-step-arrow"><i class="fas fa-chevron-right"></i></div>
                <div class="idle-step">
                    <div class="idle-step-icon">
                        <i class="fas fa-ticket-alt"></i>
                        <span class="idle-step-num">4</span>
                    </div>
                    <div class="idle-step-text">Get your queue number</div>
                </div>
            </div>

            <div class="idle-cta-wrap">
                <div class="idle-cta">
                    <span class="idle-cta-ring"></span>
                    <i class="fas fa-hand-pointer"></i>
                    <span>Tap anywhere to begin</span>
                </div>
            </div>

        </div>
    </div>

    <div class="topbar">
        <div class="topbar-brand">
            <div class="topbar-logo">
                <img src="img/olfu-logo.png" alt="Logo"
                    onerror="this.style.display='none';this.parentElement.innerHTML='<i class=\'fas fa-shield-alt\'></i>'">
            </div>
            <div class="topbar-text">
                <div class="topbar-name">Our Lady of Fatima University</div>
                <div class="topbar-sub">Queue Management System</div>
            </div>
        </div>
        <div class="topbar-spacer"></div>
        <div class="topbar-clock">
            <div class="topbar-time" id="topTime"></div>
            <div class="topbar-date" id="topDate"></div>
        </div>
    </div>

    <div class="screens">

        <div class="screen active" id="screen1">
            <div class="screen-head">
                <div class="eyebrow">Step 1 of 3</div>
                <h1 class="screen-title">Select an Office</h1>
            </div>
            <div class="screen-body">
                <div class="office-grid stagger" id="officeGrid"></div>
            </div>
        </div>

        <div class="screen" id="screen2">
            <div class="screen-head screen-head-with-nav">
                <button class="btn-nav-back" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <div style="text-align:center">
                    <div class="eyebrow">Step 2 of 3</div>
                    <h1 class="screen-title">Select a Service</h1>
                    <div style="margin-top:14px">
                        <div class="office-label-pill">
                            <i class="fas fa-building" style="font-size:12px"></i>
                            <span id="s2OfficeName"></span>
                        </div>
                    </div>
                </div>
                <div style="width:48px"></div>
            </div>
            <div class="screen-body" style="padding-top:12px">
                <div class="service-grid stagger" id="serviceGrid"></div>
            </div>
        </div>

        <div class="screen" id="screen3">
            <div class="screen-head screen-head-with-nav">
                <button class="btn-nav-back" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <div style="text-align:center">
                    <div class="eyebrow">Step 3 of 3</div>
                    <h1 class="screen-title">Confirm Your Queue</h1>
                </div>
            </div>
            <div class="screen-body">
                <div class="confirm-wrap">
                    <div class="confirm-card">
                        <div class="confirm-office" id="s3OfficeName"></div>
                        <div class="confirm-service" id="s3ServiceName"></div>
                        <div class="confirm-divider"></div>
                        <div class="confirm-preview-label">Your ticket number will be</div>
                        <div class="confirm-number" id="s3QueuePreview">—</div>
                        <div class="confirm-wait" id="s3WaitEstimate"></div>
                    </div>
                </div>
            </div>
            <div class="screen-foot">
                <button class="btn btn-primary" id="confirmBtn" onclick="confirmQueue()">
                    <i class="fas fa-check-circle"></i> Confirm &amp; Get Ticket
                </button>
            </div>
        </div>

        <div class="screen" id="screen4">
            <div class="screen-body" style="padding-top:20px;display:flex;align-items:flex-start;justify-content:center">
                <div class="ticket-wrap">
                    <div class="ticket-card" style="animation:fadeUp .4s var(--ease) both">
                        <div class="ticket-stripe"></div>
                        <div class="ticket-body">
                            <div class="ticket-office" id="s4OfficeName"></div>
                            <div class="ticket-service" id="s4ServiceName"></div>
                            <div class="ticket-number" id="s4TicketNumber"></div>
                            <div class="ticket-stats">
                                <div>
                                    <div class="stat-label">Position in Queue</div>
                                    <div class="stat-value" id="s4PeopleAhead">—</div>
                                </div>
                                <div>
                                    <div class="stat-label">Est. Wait</div>
                                    <div class="stat-value" id="s4WaitTime">—</div>
                                </div>
                            </div>
                            <div class="qr-section">
                                <div id="qrCode"></div>
                                <div class="qr-label">
                                    <i class="fas fa-qrcode"></i> Scan to track your queue
                                </div>
                            </div>
                        </div>
                        <div class="countdown-section">
                            <div class="countdown-bar-track">
                                <div class="countdown-bar-fill" id="countdownBar"></div>
                            </div>
                            <div class="countdown-meta">
                                Resets in <strong id="countdownNum">15</strong>s
                            </div>
                            <button class="btn-extend" onclick="extendTime()">
                                <i class="fas fa-clock"></i> More time
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        const OFFICES = <?php echo $officesJson; ?>;
        const KIOSK_ENABLED = <?php echo json_encode($kioskEnabled); ?>;
        const IDLE_TIMEOUT_MS = <?php echo $idleTimeout * 1000; ?>;
    </script>
    <script src="scripts/index.js"></script>
</body>

</html>