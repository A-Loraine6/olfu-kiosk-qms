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
if (($currentUser['role'] ?? '') !== 'staff') {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

if (!hasPermission('manage_queue') && !hasPermission('view_queue')) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$canManageQueue = hasPermission('manage_queue');

$staffOfficeId   = (int) ($currentUser['office_id'] ?? 0);
$officeInfo      = getOfficeInfo($db, $staffOfficeId);
$officeCode      = $officeInfo['code']      ?? '';
$officeName      = $officeInfo['name']      ?? 'My Office';
$queueMode       = $officeInfo['queue_mode'] ?? 'unified';
$csrf_token      = getCsrfToken();

$transactionTypes = [];
if ($queueMode === 'service-based') {
    $staffWindowNumber = (int) ($currentUser['window_number'] ?? 0);

    error_log("Staff ID: {$currentUser['id']}, Window: $staffWindowNumber");

    if ($staffWindowNumber > 0) {
        $stmt = $db->prepare("
            SELECT tt.id, tt.name, tt.category
            FROM transaction_types tt
            INNER JOIN service_windows sw ON sw.service_id = tt.id
            WHERE tt.office_id = ? AND tt.is_active = 1 AND tt.is_deleted = 0
            AND sw.window_number = ?
            ORDER BY tt.name ASC
        ");
        $stmt->bind_param('ii', $staffOfficeId, $staffWindowNumber);
    } else {
        $stmt = $db->prepare("
            SELECT id, name, category FROM transaction_types
            WHERE office_id = ? AND is_active = 1 AND is_deleted = 0
            ORDER BY name ASC
        ");
        $stmt->bind_param('i', $staffOfficeId);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $transactionTypes[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Queue Management — <?php echo htmlspecialchars($officeName); ?></title>
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/dashboard.css" />
    <link rel="stylesheet" href="css/queue.css" />
</head>

<body>
    <div class="dashboard-container">

        <?php include 'includes/sidebar.php'; ?>

        <div class="dashboard-body">

            <header class="dashboard-header">
                <div class="header-left">
                    <h2 class="header-page-title">
                        <i class="fas fa-ticket-alt"></i>
                        Queue Management
                    </h2>
                    <span class="office-badge">
                        <i class="fas fa-building"></i>
                        <?php echo htmlspecialchars($officeName); ?>
                    </span>
                    <?php if ($queueMode === 'service-based'): ?>
                        <span class="office-badge" style="background:rgba(26,111,170,0.08);color:#1a6faa;border-color:rgba(26,111,170,0.2);">
                            <i class="fas fa-layer-group"></i> Service-Based
                        </span>
                    <?php else: ?>
                        <span class="office-badge" style="background:rgba(42,157,92,0.08);color:#2a9d5c;border-color:rgba(42,157,92,0.2);">
                            <i class="fas fa-stream"></i> Unified Queue
                        </span>
                    <?php endif; ?>
                </div>
                <div class="header-right">
                    <div class="header-timestamp" id="currentTime"></div>
                    <button class="btn-filter btn-refresh" id="refreshBtn" onclick="refreshQueue()">
                        <i class="fas fa-sync-alt" id="refreshIcon"></i> Refresh
                    </button>
                </div>
            </header>

            <main class="dashboard-main">
                <div class="dashboard-content queue-layout">

                    <div class="queue-col-main">

                        <div class="serving-card" id="servingCard">
                            <div class="serving-card-bg"></div>
                            <div class="serving-card-content">

                                <div class="serving-header">
                                    <div class="serving-label">
                                        <span class="live-dot"></span>
                                        Now Serving
                                    </div>
                                    <div class="refresh-ring" id="refreshRing" title="Next auto-refresh">
                                        <svg viewBox="0 0 28 28">
                                            <circle class="refresh-ring-track" cx="14" cy="14" r="11" />
                                            <circle class="refresh-ring-progress" cx="14" cy="14" r="11" id="refreshRingProgress" />
                                        </svg>
                                        <div class="refresh-ring-icon"><i class="fas fa-sync-alt"></i></div>
                                    </div>
                                </div>

                                <div class="serving-ticket-wrap">
                                    <div class="serving-ticket is-idle" id="servingTicket">No ticket</div>
                                    <span class="new-badge" id="newBadge" style="display:none;">NEW</span>
                                </div>

                                <div class="serving-meta" id="servingMeta">
                                    <span id="servingStudent"><i class="fas fa-user"></i> Waiting for next student…</span>
                                    <span id="servingType" class="serving-type-badge" style="display:none;"></span>
                                </div>

                                <div class="serving-timer" id="servingTimer" style="display:none;">
                                    <i class="fas fa-stopwatch"></i>
                                    <span id="timerDisplay">00:00</span>
                                </div>

                            </div>

                            <div class="serving-actions" id="servingActions" style="display:none;">
                                <?php if ($canManageQueue): ?>
                                    <button class="action-btn action-complete" id="completeBtn" onclick="completeTicket()">
                                        <i class="fas fa-check-circle"></i> Complete
                                    </button>
                                    <button class="action-btn action-hold" id="holdBtn" onclick="holdTicket()">
                                        <i class="fas fa-pause-circle"></i> Hold
                                    </button>
                                    <button class="action-btn action-skip" id="skipBtn" onclick="skipTicket()">
                                        <i class="fas fa-forward"></i> Skip
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($queueMode === 'service-based'): ?>
                            <div class="overflow-toggle-row" id="overflowRow">
                                <div class="overflow-toggle-info">
                                    <span class="overflow-toggle-label">
                                        <i class="fas fa-clock"></i>
                                        Peak Hours Mode
                                    </span>
                                    <span class="overflow-toggle-desc" id="overflowDesc">
                                        Pull tickets from your assigned window only
                                    </span>
                                </div>
                                <button id="overflowBtn" class="overflow-toggle-btn" onclick="toggleOverflow()">
                                    <span class="overflow-toggle-pill"></span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php if ($canManageQueue): ?>
                            <button class="call-next-btn" id="callNextBtn" onclick="callNext()">
                                <i class="fas fa-bell"></i>
                                <span>Call Next Student</span>
                                <span class="call-next-count" id="callNextCount">0 waiting</span>
                            </button>
                        <?php endif; ?>

                        <div class="stats-row">
                            <div class="stat-mini stat-green">
                                <span class="stat-mini-icon"><i class="fas fa-check-circle"></i></span>
                                <div class="stat-mini-val" id="statServed">0</div>
                                <div class="stat-mini-label">Served</div>
                            </div>
                            <div class="stat-mini stat-orange">
                                <span class="stat-mini-icon"><i class="fas fa-forward"></i></span>
                                <div class="stat-mini-val" id="statSkipped">0</div>
                                <div class="stat-mini-label">Skipped</div>
                            </div>
                            <div class="stat-mini stat-blue">
                                <span class="stat-mini-icon"><i class="fas fa-clock"></i></span>
                                <div class="stat-mini-val" id="statWaiting">0</div>
                                <div class="stat-mini-label">Waiting</div>
                            </div>
                            <div class="stat-mini stat-purple">
                                <span class="stat-mini-icon"><i class="fas fa-stopwatch"></i></span>
                                <div class="stat-mini-val" id="statAvgTime">—</div>
                                <div class="stat-mini-label">Avg. Time</div>
                            </div>
                        </div>

                        <div class="last-refreshed-bar">
                            <span id="lastRefreshedText"><i class="fas fa-circle-notch"></i> Loading…</span>
                            <?php if ($canManageQueue): ?>
                                <div class="shortcut-hint" title="Keyboard shortcuts">
                                    <kbd>N</kbd> Next &nbsp;
                                    <kbd>C</kbd> Complete &nbsp;
                                    <kbd>H</kbd> Hold &nbsp;
                                    <kbd>S</kbd> Skip
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="queue-col-side">

                        <div class="queue-panel">
                            <div class="queue-panel-header">
                                <div class="queue-panel-title">
                                    <i class="fas fa-list-ol"></i>
                                    Queue
                                </div>
                                <div class="queue-panel-controls">
                                    <?php if ($queueMode === 'service-based' && !empty($transactionTypes)): ?>
                                        <select id="typeFilter" class="queue-type-filter" onchange="applyTypeFilter()">
                                            <option value="">All Services</option>
                                            <?php foreach ($transactionTypes as $tt): ?>
                                                <option value="<?= $tt['id']; ?>"><?= htmlspecialchars($tt['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                    <span class="queue-count-badge zero" id="queueCountBadge">0</span>
                                </div>
                            </div>

                            <div class="queue-tabs">
                                <button class="queue-tab active" data-tab="waiting" onclick="switchTab('waiting', this)">
                                    <i class="fas fa-clock"></i> Waiting
                                    <span class="tab-count" id="tabCountWaiting">0</span>
                                </button>
                                <button class="queue-tab" data-tab="on-hold" onclick="switchTab('on-hold', this)">
                                    <i class="fas fa-pause"></i> On Hold
                                    <span class="tab-count" id="tabCountOnHold">0</span>
                                </button>
                                <button class="queue-tab" data-tab="skipped" onclick="switchTab('skipped', this)">
                                    <i class="fas fa-forward"></i> Skipped
                                    <span class="tab-count" id="tabCountSkipped">0</span>
                                </button>
                                <button class="queue-tab" data-tab="cancelled" onclick="switchTab('cancelled', this)">
                                    <i class="fas fa-ban"></i> Cancelled
                                    <span class="tab-count" id="tabCountCancelled">0</span>
                                </button>
                                <button class="queue-tab" data-tab="done" onclick="switchTab('done', this)">
                                    <i class="fas fa-check"></i> Done
                                    <span class="tab-count" id="tabCountDone">0</span>
                                </button>
                            </div>

                            <div class="queue-list-wrap" id="queueListWrap">
                                <div class="queue-loading" id="queueLoading">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>Loading queue…</span>
                                </div>
                                <div id="queueList" class="queue-list" style="display:none;"></div>
                                <div class="queue-empty" id="queueEmpty" style="display:none;">
                                    <div class="queue-empty-icon"><i class="fas fa-ticket-alt"></i></div>
                                    <div class="queue-empty-title">Queue is Empty</div>
                                    <div class="queue-empty-msg">No tickets in this view right now.</div>
                                </div>
                            </div>

                            <div class="auto-refresh-bar">
                                <div class="auto-refresh-bar-fill" id="autoRefreshBarFill"></div>
                            </div>

                            <div class="queue-panel-footer">
                                <span id="queueLastRefreshed"><i class="fas fa-circle-notch fa-spin"></i> Loading…</span>
                                <span style="font-size:10px;">Auto-refreshes every 15s</span>
                            </div>

                        </div>

                    </div>

                </div>
            </main>
        </div>
    </div>

    <div class="modal" id="confirmModal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2 id="confirmModalTitle"><i class="fas fa-question-circle"></i> Confirm Action</h2>
                <button class="modal-close" onclick="closeModal('confirmModal')" title="Press Esc to close">&times;</button>
            </div>
            <div class="modal-body" id="confirmModalBody">Are you sure?</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="confirmCancelBtn" onclick="closeModal('confirmModal')">
                    <span>Cancel</span>
                </button>
                <button class="btn" id="confirmModalBtn" onclick="confirmAction()" data-action="confirm">
                    <span>Confirm</span>
                </button>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <script>
        const OFFICE_ID = <?php echo json_encode($staffOfficeId); ?>;
        const OFFICE_NAME = <?php echo json_encode($officeName); ?>;
        const QUEUE_MODE = <?php echo json_encode($queueMode); ?>;
        const CSRF_TOKEN = <?php echo json_encode($csrf_token); ?>;
        const CAN_MANAGE_QUEUE = <?php echo json_encode($canManageQueue); ?>;
    </script>
    <script src="scripts/queue.js"></script>
    <script src="scripts/modal-shortcuts.js"></script>
</body>

</html>