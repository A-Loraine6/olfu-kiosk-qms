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
                          AND DATE(CONVERT_TZ(completed_at, '+00:00', '+08:00')) = ?");
    $stmt->bind_param('is', $officeId, $today);
    $stmt->execute();
    $served = (int) $stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM tickets 
                          WHERE office_id=? AND status='skipped' 
                          AND DATE(CONVERT_TZ(created_at, '+00:00', '+08:00')) = ?");
    $stmt->bind_param('is', $officeId, $today);
    $stmt->execute();
    $skipped = (int) $stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM tickets WHERE office_id=? AND status='waiting'");
    $stmt->bind_param('i', $officeId);
    $stmt->execute();
    $waiting = (int) $stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $db->prepare("SELECT AVG(TIMESTAMPDIFF(SECOND, served_at, completed_at)) AS avg_s
                           FROM tickets
                           WHERE office_id=? AND status='completed'
                             AND served_at IS NOT NULL AND completed_at IS NOT NULL
                             AND DATE(CONVERT_TZ(completed_at, '+00:00', '+08:00')) = ?");
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

    $statusMap = [
        'waiting' => ['waiting', 'serving'],
        'on-hold' => ['on-hold'],
        'skipped' => ['skipped'],
        'done'    => ['completed'],
    ];
    $statuses = $statusMap[$tab] ?? ['waiting'];

    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $types        = 'i' . str_repeat('s', count($statuses));
    $params       = array_merge([$officeId], $statuses);

    $orderBy = ($tab === 'done') ? 'completed_at DESC' : 'created_at ASC';
    $today = date('Y-m-d');

    $types  = 'is' . str_repeat('s', count($statuses));
    $params = array_merge([$officeId, $today], $statuses);

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
          AND DATE(CONVERT_TZ(t.created_at, '+00:00', '+08:00')) = ?
          AND t.status IN ($placeholders)
          $typeClause
        ORDER BY $orderBy
        LIMIT 100";

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt2 = $db->prepare("SELECT COUNT(*) AS cnt 
                       FROM tickets 
                       WHERE office_id=? 
                         AND status='waiting'
                         AND DATE(CONVERT_TZ(created_at, '+00:00', '+08:00')) = ?");

    $stmt2->bind_param('is', $officeId, $today);
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

    $typeFilter = (int) ($_POST['type_filter'] ?? 0);
    $queueMode  = getOfficeQueueMode($db, $officeId);

    $typeClause = '';
    $params     = [$officeId];
    $types      = 'i';
    if ($queueMode === 'service-based' && $typeFilter > 0) {
        $typeClause = " AND transaction_type = (SELECT name FROM transaction_types WHERE id=? AND office_id=? LIMIT 1)";
        $types  .= 'ii';
        $params[] = $typeFilter;
        $params[] = $officeId;
    }

    $today = date('Y-m-d');

    $stmt = $db->prepare("SELECT * 
                      FROM tickets 
                      WHERE office_id=? 
                        AND status='waiting'
                        AND DATE(CONVERT_TZ(created_at, '+00:00', '+08:00')) = ?
                        $typeClause 
                      ORDER BY created_at ASC 
                      LIMIT 1");

    $types  = 'is' . substr($types, 1);
    $params = array_merge([$officeId, $today], array_slice($params, 1));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    if (!$ticket) {
        respond(['success' => false, 'message' => 'No waiting tickets in queue.']);
    }

    $now  = date(DATETIME_FORMAT);
    $stmt = $db->prepare("UPDATE tickets SET status='serving', served_at=?, served_by=? WHERE id=?");
    $stmt->bind_param('sii', $now, $staffId, $ticket['id']);
    $stmt->execute();

    $ticket['status']    = 'serving';
    $ticket['served_at'] = $now;
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

    $stmt = $db->prepare("UPDATE tickets SET status='skipped' WHERE id=?");
    $stmt->bind_param('i', $ticketId);
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

    $now  = date(DATETIME_FORMAT);
    $stmt = $db->prepare("UPDATE tickets SET status='serving', served_at=?, served_by=? WHERE id=?");
    $stmt->bind_param('sii', $now, $staffId, $ticketId);
    $stmt->execute();

    $ticket['status']    = 'serving';
    $ticket['served_at'] = $now;
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

    $now  = date(DATETIME_FORMAT);
    $stmt = $db->prepare("UPDATE tickets SET status='on-hold', on_hold_at=? WHERE id=?");
    $stmt->bind_param('si', $now, $ticketId);
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
    $stmt = $db->prepare("UPDATE tickets SET status='serving', served_at=?, served_by=? WHERE id=?");
    $stmt->bind_param('sii', $now, $staffId, $ticketId);
    $stmt->execute();

    $ticket['status']    = 'serving';
    $ticket['served_at'] = $now;
    respond(['success' => true, 'ticket' => $ticket]);
}

respond(['success' => false, 'message' => 'Unknown action.']);
