<?php
require_once 'config/config.php';
date_default_timezone_set('Asia/Manila');

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

$tokenValid = (strlen($token) === 64 && ctype_xdigit($token));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#006633">
    <meta property="og:title" content="Track Your Queue — OLFU">
    <meta property="og:description" content="Track your queue position at Our Lady of Fatima University.">
    <meta property="og:image" content="<?php echo htmlspecialchars(BASE_URL); ?>img/olfu-logo.png">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Track Your Queue — OLFU">
    <meta name="twitter:description" content="Track your queue position at Our Lady of Fatima University.">
    <meta name="twitter:image" content="<?php echo htmlspecialchars(BASE_URL); ?>img/olfu-logo.png">
    <title>Track Your Queue — OLFU QMS</title>
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,600;0,700;0,800;1,400&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="css/track.css">
</head>

<body>

    <div class="topbar">
        <div class="topbar-logo">
            <img src="img/olfu-logo.png" alt="OLFU"
                onerror="this.style.display='none';this.parentElement.innerHTML='<i class=\'fas fa-shield-alt\' style=\'color:rgba(255,255,255,.7);font-size:14px\'></i>'">
        </div>
        <div>
            <div class="topbar-title">Our Lady of Fatima University</div>
            <div class="topbar-sub">Queue Tracker</div>
        </div>
    </div>

    <?php if (!$tokenValid): ?>
        <div class="error-wrap">
            <div class="error-icon"><i class="fas fa-link-slash"></i></div>
            <div class="error-title">Invalid tracking link</div>
            <div class="error-sub">
                This link is not valid or has already expired.<br>
                Please scan the QR code on your ticket again.
            </div>
        </div>
    <?php else: ?>
        <div class="page" id="trackerPage">
            <div id="loadingState">
                <div class="ticket-card">
                    <div class="ticket-stripe"></div>
                    <div class="ticket-body">
                        <div class="skeleton" style="height:14px;width:80px;margin:0 auto 16px"></div>
                        <div class="skeleton" style="height:18px;width:160px;margin:0 auto 28px"></div>
                        <div class="skeleton skel-number"></div>
                        <div class="stat-row">
                            <div class="skeleton skel-row" style="flex:1"></div>
                            <div class="skeleton skel-row" style="flex:1"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="mainState" style="display:none">

                <div class="retry-card" id="retryCard" style="display:none">
                    <div>
                        <div class="retry-label"><i class="fas fa-wifi-slash" style="margin-right:6px"></i>Connection issue</div>
                        <div class="retry-text">Offline — will retry automatically</div>
                    </div>
                    <button class="btn-retry" id="manualRetryBtn" onclick="manualRetry()">
                        <i class="fas fa-redo" style="margin-right:4px"></i>Retry now
                    </button>
                </div>

                <div class="notif-card" id="notifCard" style="display:none">
                    <div class="notif-icon"><i class="fas fa-bell"></i></div>
                    <div class="notif-text">
                        <strong>Get notified when you're close</strong>
                        <p>We'll alert you when there are 5 or fewer people ahead.</p>
                    </div>
                    <button class="btn-notif" id="notifBtn" onclick="requestNotifPermission()">
                        Enable
                    </button>
                </div>

                <div id="alertArea"></div>

                <div id="statusPill"></div>

                <div class="ticket-card" id="ticketCard">
                    <div class="ticket-stripe" id="ticketStripe"></div>
                    <div class="ticket-body">
                        <div class="ticket-office" id="officeName"></div>
                        <div class="ticket-service" id="serviceName"></div>
                        <div class="ticket-number" id="ticketNumber" role="img" aria-label="Your ticket number"></div>

                        <div class="stat-row" id="statRow">
                            <div class="stat-box">
                                <div class="stat-label">People ahead</div>
                                <div class="stat-value highlight" id="peopleAhead">—</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Est. wait</div>
                                <div class="stat-value" id="estWait">—</div>
                            </div>
                        </div>

                        <div class="now-serving-banner" id="nowServingBanner" style="display:none" role="status" aria-live="polite" aria-label="Now serving announcement">
                            <div class="ns-icon"><i class="fas fa-bullhorn"></i></div>
                            <div>
                                <div class="ns-label">Now serving</div>
                                <div class="ns-value" id="nowServingNum">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="service-info" id="serviceInfo" style="display:none">
                    <div class="service-info-row" id="serviceHours">
                        <div class="service-info-icon"><i class="fas fa-clock"></i></div>
                        <div class="service-info-content">
                            <div class="service-info-label">Service hours</div>
                            <div class="service-info-value" id="hoursValue">—</div>
                        </div>
                    </div>
                    <div class="service-info-row" id="counterInfo">
                        <div class="service-info-icon"><i class="fas fa-door-open"></i></div>
                        <div class="service-info-content">
                            <div class="service-info-label">Counter window</div>
                            <div class="service-info-value" id="counterValue">—</div>
                        </div>
                    </div>
                    <div class="service-info-row" id="directionsInfo">
                        <div class="service-info-icon"><i class="fas fa-map-location-dot"></i></div>
                        <div class="service-info-content">
                            <div class="service-info-label">Location</div>
                            <div class="service-info-value">
                                <a href="#" id="directionsLink" target="_blank">Get directions</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="doneState" style="display:none" class="done-wrap">
                    <div class="done-icon" id="doneIcon"><i class="fas fa-circle-check"></i></div>
                    <div class="done-title" id="doneTitle">You're all set!</div>
                    <div class="done-sub" id="doneSub">Your transaction has been completed.<br>Thank you for visiting.</div>
                </div>

                <div class="refresh-hint" id="refreshHint" role="status" aria-live="polite" aria-label="Update status">
                    <i class="fas fa-rotate-right" id="refreshIcon"></i>
                    <span id="refreshText">Updating…</span>
                </div>
            </div>


        </div>
        <div class="page-foot">
            © Our Lady of Fatima University &mdash; Queue Management System
        </div>

        <script>
            const TOKEN = <?php echo json_encode($token); ?>;
            const API_URL = <?php echo json_encode(BASE_URL . 'api/track_status.php'); ?> +
                '?token=' + encodeURIComponent(TOKEN);
        </script>
        <script src="scripts/track.js"></script>

    <?php endif; ?>

</body>

</html>