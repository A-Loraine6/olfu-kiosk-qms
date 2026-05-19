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
    <style>
        :root {
            --green-900: #064e29;
            --green-800: #0a6635;
            --green-700: #008b43;
            --green-600: #00a850;
            --green-500: #00c45e;
            --green-100: #d6f5e5;
            --green-50: #edfaf3;
            --ink: #0d1f17;
            --ink-2: #2e4d3a;
            --ink-3: #5a7a68;
            --ink-4: #8eab9b;
            --bg: #f2f5f3;
            --surface: #ffffff;
            --border: rgba(0, 80, 40, .09);
            --border-md: rgba(0, 80, 40, .18);
            --font: 'Plus Jakarta Sans', sans-serif;
            --mono: 'DM Mono', monospace;
            --ease: cubic-bezier(.16, 1, .3, 1);
            --radius: 20px;
        }

        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--ink);
            min-height: 100%;
            -webkit-font-smoothing: antialiased;
        }

        .topbar {
            background: var(--green-800);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-logo {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1.5px solid rgba(255, 255, 255, .25);
            overflow: hidden;
            background: rgba(255, 255, 255, .1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .topbar-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .topbar-title {
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
        }

        .topbar-sub {
            font-size: 11px;
            color: rgba(255, 255, 255, .5);
            margin-top: 1px;
        }

        .page {
            max-width: 480px;
            margin: 0 auto;
            padding: 24px 16px 48px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 99px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 20px;
        }

        .pill-waiting {
            background: var(--green-50);
            color: var(--green-700);
        }

        .pill-serving {
            background: #fff3e0;
            color: #e07b1a;
        }

        .pill-completed {
            background: #e8f5e9;
            color: #1b5e20;
        }

        .pill-skipped {
            background: #f5f5f5;
            color: #616161;
        }

        .pill-onhold {
            background: #e3f2fd;
            color: #1565c0;
        }

        .pill-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
            animation: pillPulse 1.8s ease-in-out infinite;
        }

        .pill-completed .pill-dot,
        .pill-skipped .pill-dot {
            animation: none;
        }

        @keyframes pillPulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: .5;
                transform: scale(.8);
            }
        }

        .ticket-card {
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .06);
            margin-bottom: 20px;
        }

        .ticket-stripe {
            height: 6px;
            background: linear-gradient(90deg, var(--green-800), var(--green-500));
        }

        .ticket-stripe.stripe-serving {
            background: linear-gradient(90deg, #e07b1a, #f5c518);
        }

        .ticket-stripe.stripe-completed {
            background: linear-gradient(90deg, #388e3c, #66bb6a);
        }

        .ticket-stripe.stripe-skipped {
            background: #bdbdbd;
        }

        .ticket-body {
            padding: 28px 24px 32px;
            text-align: center;
        }

        .ticket-office {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--ink-4);
            margin-bottom: 4px;
        }

        .ticket-service {
            font-size: 17px;
            font-weight: 600;
            color: var(--ink-2);
            margin-bottom: 24px;
            line-height: 1.4;
        }

        .ticket-number {
            font-family: var(--mono);
            font-size: clamp(60px, 18vw, 88px);
            font-weight: 500;
            color: var(--green-700);
            letter-spacing: -.05em;
            line-height: 1;
            margin-bottom: 24px;
        }

        .ticket-number.serving-num {
            color: #e07b1a;
        }

        .ticket-number.done-num {
            color: #388e3c;
        }

        .stat-row {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-box {
            flex: 1;
            background: var(--bg);
            border-radius: 14px;
            padding: 16px 12px;
            text-align: center;
        }

        .stat-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ink-4);
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -.03em;
            font-variant-numeric: tabular-nums;
        }

        .stat-value.highlight {
            color: var(--green-700);
        }

        .now-serving-banner {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--green-50);
            border: 1.5px solid var(--green-100);
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 24px;
        }

        .ns-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--green-700);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 16px;
            flex-shrink: 0;
        }

        .ns-label {
            font-size: 12px;
            color: var(--ink-4);
            font-weight: 600;
            margin-bottom: 2px;
        }

        .ns-value {
            font-family: var(--mono);
            font-size: 20px;
            font-weight: 500;
            color: var(--ink);
        }

        .alert-strip {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .alert-strip i {
            font-size: 16px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .alert-warn {
            background: #fff8e1;
            color: #e65100;
        }

        .alert-info {
            background: var(--green-50);
            color: var(--green-800);
        }

        .alert-done {
            background: #e8f5e9;
            color: #1b5e20;
        }

        .notif-card {
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .notif-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--green-50);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--green-700);
            font-size: 20px;
            flex-shrink: 0;
        }

        .notif-text {
            flex: 1;
        }

        .notif-text strong {
            display: block;
            font-size: 14px;
            margin-bottom: 3px;
        }

        .notif-text p {
            font-size: 13px;
            color: var(--ink-3);
            line-height: 1.4;
        }

        .btn-notif {
            flex-shrink: 0;
            padding: 10px 18px;
            background: var(--green-700);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: var(--font);
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s;
        }

        .btn-notif:hover {
            background: var(--green-800);
        }

        .btn-notif:disabled {
            opacity: .5;
            pointer-events: none;
        }

        .done-wrap {
            text-align: center;
            padding: 32px 0;
        }

        .done-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #e8f5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #388e3c;
            margin: 0 auto 20px;
        }

        .done-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .done-sub {
            font-size: 15px;
            color: var(--ink-3);
            line-height: 1.6;
        }

        .page-foot {
            text-align: center;
            margin-top: 32px;
            font-size: 12px;
            color: var(--ink-4);
            line-height: 1.8;
        }

        .error-wrap {
            min-height: 80vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 24px;
        }

        .error-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #fff3e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #e07b1a;
            margin: 0 auto 24px;
        }

        .error-title {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .error-sub {
            font-size: 15px;
            color: var(--ink-3);
            line-height: 1.6;
        }

        .skeleton {
            background: linear-gradient(90deg, #e8ede9 25%, #f2f5f2 50%, #e8ede9 75%);
            background-size: 200% 100%;
            animation: shimmer 1.4s infinite;
            border-radius: 8px;
        }

        @keyframes shimmer {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        .skel-number {
            height: 80px;
            width: 180px;
            margin: 0 auto 24px;
            border-radius: 12px;
        }

        .skel-row {
            height: 72px;
            border-radius: 14px;
        }

        .refresh-hint {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            color: var(--ink-4);
            margin-top: 12px;
        }

        .refresh-hint i {
            font-size: 12px;
        }

        .spin {
            animation: spin 1.2s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .service-info {
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .service-info-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }

        .service-info-row:last-child {
            margin-bottom: 0;
        }

        .service-info-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--green-50);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--green-700);
            font-size: 12px;
            flex-shrink: 0;
        }

        .service-info-content {
            flex: 1;
        }

        .service-info-label {
            font-weight: 700;
            color: var(--ink-2);
            margin-bottom: 2px;
        }

        .service-info-value {
            color: var(--ink-4);
            line-height: 1.4;
        }

        .service-info-value a {
            color: var(--green-700);
            font-weight: 600;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse-glow {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(0, 184, 80, 0.7);
            }

            50% {
                box-shadow: 0 0 0 12px rgba(0, 184, 80, 0);
            }
        }

        .alert-strip.serving-alert {
            animation: slideInUp 0.4s ease, pulse-glow 2s ease-in-out infinite;
        }

        .done-wrap.completion {
            animation: slideInUp 0.6s ease;
        }

        .confetti {
            position: fixed;
            pointer-events: none;
            font-size: 20px;
            animation: confettiFall 3s ease-out forwards;
        }

        .retry-card {
            background: #fff3e0;
            border: 1.5px solid #ffe0b2;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .retry-content {
            flex: 1;
            font-size: 13px;
        }

        .retry-label {
            font-weight: 700;
            color: #e07b1a;
            margin-bottom: 2px;
        }

        .retry-text {
            color: #e07b1a;
            opacity: 0.8;
        }

        .btn-retry {
            padding: 10px 18px;
            background: #e07b1a;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: var(--font);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .btn-retry:hover {
            background: #c66a0f;
        }

        .btn-retry:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-right: 8px;
        }

        .indicator-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .indicator-pattern {
            display: inline-block;
            width: 12px;
            height: 12px;
            margin: 0 2px;
        }

        .pattern-waiting {
            background: repeating-linear-gradient(45deg, var(--green-700), var(--green-700) 2px, var(--green-500) 2px, var(--green-500) 4px);
        }

        .pattern-serving {
            background: repeating-linear-gradient(45deg, #e07b1a, #e07b1a 2px, #f5c518 2px, #f5c518 4px);
        }

        .pattern-completed {
            background: repeating-linear-gradient(45deg, #388e3c, #388e3c 2px, #66bb6a 2px, #66bb6a 4px);
        }
    </style>
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

            <div class="page-foot">
                © Our Lady of Fatima University &mdash; Queue Management System
            </div>
        </div>

        <script>
            const TOKEN = <?php echo json_encode($token); ?>;
            const API_URL = <?php echo json_encode(BASE_URL . 'api/track_status.php'); ?> +
                '?token=' + encodeURIComponent(TOKEN);

            let lastPeopleAhead = null;
            let notifiedNearTurn = false;
            let notifPermission = Notification.permission;
            let pollInterval = null;
            let lastStatus = null;
            let secondsTillRefresh = 8;
            const _soundPref = localStorage.getItem('qms_sound_enabled');
            let soundEnabled = _soundPref === null ? true : _soundPref === 'true';
            let isOffline = false;
            let totalQueueSize = 0;

            let audioContext = null;
            let pendingSound = null;
            let userHasInteracted = false;

            function ensureAudio() {
                if (!audioContext) {
                    try {
                        audioContext = new(window.AudioContext || window.webkitAudioContext)();
                    } catch (e) {
                        return false;
                    }
                }
                return true;
            }

            function playNotificationSound(type = 'alert') {
                if (!soundEnabled) return;
                if (!userHasInteracted) {
                    pendingSound = type;
                    return;
                }
                if (!ensureAudio()) return;

                const play = () => {
                    try {
                        if (type === 'alert') {
                            const osc = audioContext.createOscillator();
                            const gain = audioContext.createGain();
                            osc.connect(gain);
                            gain.connect(audioContext.destination);
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(1000, audioContext.currentTime);
                            gain.gain.setValueAtTime(1.0, audioContext.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.8);
                            osc.start(audioContext.currentTime);
                            osc.stop(audioContext.currentTime + 0.8);

                        } else if (type === 'turn') {
                            [0, 0.35, 0.7].forEach(offset => {
                                const osc = audioContext.createOscillator();
                                const gain = audioContext.createGain();
                                osc.connect(gain);
                                gain.connect(audioContext.destination);
                                osc.type = 'sine';
                                osc.frequency.setValueAtTime(1200, audioContext.currentTime + offset);
                                gain.gain.setValueAtTime(1.0, audioContext.currentTime + offset);
                                gain.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + offset + 0.25);
                                osc.start(audioContext.currentTime + offset);
                                osc.stop(audioContext.currentTime + offset + 0.25);
                            });

                        } else if (type === 'complete') {
                            [523, 659, 784].forEach((freq, i) => {
                                const osc = audioContext.createOscillator();
                                const gain = audioContext.createGain();
                                osc.connect(gain);
                                gain.connect(audioContext.destination);
                                osc.type = 'sine';
                                osc.frequency.setValueAtTime(freq, audioContext.currentTime + i * 0.2);
                                gain.gain.setValueAtTime(1.0, audioContext.currentTime + i * 0.2);
                                gain.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + i * 0.2 + 0.4);
                                osc.start(audioContext.currentTime + i * 0.2);
                                osc.stop(audioContext.currentTime + i * 0.2 + 0.4);
                            });
                        }
                    } catch (e) {
                        console.warn('Audio playback failed:', e);
                    }
                };

                if (audioContext.state === 'suspended') {
                    audioContext.resume().then(play);
                } else {
                    play();
                }
            }

            function triggerHaptic(pattern = 'medium') {
                if (!navigator.vibrate || !userHasInteracted) return;
                const patterns = {
                    light: [10],
                    medium: [30],
                    strong: [50],
                    alert: [20, 10, 20],
                    celebration: [20, 10, 20, 10, 30]
                };
                navigator.vibrate(patterns[pattern] || patterns.medium);
            }


            window.addEventListener('online', () => {
                isOffline = false;
                document.getElementById('retryCard').style.display = 'none';
                poll();
            });

            window.addEventListener('offline', () => {
                isOffline = true;
                document.getElementById('retryCard').style.display = 'flex';
            });

            function createConfetti() {
                const icon = document.getElementById('doneIcon');
                icon.style.transform = 'scale(0)';
                icon.style.transition = 'transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
                setTimeout(() => {
                    icon.style.transform = 'scale(1)';
                }, 50);
            }

            let swRegistration = null;

            window.addEventListener('DOMContentLoaded', () => {
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.register('/scripts/sw.js')
                        .then(reg => {
                            swRegistration = reg;
                        })
                        .catch(e => console.warn('SW registration failed:', e));
                }

                initNotifCard();
                initControls();
                poll();
                startPollTimer();
                if (!navigator.onLine) {
                    isOffline = true;
                    document.getElementById('retryCard').style.display = 'flex';
                }
            });

            function initControls() {
                const onFirstInteraction = () => {
                    if (userHasInteracted) return;
                    userHasInteracted = true;

                    try {
                        if (!audioContext) {
                            audioContext = new(window.AudioContext || window.webkitAudioContext)();
                        }
                        if (audioContext.state === 'suspended') {
                            audioContext.resume();
                        }
                        const osc = audioContext.createOscillator();
                        const gain = audioContext.createGain();
                        osc.connect(gain);
                        gain.connect(audioContext.destination);
                        gain.gain.setValueAtTime(0.001, audioContext.currentTime); // nearly silent
                        osc.start(audioContext.currentTime);
                        osc.stop(audioContext.currentTime + 0.1);
                    } catch (e) {}

                    if (pendingSound) {
                        const s = pendingSound;
                        pendingSound = null;
                        setTimeout(() => playNotificationSound(s), 200);
                    }
                };

                document.addEventListener('click', onFirstInteraction);
                document.addEventListener('touchstart', onFirstInteraction);

                let touchStartY = 0;
                document.addEventListener('touchstart', (e) => {
                    touchStartY = e.touches[0].clientY;
                });
                document.addEventListener('touchend', (e) => {
                    const touchEndY = e.changedTouches[0].clientY;
                    if (touchStartY < touchEndY && window.scrollY === 0) {
                        manualRetry();
                    }
                });
            }

            function manualRetry() {
                const btn = document.getElementById('manualRetryBtn');
                btn.disabled = true;
                btn.textContent = 'Retrying…';

                poll().finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-redo" style="margin-right:4px"></i>Retry now';
                });
            }

            function startPollTimer() {
                clearInterval(pollInterval);
                secondsTillRefresh = 8;
                updateRefreshHint();
                pollInterval = setInterval(() => {
                    secondsTillRefresh--;
                    if (secondsTillRefresh <= 0) {
                        poll();
                        secondsTillRefresh = 8;
                    }
                    updateRefreshHint();
                }, 1000);
            }

            function updateRefreshHint() {
                const icon = document.getElementById('refreshIcon');
                const text = document.getElementById('refreshText');
                if (!icon || !text) return;
                if (secondsTillRefresh <= 0) {
                    icon.classList.add('spin');
                    text.textContent = 'Refreshing…';
                } else {
                    icon.classList.remove('spin');
                    text.textContent = `Updates in ${secondsTillRefresh}s`;
                }
            }

            function poll() {
                return fetch(API_URL)
                    .then(r => r.json())
                    .then(data => {
                        document.getElementById('loadingState').style.display = 'none';
                        document.getElementById('mainState').style.display = 'block';

                        if (!data.success) {
                            showError(data.message || 'Could not load queue info.');
                            return;
                        }

                        render(data);
                    })
                    .catch(() => {
                        if (navigator.onLine) {
                            isOffline = true;
                            document.getElementById('retryCard').style.display = 'flex';
                        }
                        document.getElementById('refreshText').textContent = 'Connection error — retrying…';
                    });
            }

            function render(data) {
                const {
                    ticket_number,
                    office_name,
                    service_name,
                    status,
                    people_ahead,
                    now_serving,
                    est_wait_mins,
                    expired,
                    total_in_queue,
                    counter_window,
                    service_hours,
                    location_info
                } = data;

                totalQueueSize = total_in_queue || (people_ahead + 1);

                document.getElementById('officeName').textContent = office_name;
                document.getElementById('serviceName').textContent = service_name;
                document.getElementById('ticketNumber').textContent = ticket_number;
                document.getElementById('ticketNumber').setAttribute('aria-label', `Your ticket number is ${ticket_number}`);

                renderStatusPill(status, people_ahead);

                const stripe = document.getElementById('ticketStripe');
                stripe.className = 'ticket-stripe';
                if (status === 'serving') stripe.classList.add('stripe-serving');
                if (status === 'completed') stripe.classList.add('stripe-completed');
                if (status === 'skipped') stripe.classList.add('stripe-skipped');

                const numEl = document.getElementById('ticketNumber');
                numEl.className = 'ticket-number';
                if (status === 'serving') numEl.classList.add('serving-num');
                if (status === 'completed' || status === 'skipped') numEl.classList.add('done-num');

                if (expired || status === 'completed' || status === 'skipped') {
                    renderDone(status);
                    clearInterval(pollInterval);
                    document.getElementById('refreshHint').style.display = 'none';
                    triggerHaptic('celebration');
                    playNotificationSound('complete');
                    createConfetti();
                    return;
                }

                document.getElementById('doneState').style.display = 'none';
                document.getElementById('ticketCard').style.display = 'block';

                const statRow = document.getElementById('statRow');

                if (status === 'serving') {
                    statRow.style.display = 'none';
                } else {
                    statRow.style.display = 'flex';

                    const aheadEl = document.getElementById('peopleAhead');
                    aheadEl.textContent = people_ahead === 0 ?
                        'You\'re next!' :
                        people_ahead;
                    aheadEl.setAttribute('aria-label', people_ahead === 0 ? 'You are next in queue' : `${people_ahead} people ahead of you`);

                    const waitEl = document.getElementById('estWait');
                    waitEl.textContent = est_wait_mins === 0 ?
                        'Now' :
                        `~${est_wait_mins}m`;
                }

                const nsBanner = document.getElementById('nowServingBanner');
                if (now_serving) {
                    nsBanner.style.display = 'flex';
                    document.getElementById('nowServingNum').textContent = now_serving;
                } else {
                    nsBanner.style.display = 'none';
                }

                renderServiceInfo(counter_window, service_hours, location_info);

                renderAlerts(status, people_ahead);

                if (lastStatus !== null && lastStatus !== 'serving' && status === 'serving') {
                    sendNotification(
                        '🔔 It\'s your turn!',
                        `${ticket_number} — please proceed to the window now.`
                    );
                }

                checkNearTurnNotification(people_ahead);

                lastPeopleAhead = people_ahead;
                lastStatus = status;
            }

            function renderStatusPill(status, peopleAhead) {
                const pill = document.getElementById('statusPill');
                const map = {
                    waiting: peopleAhead === 0 ? ['pill-serving', 'You\'re next!', '🎯'] : ['pill-waiting', 'Waiting', '📍'],
                    serving: ['pill-serving', 'Now serving you', '🎯'],
                    completed: ['pill-completed', 'Completed', '✅'],
                    skipped: ['pill-skipped', 'Skipped', '⏭️'],
                    'on-hold': ['pill-onhold', 'On hold', '⏸️'],
                };
                const [cls, label] = map[status] || ['pill-waiting', status];
                pill.innerHTML = `<span class="status-pill ${cls}"><span class="pill-dot"></span>${label}</span>`;
            }

            function renderServiceInfo(counterWindow, serviceHours, locationInfo) {
                const serviceInfoDiv = document.getElementById('serviceInfo');

                if (!counterWindow && !serviceHours && !locationInfo) {
                    serviceInfoDiv.style.display = 'none';
                    return;
                }

                serviceInfoDiv.style.display = 'block';

                const hoursRow = document.getElementById('serviceHours');
                if (serviceHours) {
                    hoursRow.style.display = 'flex';
                    document.getElementById('hoursValue').textContent = serviceHours;
                } else {
                    hoursRow.style.display = 'none';
                }

                const counterRow = document.getElementById('counterInfo');
                if (counterWindow) {
                    counterRow.style.display = 'flex';
                    document.getElementById('counterValue').textContent = counterWindow;
                } else {
                    counterRow.style.display = 'none';
                }

                const dirRow = document.getElementById('directionsInfo');
                if (locationInfo) {
                    dirRow.style.display = 'flex';
                    const link = document.getElementById('directionsLink');
                    link.href = `https://maps.google.com/?q=${encodeURIComponent(locationInfo)}`;
                    link.textContent = 'Get directions';
                } else {
                    dirRow.style.display = 'none';
                }
            }

            function renderAlerts(status, peopleAhead) {
                const area = document.getElementById('alertArea');
                area.innerHTML = '';

                if (status === 'serving') {
                    area.innerHTML = `
                    <div class="alert-strip alert-warn serving-alert">
                        <i class="fas fa-circle-exclamation"></i>
                        <span>It's your turn! Please proceed to the window now.</span>
                    </div>`;
                    triggerHaptic('strong');
                    if (lastStatus !== 'serving') playNotificationSound('turn');
                    return;
                }

                if (status === 'on-hold') {
                    area.innerHTML = `
                    <div class="alert-strip alert-info">
                        <i class="fas fa-pause-circle"></i>
                        <span>Your ticket is on hold. Please wait for further updates.</span>
                    </div>`;
                    return;
                }

                if (peopleAhead <= 5 && peopleAhead > 0) {
                    area.innerHTML = `
                    <div class="alert-strip alert-info">
                        <i class="fas fa-bell"></i>
                        <span><strong>${peopleAhead} ${peopleAhead === 1 ? 'person' : 'people'} ahead</strong> — head to the office now.</span>
                    </div>`;
                    if (lastPeopleAhead === null || peopleAhead < lastPeopleAhead) {
                        triggerHaptic('alert');
                        playNotificationSound('alert');
                    }
                }
            }

            function renderDone(status) {
                document.getElementById('ticketCard').style.display = 'none';
                document.getElementById('doneState').style.display = 'block';
                document.getElementById('doneState').classList.add('completion');
                document.getElementById('alertArea').innerHTML = '';

                const title = document.getElementById('doneTitle');
                const sub = document.getElementById('doneSub');
                const icon = document.getElementById('doneIcon');
                icon.classList.add('celebration');

                if (status === 'completed') {
                    title.textContent = 'Transaction complete!';
                    sub.innerHTML = 'Your transaction has been completed.<br>Thank you for visiting OLFU.';
                } else if (status === 'skipped') {
                    title.textContent = 'Ticket skipped';
                    sub.innerHTML = 'Your number was called but was skipped.<br>Please approach a staff member for assistance.';
                } else {
                    title.textContent = 'Queue closed';
                    sub.innerHTML = 'This ticket is no longer active.';
                }
            }

            function showError(msg) {
                document.getElementById('ticketCard').style.display = 'none';
                document.getElementById('alertArea').innerHTML = `
        <div class="alert-strip alert-warn">
            <i class="fas fa-triangle-exclamation"></i> ${msg}
        </div>`;
            }

            function initNotifCard() {
                if (!('Notification' in window)) return;
                if (Notification.permission === 'granted') {
                    document.getElementById('notifCard').style.display = 'none';
                    return;
                }
                if (Notification.permission === 'denied') return;
                document.getElementById('notifCard').style.display = 'flex';
            }

            function requestNotifPermission() {
                if (!('Notification' in window)) return;
                const btn = document.getElementById('notifBtn');
                btn.disabled = true;
                btn.textContent = 'Asking…';
                Notification.requestPermission().then(perm => {
                    notifPermission = perm;
                    if (perm === 'granted') {
                        document.getElementById('notifCard').style.display = 'none';
                        if ('serviceWorker' in navigator && !swRegistration) {
                            navigator.serviceWorker.register('sw.js')
                                .then(reg => {
                                    swRegistration = reg;
                                });
                        }
                    } else {
                        btn.disabled = false;
                        btn.textContent = 'Blocked';
                    }
                });
            }

            function checkNearTurnNotification(peopleAhead) {
                if (notifiedNearTurn) return;
                if (Notification.permission !== 'granted') return;

                if (lastPeopleAhead === null) return;
                if (lastPeopleAhead > 5 && peopleAhead <= 5) {
                    notifiedNearTurn = true;
                    sendNotification(
                        '⏰ Almost your turn!',
                        `Only ${peopleAhead} ${peopleAhead === 1 ? 'person' : 'people'} ahead of you. Please make your way to the office.`
                    );
                }
            }

            function sendNotification(title, body) {
                if (Notification.permission !== 'granted') return;

                if (swRegistration) {
                    swRegistration.showNotification(title, {
                        body,
                        icon: 'img/olfu-logo.png',
                        badge: 'img/olfu-logo.png',
                        tag: 'queue-tracker',
                        renotify: true,
                        vibrate: [200, 100, 200],
                    }).catch(e => console.warn('SW notification failed:', e));
                    return;
                }

                try {
                    const n = new Notification(title, {
                        body,
                        icon: 'img/olfu-logo.png',
                        tag: 'queue-tracker',
                        renotify: true
                    });
                    n.onclick = () => {
                        window.focus();
                        n.close();
                    };
                } catch (e) {}
            }
        </script>

    <?php endif; ?>

</body>

</html>