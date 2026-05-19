<?php
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

const DATETIME_FORMAT = 'Y-m-d H:i:s';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthenticated.']);
    exit;
}

$currentUser = getCurrentUser();
if (($currentUser['role'] ?? '') !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$action    = $_POST['action']    ?? '';
$officeId  = (int) ($_POST['office_id'] ?? 0);
$staffId   = (int) $currentUser['id'];

if ($officeId !== (int) ($currentUser['office_id'] ?? 0)) {
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
    $tab        = $_POST['tab'] ?? 'waiting';
    $typeFilter = (int) ($_POST['type_filter'] ?? 0);
    $queueMode  = getOfficeQueueMode($db, $officeId);

    $stmt = $db->prepare("SELECT window_number, overflow_mode FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $staffId);
    $stmt->execute();
    $staffData    = $stmt->get_result()->fetch_assoc();
    $windowNumber = (int)  ($staffData['window_number'] ?? 0);
    $overflowMode = (bool) ($staffData['overflow_mode']  ?? false);

    $statusMap = [
        'waiting' => ['waiting', 'serving'],
        'on-hold' => ['on-hold'],
        'skipped' => ['skipped'],
        'done'    => ['completed'],
    ];
    $statuses = $statusMap[$tab] ?? ['waiting'];

    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $orderBy = ($tab === 'done') ? 'completed_at DESC' : 'created_at ASC';
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

    $today  = date('Y-m-d');
    $params = [$officeId, $today];
    $types  = 'is';

    if (!$overflowMode && $windowNumber > 0) {
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
            ORDER BY t.created_at ASC
            LIMIT 1";
        $types   .= 'i';
        $params[] = $windowNumber;
    } else {
        $sql = "SELECT t.*
                FROM tickets t
                WHERE t.office_id = ?
                  AND t.status = 'waiting'
                  AND DATE(t.created_at) = ?
                ORDER BY t.created_at ASC
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
    $stmt->bind_param('siii', $now, $staffId, $assignedWindowId, $ticket['id']);
    $stmt->execute();

    $ticket['status']    = 'serving';
    $ticket['served_at'] = $now;
    $ticket['window_id'] = $assignedWindowId;
    respond(['success' => true, 'ticket' => $ticket]);
}

if ($action === 'complete_ticket') {
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);

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
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);

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
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);

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
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);

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
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);

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
            served_by=?
        WHERE id=?
    ");
    $stmt->bind_param('ii', $staffId, $ticketId);
    $stmt->execute();

    $ticket['status']    = 'serving';
    $ticket['served_at'] = $now;
    respond(['success' => true, 'ticket' => $ticket]);
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

respond(['success' => false, 'message' => 'Unknown action.']);
