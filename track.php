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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a6635">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta property="og:title" content="Track Your Queue — OLFU">
    <meta property="og:description" content="Track your queue position at Our Lady of Fatima University.">
    <meta property="og:image" content="<?php echo htmlspecialchars(BASE_URL); ?>img/olfu-logo.png">
    <title>Track Your Queue</title>
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/track.css">
</head>

<body>

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

        <div class="app-shell">

            <div class="app-header">
                <div class="app-header-logo">
                    <img src="img/olfu-logo.png" alt="OLFU"
                        onerror="this.style.display='none';this.parentElement.innerHTML='<i class=\'fas fa-shield-alt\'></i>'">
                </div>
                <div class="app-header-text">
                    <div class="app-header-title">Our Lady of Fatima University</div>
                    <div class="app-header-sub">Queue Tracker</div>
                </div>
            </div>

            <div class="app-content">

                <div class="tab-pane active" id="tab-ticket">

                    <div id="loadingState" class="ticket-wrap">
                        <div class="skeleton" style="height:12px;width:90px;margin:0 auto 10px;border-radius:6px"></div>
                        <div class="skeleton" style="height:16px;width:170px;margin:0 auto 24px;border-radius:6px"></div>
                        <div class="skeleton" style="height:88px;width:180px;margin:0 auto 20px;border-radius:12px"></div>
                        <div style="display:flex;gap:12px">
                            <div class="skeleton" style="flex:1;height:72px;border-radius:14px"></div>
                            <div class="skeleton" style="flex:1;height:72px;border-radius:14px"></div>
                        </div>
                    </div>

                    <div id="mainState" style="display:none" class="ticket-wrap">

                        <div class="retry-card" id="retryCard" style="display:none">
                            <div>
                                <div class="retry-label"><i class="fas fa-wifi-slash"></i> Connection issue</div>
                                <div class="retry-text">Offline — will retry automatically</div>
                            </div>
                            <button class="btn-retry" id="manualRetryBtn" onclick="manualRetry()">
                                <i class="fas fa-redo"></i> Retry
                            </button>
                        </div>

                        <div class="notif-card" id="notifCard" style="display:none">
                            <div class="notif-icon"><i class="fas fa-bell"></i></div>
                            <div class="notif-text">
                                <strong>Get notified when you're close</strong>
                                <p>We'll alert you when 5 or fewer people are ahead.</p>
                            </div>
                            <button class="btn-notif" id="notifBtn" onclick="requestNotifPermission()">Enable</button>
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

                                <div class="now-serving-banner" id="nowServingBanner" style="display:none"
                                    role="status" aria-live="polite">
                                    <div class="ns-icon"><i class="fas fa-bullhorn"></i></div>
                                    <div>
                                        <div class="ns-label">Now serving</div>
                                        <div class="ns-value" id="nowServingNum">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="doneState" style="display:none" class="done-wrap">
                            <div class="done-icon" id="doneIcon"><i class="fas fa-circle-check"></i></div>
                            <div class="done-title" id="doneTitle">You're all set!</div>
                            <div class="done-sub" id="doneSub">Your transaction has been completed.<br>Thank you for visiting.</div>
                        </div>

                        <div class="refresh-hint" id="refreshHint" role="status" aria-live="polite">
                            <i class="fas fa-rotate-right" id="refreshIcon"></i>
                            <span id="refreshText">Updating…</span>
                        </div>

                        <div class="cancel-row" id="cancelRow">
                            <button class="cancel-btn" id="cancelTicketBtn" type="button" onclick="requestCancelTicket()">
                                <i class="fas fa-circle-xmark"></i> Cancel ticket
                            </button>
                        </div>

                    </div>
                </div>

                <div class="tab-pane" id="tab-map">
                    <div class="map-header-bar" id="mapHeaderBar">
                        <div class="map-info-row">
                            <div class="map-dest-info">
                                <div class="map-dest-label">Navigating to</div>
                                <div class="map-dest-name" id="mapDestName">—</div>
                            </div>
                            <div class="map-walk-chip" id="mapWalkChip" style="display:none">
                                <i class="fas fa-person-walking"></i>
                                <span id="mapWalkTime">—</span>
                            </div>
                        </div>
                        <div class="map-directions-bar" id="mapDirectionsBar" style="display:none">
                            <i class="fas fa-route"></i>
                            <span id="mapDirectionsText">—</span>
                        </div>
                        <div class="map-search-wrap">
                            <div class="map-search-row">
                                <i class="fas fa-magnifying-glass" id="mapSearchIcon"></i>
                                <input type="text" id="mapSearchInput" placeholder="Search buildings, offices…" autocomplete="off" />
                                <button type="button" id="mapSearchClear" aria-label="Clear search" style="display:none">
                                    <i class="fas fa-circle-xmark"></i>
                                </button>
                            </div>
                            <div id="mapSearchResults"></div>
                        </div>
                    </div>
                    <div id="campusMap"></div>
                </div>

            </div>

            <nav class="bottom-nav">
                <button class="nav-item active" id="nav-ticket" onclick="switchTab('ticket')" aria-label="Ticket">
                    <i class="fas fa-ticket"></i>
                    <span>Ticket</span>
                </button>
                <button class="nav-item" id="nav-map" onclick="switchTab('map')" aria-label="Map">
                    <i class="fas fa-map-location-dot"></i>
                    <span>Map</span>
                </button>
            </nav>

            <!-- Cancel ticket confirmation modal -->
            <div class="modal-overlay" id="cancelModalOverlay" role="dialog" aria-modal="true" aria-labelledby="cancelModalTitle">
                <div class="modal-card">
                    <div class="modal-icon"><i class="fas fa-triangle-exclamation"></i></div>
                    <div class="modal-title" id="cancelModalTitle">Cancel this ticket?</div>
                    <div class="modal-sub">You'll lose your place in line and will need to request a new ticket if you change your mind.</div>
                    <div class="modal-actions">
                        <button class="modal-btn modal-btn-secondary" id="modalKeepBtn" type="button">Keep ticket</button>
                        <button class="modal-btn modal-btn-danger" id="modalConfirmCancelBtn" type="button">Yes, cancel</button>
                    </div>
                </div>
            </div>

        </div>

        <script>
            const TOKEN = <?php echo json_encode($token); ?>;
            const API_URL = <?php echo json_encode(BASE_URL . 'api/track_status.php'); ?> + '?token=' + encodeURIComponent(TOKEN);
        </script>
        <script src="scripts/campus_map.js"></script>
        <script src="scripts/track.js"></script>

    <?php endif; ?>
</body>

</html>