<?php
require_once __DIR__ . '/../includes/config_loader.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once __DIR__ . '/../includes/request_validation.php';
require_once __DIR__ . '/activity_logger.php';

header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

const DATETIME_FORMAT = 'Y-m-d H:i:s';

if (!isLoggedIn()) {
    logAuthDenied($db->getConnection(), 'Unauthenticated access to queue API', null, 'queue');
    echo json_encode(['success' => false, 'message' => 'Unauthenticated.']);
    exit;
}

$currentUser = getCurrentUser();
$isAdmin = ($_SESSION['role_id'] ?? null) === 1 || ($_SESSION['role'] ?? '') === 'admin';
if (!$isAdmin && !hasPermission('manage_queue') && !hasPermission('view_queue')) {
    logPermissionDenied($db->getConnection(), 'User lacks queue permission for queue API', (int) ($_SESSION['user_id'] ?? 0), 'queue');
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

if (!verifyCsrfToken(requestString($_POST, 'csrf_token', 255, true) ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$action    = requestEnum($_POST, 'action', ['get_stats', 'get_active', 'get_queue', 'call_next', 'serve_ticket', 'complete_ticket', 'skip_ticket', 'recall_ticket', 'hold_ticket', 'resume_ticket', 'transfer_ticket', 'transfer_window', 'toggle_overflow', 'get_overflow_state', 'get_office_services']);
$officeId  = requestInt($_POST, 'office_id') ?? 0;
$staffId   = (int) $currentUser['id'];

if ($action !== 'transfer_ticket' && $officeId !== (int) ($currentUser['office_id'] ?? 0)) {
    echo json_encode(['success' => false, 'message' => 'Office mismatch.']);
    exit;
}

function respond(array $data): void
{
    echo json_encode($data);
    exit;
}

function getOfficeQueueMode(Database $db, int $officeId): string
{
    $stmt = $db->prepare("SELECT queue_mode FROM offices WHERE id = ?");
    $stmt->bind_param('i', $officeId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['queue_mode'] ?? 'unified';
}

if ($action === 'get_stats') {
    $today = date('Y-m-d');

    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM tickets 
                          WHERE office_id=? AND status='completed' 
                          AND DATE(completed_at) = ?");
    $stmt->bind_param('is', $officeId, $today);
    $stmt->execute();
    $served = (int) $stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM tickets 
                          WHERE office_id=? AND status='skipped' 
                          AND DATE(created_at) = ?");
    $stmt->bind_param('is', $officeId, $today);
    $stmt->execute();
    $skipped = (int) $stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM tickets
                          WHERE office_id=? AND status='waiting'
                          AND DATE(created_at) = ?");
    $stmt->bind_param('is', $officeId, $today);
    $stmt->execute();
    $waiting = (int) $stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $db->prepare("
        SELECT AVG(
            COALESCE(time_spent, 0) +
            TIMESTAMPDIFF(SECOND, served_at, completed_at)
        ) AS avg_s
        FROM tickets
        WHERE office_id=? AND status='completed'
          AND served_at IS NOT NULL AND completed_at IS NOT NULL
          AND DATE(completed_at) = ?
    ");
    $stmt->bind_param('is', $officeId, $today);
    $stmt->execute();
    $avgRow  = $stmt->get_result()->fetch_assoc();
    $avgTime = $avgRow['avg_s'] !== null ? round((float)$avgRow['avg_s']) : null;

    respond(['success' => true, 'served' => $served, 'skipped' => $skipped, 'waiting' => $waiting, 'avg_time' => $avgTime]);
}

if ($action === 'get_active') {
    $stmt = $db->prepare("SELECT * FROM tickets WHERE office_id=? AND status='serving' AND served_by=? ORDER BY served_at DESC LIMIT 1");
    $stmt->bind_param('ii', $officeId, $staffId);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    respond(['success' => true, 'ticket' => $ticket ?: null]);
}

if ($action === 'get_queue') {
    $tab        = requestEnum($_POST, 'tab', ['waiting', 'on-hold', 'skipped', 'done', 'cancelled'], false) ?: 'waiting';
    $typeFilter = requestOptionalInt($_POST, 'type_filter') ?? 0;
    $queueMode  = getOfficeQueueMode($db, $officeId);

    $stmt = $db->prepare("SELECT window_number, overflow_mode FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $staffId);
    $stmt->execute();
    $staffData    = $stmt->get_result()->fetch_assoc();
    $windowNumber = (int)  ($staffData['window_number'] ?? 0);
    $overflowMode = (bool) ($staffData['overflow_mode']  ?? false);

    $statusMap = [
        'waiting'   => ['waiting', 'serving'],
        'on-hold'   => ['on-hold'],
        'skipped'   => ['skipped'],
        'done'      => ['completed'],
        'cancelled' => ['cancelled'],
    ];
    $statuses = $statusMap[$tab] ?? ['waiting'];

    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $orderBy = ($tab === 'done') ? 'completed_at DESC' : 't.is_priority DESC, t.created_at ASC';
    $today   = date('Y-m-d');

    $types  = 'is' . str_repeat('s', count($statuses));
    $params = array_merge([$officeId, $today], $statuses);

    $windowClause = '';
    $queueModeForFilter = getOfficeQueueMode($db, $officeId);
    if ($queueMode !== 'unified' && !$overflowMode && $windowNumber > 0 && in_array($tab, ['waiting', 'on-hold', 'skipped'])) {
        $windowClause = " AND EXISTS (
            SELECT 1 FROM service_windows sw
            LEFT JOIN transaction_types tt2 ON tt2.id = sw.service_id
            WHERE tt2.name = t.transaction_type AND tt2.office_id = t.office_id
              AND sw.window_number = ?
        )";
        $types   .= 'i';
        $params[] = $windowNumber;
    }

    $typeClause = '';
    if ($queueMode === 'service-based' && $typeFilter > 0) {
        $typeClause = " AND t.transaction_type = (SELECT name FROM transaction_types WHERE id=? AND office_id=? LIMIT 1)";
        $types  .= 'ii';
        $params[] = $typeFilter;
        $params[] = $officeId;
    }

    $sql = "SELECT t.*, u.full_name AS staff_name
        FROM tickets t
        LEFT JOIN users u ON t.served_by = u.id
        WHERE t.office_id=?
          AND DATE(t.created_at) = ?
          AND t.status IN ($placeholders)
          $windowClause
          $typeClause
        ORDER BY $orderBy
        LIMIT 100";

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $queueMode = getOfficeQueueMode($db, $officeId);
    if ($queueMode !== 'unified' && !$overflowMode && $windowNumber > 0) {
        $stmt2 = $db->prepare("SELECT COUNT(*) AS cnt 
            FROM tickets t
            WHERE t.office_id = ?
              AND t.status = 'waiting'
              AND DATE(t.created_at) = ?
              AND EXISTS (
                  SELECT 1 FROM service_windows sw
                  LEFT JOIN transaction_types tt2 ON tt2.id = sw.service_id
                  WHERE tt2.name = t.transaction_type AND tt2.office_id = t.office_id
                    AND sw.window_number = ?
              )");
        $stmt2->bind_param('isi', $officeId, $today, $windowNumber);
    } else {
        $stmt2 = $db->prepare("SELECT COUNT(*) AS cnt 
            FROM tickets 
            WHERE office_id=? AND status='waiting'
              AND DATE(created_at) = ?");
        $stmt2->bind_param('is', $officeId, $today);
    }

    $stmt2->execute();
    $waitingCount = (int) $stmt2->get_result()->fetch_assoc()['cnt'];

    respond(['success' => true, 'tickets' => $rows, 'waiting_count' => $waitingCount]);
}

if ($action === 'call_next') {
    $stmt = $db->prepare("SELECT id FROM tickets WHERE office_id=? AND status='serving' AND served_by=? LIMIT 1");
    $stmt->bind_param('ii', $officeId, $staffId);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        respond(['success' => false, 'message' => 'You already have a ticket in progress.']);
    }

    $stmt = $db->prepare("SELECT window_number, overflow_mode FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $staffId);
    $stmt->execute();
    $staffData    = $stmt->get_result()->fetch_assoc();
    $windowNumber = (int)  ($staffData['window_number']  ?? 0);
    $overflowMode = (bool) ($staffData['overflow_mode']  ?? false);
    $queueMode    = getOfficeQueueMode($db, $officeId);

    $today  = date('Y-m-d');
    $params = [$officeId, $today];
    $types  = 'is';

    if ($queueMode !== 'unified' && !$overflowMode && $windowNumber > 0) {
        $sql = "SELECT t.*
            FROM tickets t
            LEFT JOIN transaction_types tt
                ON tt.name = t.transaction_type AND tt.office_id = t.office_id
            WHERE t.office_id = ?
            AND t.status = 'waiting'
            AND DATE(t.created_at) = ?
            AND EXISTS (
                SELECT 1 FROM service_windows sw
                WHERE sw.service_id = tt.id AND sw.window_number = ?
            )
            ORDER BY t.is_priority DESC, t.created_at ASC
            LIMIT 1";
        $types   .= 'i';
        $params[] = $windowNumber;
    } else {
        $sql = "SELECT t.*
                FROM tickets t
                WHERE t.office_id = ?
                  AND t.status = 'waiting'
                  AND DATE(t.created_at) = ?
                ORDER BY t.is_priority DESC, t.created_at ASC
                LIMIT 1";
    }

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    if (!$ticket) {
        $msg = $overflowMode
            ? 'No waiting tickets in queue.'
            : 'No waiting tickets for your window.';
        respond(['success' => false, 'message' => $msg]);
    }

    $assignedWindowId = $ticket['window_id'];
    if ($assignedWindowId === null) {
        if ($ticket['transaction_type'] !== null) {
            $serviceStmt = $db->prepare("
                SELECT sw.window_number
                FROM service_windows sw
                LEFT JOIN transaction_types tt ON tt.id = sw.service_id
                WHERE tt.name = ? AND tt.office_id = ? AND sw.office_id = ?
            ");
            $serviceStmt->bind_param('sii', $ticket['transaction_type'], $officeId, $officeId);
            $serviceStmt->execute();
            $serviceWindows = $serviceStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $serviceStmt->close();

            if (count($serviceWindows) > 0 && $windowNumber > 0) {
                $validWindows = array_column($serviceWindows, 'window_number');
                $assignedWindowId = in_array($windowNumber, $validWindows)
                    ? $windowNumber
                    : (int) $serviceWindows[0]['window_number'];
            } elseif ($windowNumber > 0) {
                $assignedWindowId = $windowNumber;
            }
        } elseif ($windowNumber > 0) {
            // Unified queue mode: ticket has no transaction_type, so just use the staff's own window
            $assignedWindowId = $windowNumber;
        }
    }

    $now  = date(DATETIME_FORMAT);
    $stmt = $db->prepare("UPDATE tickets SET status='serving', served_at=?, served_by=?, window_id=? WHERE id=?");
    $stmt->bind_param('siii', $now, $staffId, $assignedWindowId, $ticket['id']);
    $stmt->execute();

    $ticket['status']    = 'serving';
    $ticket['served_at'] = $now;
    $ticket['window_id'] = $assignedWindowId;
    respond(['success' => true, 'ticket' => $ticket]);
}

if ($action === 'serve_ticket') {
    $ticketId = requestInt($_POST, 'ticket_id') ?? 0;

    $stmt = $db->prepare("SELECT id FROM tickets WHERE office_id=? AND status='serving' AND served_by=? LIMIT 1");
    $stmt->bind_param('ii', $officeId, $staffId);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        respond(['success' => false, 'message' => 'You already have a ticket in progress.']);
    }

    $stmt = $db->prepare("SELECT * FROM tickets WHERE id=? AND office_id=? AND status='waiting'");
    $stmt->bind_param('ii', $ticketId, $officeId);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    if (!$ticket) {
        respond(['success' => false, 'message' => 'Ticket not found or no longer waiting.']);
    }

    $stmt = $db->prepare("SELECT window_number, overflow_mode FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $staffId);
    $stmt->execute();
    $staffData    = $stmt->get_result()->fetch_assoc();
    $windowNumber = (int)  ($staffData['window_number']  ?? 0);
    $overflowMode = (bool) ($staffData['overflow_mode']  ?? false);
    $queueMode    = getOfficeQueueMode($db, $officeId);

    if ($queueMode !== 'unified' && !$overflowMode && $windowNumber > 0 && $ticket['transaction_type'] !== null) {
        $checkStmt = $db->prepare("
            SELECT 1 FROM service_windows sw
            LEFT JOIN transaction_types tt2 ON tt2.id = sw.service_id
            WHERE tt2.name = ? AND tt2.office_id = ? AND sw.window_number = ?
            LIMIT 1
        ");
        $checkStmt->bind_param('sii', $ticket['transaction_type'], $officeId, $windowNumber);
        $checkStmt->execute();
        if (!$checkStmt->get_result()->fetch_assoc()) {
            respond(['success' => false, 'message' => 'This ticket belongs to another window. Enable Peak Hours Mode to serve it.']);
        }
    }

    $assignedWindowId = $ticket['window_id'];
    if ($assignedWindowId === null && $ticket['transaction_type'] !== null) {
        $serviceStmt = $db->prepare("
            SELECT sw.window_number
            FROM service_windows sw
            LEFT JOIN transaction_types tt ON tt.id = sw.service_id
            WHERE tt.name = ? AND tt.office_id = ? AND sw.office_id = ?
        ");
        $serviceStmt->bind_param('sii', $ticket['transaction_type'], $officeId, $officeId);
        $serviceStmt->execute();
        $serviceWindows = $serviceStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $serviceStmt->close();

        if (count($serviceWindows) > 0 && $windowNumber > 0) {
            $validWindows = array_column($serviceWindows, 'window_number');
            $assignedWindowId = in_array($windowNumber, $validWindows)
                ? $windowNumber
                : (int) $serviceWindows[0]['window_number'];
        } elseif ($windowNumber > 0) {
            $assignedWindowId = $windowNumber;
        }
    }

    $now  = date(DATETIME_FORMAT);
    $stmt = $db->prepare("UPDATE tickets SET status='serving', served_at=?, served_by=?, window_id=? WHERE id=?");
    $stmt->bind_param('siii', $now, $staffId, $assignedWindowId, $ticketId);
    $stmt->execute();

    $ticket['status']    = 'serving';
    $ticket['served_at'] = $now;
    $ticket['window_id'] = $assignedWindowId;
    respond(['success' => true, 'ticket' => $ticket]);
}

if ($action === 'complete_ticket') {
    $ticketId = requestInt($_POST, 'ticket_id') ?? 0;

    $stmt = $db->prepare("SELECT * FROM tickets WHERE id=? AND office_id=? AND status='serving'");
    $stmt->bind_param('ii', $ticketId, $officeId);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    if (!$ticket) {
        respond(['success' => false, 'message' => 'Ticket not found or not in serving status.']);
    }

    $now  = date(DATETIME_FORMAT);
    $stmt = $db->prepare("UPDATE tickets SET status='completed', completed_at=? WHERE id=?");
    $stmt->bind_param('si', $now, $ticketId);
    $stmt->execute();

    respond(['success' => true, 'message' => 'Ticket completed.']);
}

if ($action === 'skip_ticket') {
    $ticketId = requestInt($_POST, 'ticket_id') ?? 0;

    $stmt = $db->prepare("SELECT * FROM tickets WHERE id=? AND office_id=? AND status IN ('serving','waiting')");
    $stmt->bind_param('ii', $ticketId, $officeId);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    if (!$ticket) {
        respond(['success' => false, 'message' => 'Ticket not found or already processed.']);
    }

    $stmt = $db->prepare("UPDATE tickets SET status='skipped', served_by=? WHERE id=?");
    $stmt->bind_param('ii', $staffId, $ticketId);
    $stmt->execute();

    respond(['success' => true, 'message' => 'Ticket skipped.']);
}

if ($action === 'recall_ticket') {
    $ticketId = requestInt($_POST, 'ticket_id') ?? 0;

    $stmt = $db->prepare("SELECT id FROM tickets WHERE office_id=? AND status='serving' AND served_by=? LIMIT 1");
    $stmt->bind_param('ii', $officeId, $staffId);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        respond(['success' => false, 'message' => 'Complete the current ticket before recalling.']);
    }

    $stmt = $db->prepare("SELECT * FROM tickets WHERE id=? AND office_id=? AND status='skipped'");
    $stmt->bind_param('ii', $ticketId, $officeId);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    if (!$ticket) {
        respond(['success' => false, 'message' => 'Skipped ticket not found.']);
    }

    $assignedWindowId = $ticket['window_id'];
    if ($assignedWindowId === null && $ticket['transaction_type'] !== null) {
        $stmt = $db->prepare("SELECT window_number FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $staffId);
        $stmt->execute();
        $staffData    = $stmt->get_result()->fetch_assoc();
        $windowNumber = (int)  ($staffData['window_number']  ?? 0);

        $serviceStmt = $db->prepare("
            SELECT sw.window_number
            FROM service_windows sw
            LEFT JOIN transaction_types tt ON tt.id = sw.service_id
            WHERE tt.name = ? AND tt.office_id = ? AND sw.office_id = ?
        ");
        $serviceStmt->bind_param('sii', $ticket['transaction_type'], $officeId, $officeId);
        $serviceStmt->execute();
        $serviceWindows = $serviceStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $serviceStmt->close();

        if (count($serviceWindows) > 0 && $windowNumber > 0) {
            $validWindows = array_column($serviceWindows, 'window_number');
            if (in_array($windowNumber, $validWindows)) {
                $assignedWindowId = $windowNumber;
            } else {
                $assignedWindowId = (int)$serviceWindows[0]['window_number'];
            }
        } elseif ($windowNumber > 0) {
            $assignedWindowId = $windowNumber;
        }
    }

    $now  = date(DATETIME_FORMAT);
    $stmt = $db->prepare("UPDATE tickets SET status='serving', served_at=?, served_by=?, window_id=? WHERE id=?");
    $stmt->bind_param('siii', $now, $staffId, $assignedWindowId, $ticketId);
    $stmt->execute();

    $ticket['status']    = 'serving';
    $ticket['served_at'] = $now;
    $ticket['window_id'] = $assignedWindowId;
    respond(['success' => true, 'ticket' => $ticket]);
}

if ($action === 'hold_ticket') {
    $ticketId = requestInt($_POST, 'ticket_id') ?? 0;

    $stmt = $db->prepare("SELECT * FROM tickets WHERE id=? AND office_id=? AND status='serving'");
    $stmt->bind_param('ii', $ticketId, $officeId);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    if (!$ticket) {
        respond(['success' => false, 'message' => 'Ticket not found or not in serving status.']);
    }

    $servedAt   = new DateTime($ticket['served_at']);
    $now        = new DateTime();
    $elapsed    = max(0, $now->getTimestamp() - $servedAt->getTimestamp());
    $newSpent   = (int)($ticket['time_spent'] ?? 0) + $elapsed;
    $nowStr     = date(DATETIME_FORMAT);

    $stmt = $db->prepare("UPDATE tickets SET status='on-hold', on_hold_at=?, time_spent=? WHERE id=?");
    $stmt->bind_param('sii', $nowStr, $newSpent, $ticketId);
    $stmt->execute();

    respond(['success' => true, 'message' => 'Ticket placed on hold. Queue number: ' . $ticket['ticket_number']]);
}

if ($action === 'resume_ticket') {
    $ticketId = requestInt($_POST, 'ticket_id') ?? 0;

    $stmt = $db->prepare("SELECT id FROM tickets WHERE office_id=? AND status='serving' AND served_by=? LIMIT 1");
    $stmt->bind_param('ii', $officeId, $staffId);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        respond(['success' => false, 'message' => 'Complete the current ticket before resuming another.']);
    }

    $stmt = $db->prepare("SELECT * FROM tickets WHERE id=? AND office_id=? AND status='on-hold'");
    $stmt->bind_param('ii', $ticketId, $officeId);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    if (!$ticket) {
        respond(['success' => false, 'message' => 'On-hold ticket not found.']);
    }

    $now  = date(DATETIME_FORMAT);
    $stmt = $db->prepare("
        UPDATE tickets 
        SET status='serving',
            served_by=?,
            served_at=?
        WHERE id=?
    ");
    $stmt->bind_param('isi', $staffId, $now, $ticketId);
    $stmt->execute();

    $ticket['status']    = 'serving';
    $ticket['served_at'] = $now;
    respond(['success' => true, 'ticket' => $ticket]);
}

if ($action === 'transfer_ticket') {
    $ticketId       = requestInt($_POST, 'ticket_id') ?? 0;
    $targetOfficeId = requestInt($_POST, 'target_office_id') ?? 0;
    $targetServiceId = requestOptionalInt($_POST, 'target_service_id') ?? 0;
    $placement      = requestEnum($_POST, 'placement', ['top', 'bottom'], false) ?: 'top';

    if ($ticketId <= 0 || $targetOfficeId <= 0) {
        respond(['success' => false, 'message' => 'A valid ticket and department are required.']);
    }

    $stmt = $db->prepare("SELECT * FROM tickets WHERE id = ? AND office_id = ? LIMIT 1");
    $stmt->bind_param('ii', $ticketId, $officeId);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    if (!$ticket) {
        respond(['success' => false, 'message' => 'Ticket not found in this office.']);
    }

    $targetOfficeCheck = $db->prepare("SELECT id FROM offices WHERE id = ? LIMIT 1");
    $targetOfficeCheck->bind_param('i', $targetOfficeId);
    $targetOfficeCheck->execute();
    if (!$targetOfficeCheck->get_result()->fetch_assoc()) {
        respond(['success' => false, 'message' => 'Target department not found.']);
    }
    $targetOfficeCheck->close();

    if ($ticket['status'] === 'completed' || $ticket['status'] === 'cancelled') {
        respond(['success' => false, 'message' => 'Only active or waiting tickets can be transferred.']);
    }

    $targetQueueMode    = getOfficeQueueMode($db, $targetOfficeId);
    $newTransactionType = $ticket['transaction_type']; // default: carry over as-is for unified offices

    if ($targetQueueMode === 'service-based') {
        if ($targetServiceId <= 0) {
            respond(['success' => false, 'message' => 'Please select a service for the destination department.']);
        }

        $svcStmt = $db->prepare("
            SELECT name FROM transaction_types
            WHERE id = ? AND office_id = ? AND is_active = 1 AND is_deleted = 0
            LIMIT 1
        ");
        $svcStmt->bind_param('ii', $targetServiceId, $targetOfficeId);
        $svcStmt->execute();
        $svcRow = $svcStmt->get_result()->fetch_assoc();
        $svcStmt->close();

        if (!$svcRow) {
            respond(['success' => false, 'message' => 'Selected service is not valid for that department.']);
        }

        $newTransactionType = $svcRow['name'];
    }

    $newStatus      = 'waiting';
    $newPriority    = ($placement === 'top') ? 1 : 0;
    $newServedBy    = null;
    $newServedAt    = null;
    $newCompletedAt = null;
    $newOnHoldAt    = null;
    $newTimeSpent   = 0;

    if ($ticket['status'] === 'on-hold') {
        $newTimeSpent = (int) ($ticket['time_spent'] ?? 0);
    }

    $transferNow = date(DATETIME_FORMAT);

    $stmt = $db->prepare("
        UPDATE tickets
        SET office_id = ?,
            status = ?,
            is_priority = ?,
            served_by = ?,
            served_at = ?,
            completed_at = ?,
            on_hold_at = ?,
            time_spent = ?,
            created_at = ?,
            transaction_type = ?,
            window_id = NULL
        WHERE id = ?
    ");
    $stmt->bind_param('isiissiissi',
        $targetOfficeId,
        $newStatus,
        $newPriority,
        $newServedBy,
        $newServedAt,
        $newCompletedAt,
        $newOnHoldAt,
        $newTimeSpent,
        $transferNow,
        $newTransactionType,
        $ticketId
    );
    $stmt->execute();

    respond([
        'success' => true,
        'message' => ($placement === 'top')
            ? 'Ticket moved to the top of the target queue.'
            : 'Ticket queued again at the end of the target queue.',
        'ticket_id' => $ticketId,
        'target_office_id' => $targetOfficeId,
        'placement' => $placement,
    ]);
}

if ($action === 'transfer_window') {
    $ticketId = requestInt($_POST, 'ticket_id') ?? 0;
    $targetWindow = requestInt($_POST, 'target_window') ?? 0;
    $placement = requestEnum($_POST, 'placement', ['top', 'bottom'], false) ?: 'top';

    if ($ticketId <= 0 || $targetWindow <= 0) {
        respond(['success' => false, 'message' => 'A valid ticket and destination window are required.']);
    }
    $currentWindow = (int) ($currentUser['window_number'] ?? 0);
    if ($currentWindow > 0 && $targetWindow === $currentWindow) {
        respond(['success' => false, 'message' => 'Choose a different window from your current assignment.']);
    }

    $stmt = $db->prepare("SELECT * FROM tickets WHERE id = ? AND office_id = ? LIMIT 1");
    $stmt->bind_param('ii', $ticketId, $officeId);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    if (!$ticket) {
        respond(['success' => false, 'message' => 'Ticket not found in this office.']);
    }
    if (in_array($ticket['status'], ['completed', 'cancelled'], true)) {
        respond(['success' => false, 'message' => 'Only active or waiting tickets can be transferred.']);
    }

    $windowStmt = $db->prepare("SELECT window_count, queue_mode FROM offices WHERE id = ? LIMIT 1");
    $windowStmt->bind_param('i', $officeId);
    $windowStmt->execute();
    $officeRow = $windowStmt->get_result()->fetch_assoc();
    if (!$officeRow || $targetWindow > (int) $officeRow['window_count']) {
        respond(['success' => false, 'message' => 'Destination window is not valid for this department.']);
    }

    if (($officeRow['queue_mode'] ?? 'unified') === 'service-based' && $ticket['transaction_type'] !== null) {
        $serviceWindowStmt = $db->prepare("\n            SELECT 1\n            FROM service_windows sw\n            INNER JOIN transaction_types tt ON tt.id = sw.service_id\n            WHERE tt.office_id = ? AND tt.name = ? AND sw.window_number = ?\n            LIMIT 1\n        ");
        $serviceWindowStmt->bind_param('isi', $officeId, $ticket['transaction_type'], $targetWindow);
        $serviceWindowStmt->execute();
        if (!$serviceWindowStmt->get_result()->fetch_assoc()) {
            respond(['success' => false, 'message' => 'That window does not handle this ticket service.']);
        }
    }

    $newPriority = $placement === 'top' ? 1 : 0;
    $newTimeSpent = $ticket['status'] === 'on-hold' ? (int) ($ticket['time_spent'] ?? 0) : 0;
    $transferNow = date(DATETIME_FORMAT);
    $newStatus = 'waiting';

    $stmt = $db->prepare("\n        UPDATE tickets\n        SET status = ?, is_priority = ?, served_by = NULL, served_at = NULL,\n            completed_at = NULL, on_hold_at = NULL, time_spent = ?, created_at = ?, window_id = ?\n        WHERE id = ? AND office_id = ?\n    ");
    $stmt->bind_param('siisiii', $newStatus, $newPriority, $newTimeSpent, $transferNow, $targetWindow, $ticketId, $officeId);
    $stmt->execute();

    respond([
        'success' => true,
        'message' => $placement === 'top'
            ? 'Ticket moved to the top of the target window queue.'
            : 'Ticket queued at the end of the target window queue.',
        'ticket_id' => $ticketId,
        'target_window' => $targetWindow,
        'placement' => $placement,
    ]);
}

if ($action === 'toggle_overflow') {
    $stmt = $db->prepare("SELECT overflow_mode FROM users WHERE id = ?");
    $stmt->bind_param('i', $staffId);
    $stmt->execute();
    $current  = (bool) $stmt->get_result()->fetch_assoc()['overflow_mode'];
    $newMode  = $current ? 0 : 1;

    $stmt = $db->prepare("UPDATE users SET overflow_mode = ? WHERE id = ?");
    $stmt->bind_param('ii', $newMode, $staffId);
    $stmt->execute();

    respond([
        'success'       => true,
        'overflow_mode' => (bool) $newMode,
        'message'       => $newMode
            ? 'Overflow mode enabled — pulling from all services.'
            : 'Back to your assigned services only.',
    ]);
}

if ($action === 'get_overflow_state') {
    $stmt = $db->prepare("SELECT overflow_mode FROM users WHERE id = ?");
    $stmt->bind_param('i', $staffId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    respond([
        'success'       => true,
        'overflow_mode' => (bool) ($row['overflow_mode'] ?? false),
    ]);
}

if ($action === 'get_office_services') {
    $targetOfficeId = requestInt($_POST, 'target_office_id') ?? 0;

    if ($targetOfficeId <= 0) {
        respond(['success' => false, 'message' => 'Invalid target department.']);
    }

    $queueMode = getOfficeQueueMode($db, $targetOfficeId);

    if ($queueMode !== 'service-based') {
        respond(['success' => true, 'queue_mode' => $queueMode, 'services' => []]);
    }

    $stmt = $db->prepare("
        SELECT id, name, category
        FROM transaction_types
        WHERE office_id = ? AND is_active = 1 AND is_deleted = 0
        ORDER BY name ASC
    ");
    $stmt->bind_param('i', $targetOfficeId);
    $stmt->execute();
    $services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    respond(['success' => true, 'queue_mode' => $queueMode, 'services' => $services]);
}

respond(['success' => false, 'message' => 'Unknown action.']);
