<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/api/settings_functions.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$currentUser = getCurrentUser();

if (!hasPermission('manage_settings')) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$csrf_token = getCsrfToken();

$offices      = getAllOffices($db->getConnection());
$kioskSettings = getKioskSettings($db->getConnection());

$activeOfficeId = isset($_GET['office']) ? (int) $_GET['office']
    : ($offices[0]['id'] ?? 0);
$activeTab      = in_array($_GET['tab'] ?? '', ['schedule', 'queue', 'kiosk'])
    ? $_GET['tab'] : 'schedule';

$activeOffice = null;
foreach ($offices as $o) {
    if ($o['id'] === $activeOfficeId) {
        $activeOffice = $o;
        break;
    }
}
if (!$activeOffice && !empty($offices)) {
    $activeOffice   = $offices[0];
    $activeOfficeId = $activeOffice['id'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/settings.css">
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="settings-container">

        <?php include 'includes/sidebar.php'; ?>

        <div class="settings-body">

            <header class="dashboard-header">
                <div class="header-left">
                    <h2 class="header-page-title">
                        <i class="fas fa-cog"></i> System Settings
                    </h2>
                </div>
                <div class="header-right">
                    <button type="button" id="headerSaveSchedule"
                        class="btn-primary header-save-btn"
                        style="<?php echo $activeTab !== 'schedule' ? 'display:none;' : ''; ?>"
                        onclick="document.getElementById('scheduleForm').requestSubmit()">
                        <i class="fas fa-save"></i> Save Schedule
                    </button>
                    <button type="button" id="headerSaveQueue"
                        class="btn-primary header-save-btn"
                        style="<?php echo $activeTab !== 'queue' ? 'display:none;' : ''; ?>"
                        onclick="document.getElementById('queueForm').requestSubmit()">
                        <i class="fas fa-save"></i> Save Queue Config
                    </button>
                    <button type="button" id="headerSaveKiosk"
                        class="btn-primary header-save-btn"
                        style="<?php echo $activeTab !== 'kiosk' ? 'display:none;' : ''; ?>"
                        onclick="document.getElementById('kioskForm').requestSubmit()">
                        <i class="fas fa-save"></i> Save Kiosk Settings
                    </button>
                </div>
            </header>

            <nav class="settings-tabs">
                <button class="settings-tab <?php echo $activeTab === 'schedule' ? 'active' : ''; ?>"
                    onclick="switchTab('schedule')">
                    <i class="fas fa-clock"></i> Office Schedule
                </button>
                <button class="settings-tab <?php echo $activeTab === 'queue' ? 'active' : ''; ?>"
                    onclick="switchTab('queue')">
                    <i class="fas fa-ticket-alt"></i> Queue Configuration
                </button>
                <button class="settings-tab <?php echo $activeTab === 'kiosk' ? 'active' : ''; ?>"
                    onclick="switchTab('kiosk')">
                    <i class="fas fa-tablet-alt"></i> Kiosk Settings
                </button>
            </nav>

            <div class="dashboard-main">
                <div class="dashboard-content">

                    <div id="tab-schedule"
                        class="tab-panel <?php echo $activeTab === 'schedule' ? 'active' : ''; ?>">
                        <div class="settings-grid">

                            <div class="office-selector">
                                <div class="office-selector-header">Offices</div>
                                <ul class="office-selector-list">
                                    <?php foreach ($offices as $o): ?>
                                        <li class="office-selector-item
                                    <?php echo $o['id'] === $activeOfficeId ? 'active' : ''; ?>"
                                            onclick="switchOffice(<?php echo $o['id']; ?>, 'schedule')">
                                            <i class="fas fa-building"></i>
                                            <?php echo htmlspecialchars($o['name']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <div class="settings-sections">
                                <?php if ($activeOffice): ?>
                                    <form id="scheduleForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="save_schedule">
                                        <input type="hidden" name="office_id" value="<?php echo $activeOfficeId; ?>">

                                        <div class="settings-section">
                                            <div class="settings-section-header">
                                                <div class="settings-section-icon">
                                                    <i class="fas fa-door-open"></i>
                                                </div>
                                                <div>
                                                    <div class="settings-section-title">Operating Hours</div>
                                                    <div class="settings-section-desc">
                                                        Define when <?php echo htmlspecialchars($activeOffice['name']); ?> opens and closes each day
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="settings-section-body">
                                                <div class="settings-row">
                                                    <div class="field">
                                                        <label class="field-label">
                                                            <i class="fas fa-sun" style="color:var(--primary);font-size:11px;"></i>
                                                            Opening Time
                                                        </label>
                                                        <input type="time" name="open"
                                                            value="<?php echo htmlspecialchars($activeOffice['open_time'] ?: '08:00'); ?>">
                                                        <div class="field-hint">Time when the office starts accepting queues.</div>
                                                    </div>
                                                    <div class="field">
                                                        <label class="field-label">
                                                            <i class="fas fa-moon" style="color:var(--primary);font-size:11px;"></i>
                                                            Closing Time
                                                        </label>
                                                        <input type="time" name="close"
                                                            value="<?php echo htmlspecialchars($activeOffice['close_time'] ?: '17:00'); ?>">
                                                        <div class="field-hint">Time when the office stops all operations.</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="settings-section">
                                            <div class="settings-section-header">
                                                <div class="settings-section-icon">
                                                    <i class="fas fa-coffee"></i>
                                                </div>
                                                <div>
                                                    <div class="settings-section-title">Break Time</div>
                                                    <div class="settings-section-desc">Queues are paused automatically during this period</div>
                                                </div>
                                            </div>
                                            <div class="settings-section-body">
                                                <div class="settings-row">
                                                    <div class="field">
                                                        <label class="field-label">Break Start</label>
                                                        <input type="time" name="break_start"
                                                            value="<?php echo htmlspecialchars($activeOffice['break_start']); ?>">
                                                    </div>
                                                    <div class="field">
                                                        <label class="field-label">Break End</label>
                                                        <input type="time" name="break_end"
                                                            value="<?php echo htmlspecialchars($activeOffice['break_end']); ?>">
                                                    </div>
                                                </div>
                                                <div class="field-hint" style="margin-top:4px;">
                                                    Leave both blank if this office has no break period.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="settings-section">
                                            <div class="settings-section-header">
                                                <div class="settings-section-icon">
                                                    <i class="fas fa-stopwatch"></i>
                                                </div>
                                                <div>
                                                    <div class="settings-section-title">Queue Cut-off Time</div>
                                                    <div class="settings-section-desc">Latest time a user can join the queue</div>
                                                </div>
                                            </div>
                                            <div class="settings-section-body">
                                                <div class="settings-row" style="grid-template-columns:1fr 1fr;">
                                                    <div class="field">
                                                        <label class="field-label">Cut-off Time</label>
                                                        <div style="display:flex; align-items:center; gap:8px;">
                                                            <input type="time" name="cutoff" id="cutoffInput"
                                                                value="<?php echo htmlspecialchars($activeOffice['queue_cutoff']); ?>">
                                                            <button type="button" onclick="clearCutoff()"
                                                                title="Clear cut-off time"
                                                                style="background:none; border:1px solid var(--border); border-radius:8px; padding:13px 16px; cursor:pointer; color:var(--text-muted); font-size:14px;">
                                                                <i class="fas fa-times"></i> 
                                                            </button>
                                                        </div>
                                                        <div class="field-hint">Press clear to leave blank. Time must be before closing.</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>

                    <div id="tab-queue"
                        class="tab-panel <?php echo $activeTab === 'queue' ? 'active' : ''; ?>">
                        <div class="settings-grid">

                            <div class="office-selector">
                                <div class="office-selector-header">Offices</div>
                                <ul class="office-selector-list">
                                    <?php foreach ($offices as $o): ?>
                                        <li class="office-selector-item
                                    <?php echo $o['id'] === $activeOfficeId ? 'active' : ''; ?>"
                                            onclick="switchOffice(<?php echo $o['id']; ?>, 'queue')">
                                            <i class="fas fa-building"></i>
                                            <?php echo htmlspecialchars($o['name']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <div class="settings-sections">
                                <?php if ($activeOffice): ?>
                                    <form id="queueForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="save_queue">
                                        <input type="hidden" name="office_id" value="<?php echo $activeOfficeId; ?>">

                                        <div class="settings-section">
                                            <div class="settings-section-header">
                                                <div class="settings-section-icon">
                                                    <i class="fas fa-hashtag"></i>
                                                </div>
                                                <div>
                                                    <div class="settings-section-title">Queue Number Format</div>
                                                    <div class="settings-section-desc">
                                                        Configure how ticket numbers appear for <?php echo htmlspecialchars($activeOffice['name']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="settings-section-body">
                                                <div class="settings-row">
                                                    <div class="field">
                                                        <label class="field-label">Prefix</label>
                                                        <input type="text" name="queue_prefix" id="prefixInput"
                                                            maxlength="10" placeholder="e.g. REG, ACC, A"
                                                            value="<?php echo htmlspecialchars($activeOffice['queue_prefix']); ?>"
                                                            oninput="updatePreview()">
                                                        <div class="field-hint">Uppercase letters, digits, or hyphens. Leave blank for number-only format.</div>
                                                    </div>
                                                    <div class="field">
                                                        <label class="field-label">Starting Number</label>
                                                        <input type="number" name="queue_start" id="startInput"
                                                            min="1" max="9999"
                                                            value="<?php echo (int) $activeOffice['queue_start_num']; ?>"
                                                            oninput="updatePreview()">
                                                        <div class="field-hint">Queue resets to this number each day if daily reset is on.</div>
                                                    </div>
                                                </div>
                                                <div class="queue-preview" id="queuePreview">
                                                    <i class="fas fa-ticket-alt"></i>
                                                    <?php echo htmlspecialchars(buildQueuePreview(
                                                        $activeOffice['queue_prefix'],
                                                        $activeOffice['queue_start_num']
                                                    )); ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="settings-section">
                                            <div class="settings-section-header">
                                                <div class="settings-section-icon">
                                                    <i class="fas fa-sliders-h"></i>
                                                </div>
                                                <div>
                                                    <div class="settings-section-title">Queue Behaviour</div>
                                                    <div class="settings-section-desc">Control limits, resets, and auto-close rules</div>
                                                </div>
                                            </div>
                                            <div class="settings-section-body">
                                                <div class="field" style="margin-bottom:20px;">
                                                    <label class="field-label">Maximum Tickets Per Day</label>
                                                    <input type="number" name="max_limit"
                                                        min="0" max="9999"
                                                        value="<?php echo (int) $activeOffice['max_queue_limit']; ?>"
                                                        style="max-width:200px;">
                                                    <div class="field-hint">Set to <strong>0</strong> for unlimited. System stops issuing tickets once this number is reached.</div>
                                                </div>

                                                <div class="settings-divider"></div>

                                                <div class="toggle-row">
                                                    <div class="toggle-info">
                                                        <div class="toggle-label">Daily Queue Reset</div>
                                                        <div class="toggle-desc">Reset queue numbers back to the starting value at midnight each day.</div>
                                                    </div>
                                                    <label class="toggle-switch">
                                                        <input type="checkbox" name="daily_reset" value="1"
                                                            <?php echo $activeOffice['daily_reset'] ? 'checked' : ''; ?>>
                                                        <span class="toggle-track"></span>
                                                    </label>
                                                </div>

                                                <div class="toggle-row">
                                                    <div class="toggle-info">
                                                        <div class="toggle-label">Auto-Close Queue</div>
                                                        <div class="toggle-desc">Automatically stop issuing tickets when the maximum limit is reached or the cut-off time arrives.</div>
                                                    </div>
                                                    <label class="toggle-switch">
                                                        <input type="checkbox" name="auto_close" value="1"
                                                            <?php echo $activeOffice['auto_close'] ? 'checked' : ''; ?>>
                                                        <span class="toggle-track"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                    </form>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>

                    <div id="tab-kiosk"
                        class="tab-panel <?php echo $activeTab === 'kiosk' ? 'active' : ''; ?>">

                        <form id="kioskForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="action" value="save_kiosk">

                            <div class="settings-sections">

                                <?php $kioskOn = $kioskSettings['kiosk_enabled']; ?>
                                <div class="kiosk-status-card <?php echo $kioskOn ? 'status-on' : 'status-off'; ?>"
                                    id="kioskStatusCard">
                                    <div class="kiosk-status-icon">
                                        <i class="fas fa-<?php echo $kioskOn ? 'tablet-alt' : 'ban'; ?>"></i>
                                    </div>
                                    <div class="kiosk-status-info">
                                        <div class="kiosk-status-label" id="kioskStatusLabel">
                                            Kiosk is <?php echo $kioskOn ? 'Enabled' : 'Disabled'; ?>
                                        </div>
                                        <div class="kiosk-status-sub" id="kioskStatusSub">
                                            <?php echo $kioskOn
                                                ? 'Users can interact with the kiosk to get queue numbers.'
                                                : 'The kiosk is turned off. Users cannot generate queue numbers.'; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="settings-section">
                                    <div class="settings-section-header">
                                        <div class="settings-section-icon">
                                            <i class="fas fa-tablet-alt"></i>
                                        </div>
                                        <div>
                                            <div class="settings-section-title">Kiosk Access</div>
                                            <div class="settings-section-desc">Control whether the kiosk is available to users</div>
                                        </div>
                                    </div>
                                    <div class="settings-section-body">
                                        <div class="toggle-row">
                                            <div class="toggle-info">
                                                <div class="toggle-label">Enable Kiosk</div>
                                                <div class="toggle-desc">
                                                    Turning this off prevents users from interacting with the kiosk while keeping all other system functions running.
                                                </div>
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="kiosk_enabled" value="1"
                                                    id="kioskToggle"
                                                    <?php echo $kioskOn ? 'checked' : ''; ?>
                                                    onchange="updateKioskStatus(this.checked)">
                                                <span class="toggle-track"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="settings-section">
                                    <div class="settings-section-header">
                                        <div class="settings-section-icon">
                                            <i class="fas fa-hourglass-half"></i>
                                        </div>
                                        <div>
                                            <div class="settings-section-title">Idle Timeout</div>
                                            <div class="settings-section-desc">
                                                How long before the kiosk resets to the main screen after no interaction
                                            </div>
                                        </div>
                                    </div>
                                    <div class="settings-section-body">
                                        <div class="settings-row" style="grid-template-columns:1fr 1fr;">
                                            <div class="field">
                                                <label class="field-label">Timeout Duration (seconds)</label>
                                                <input type="number" name="idle_timeout"
                                                    min="10" max="600"
                                                    value="<?php echo (int) $kioskSettings['idle_timeout']; ?>">
                                                <div class="field-hint">Between 10 – 600 seconds. After this idle period the kiosk returns to the welcome screen automatically.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <script>
        const CSRF_TOKEN = <?php echo json_encode($csrf_token); ?>;
    </script>

    <script src="scripts/settings.js"></script>

</body>

</html>