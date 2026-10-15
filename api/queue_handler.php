<?php
require_once __DIR__ . '/../includes/config_loader.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once __DIR__ . '/../includes/request_validation.php';
require_once __DIR__ . '/activity_logger.php';
require_once __DIR__ . '/utils/TicketGenerator.php';
require_once __DIR__ . '/utils/QueueEligibility.php';
require_once __DIR__ . '/utils/TicketEvents.php';

header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

const DATETIME_FORMAT = 'Y-m-d H:i:s';

function respond(array $data): void
{
    echo json_encode($data);
    exit;
}

if (!isLoggedIn()) {
    logAuthDenied($db->getConnection(), 'Unauthenticated access to queue API', null, 'queue');
    http_response_code(401);
    echo json_encode(['success' => false, 'code' => 'unauthenticated', 'message' => 'Session expired.']);
    exit;
}

$currentUser = getCurrentUser();
$isAdmin = ($_SESSION['role_id'] ?? null) === 1 || ($_SESSION['role'] ?? '') === 'admin';
$action = requestEnum($_POST, 'action', ['get_stats', 'get_active', 'get_queue', 'call_next', 'serve_ticket', 'complete_ticket', 'skip_ticket', 'recall_ticket', 'hold_ticket', 'resume_ticket', 'transfer_ticket', 'transfer_window', 'toggle_overflow', 'get_overflow_state', 'get_office_services']);
if (!$isAdmin && !hasPermission('manage_queue') && !hasPermission('view_queue')) {
    logPermissionDenied($db->getConnection(), 'User lacks queue permission for queue API', (int) ($_SESSION['user_id'] ?? 0), 'queue');
    http_response_code(403);
    echo json_encode(['success' => false, 'code' => 'forbidden', 'message' => 'Access denied.']);
    exit;
}

if (!verifyCsrfToken(requestString($_POST, 'csrf_token', 255, true) ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'code' => 'csrf_invalid', 'message' => 'Invalid CSRF token.']);
    exit;
}

$readOnlyActions = ['get_stats', 'get_active', 'get_queue', 'get_overflow_state', 'get_office_services'];
if (!$isAdmin && !in_array($action, $readOnlyActions, true) && !hasPermission('manage_queue')) {
    logPermissionDenied($db->getConnection(), 'manage_queue required for ' . ($action ?? 'unknown'), (int) ($_SESSION['user_id'] ?? 0), 'queue');
    http_response_code(403);
    respond(['success' => false, 'code' => 'forbidden', 'message' => 'Access denied.']);
}

$officeId  = requestInt($_POST, 'office_id') ?? 0;
$staffId   = (int) $currentUser['id'];
$userOfficeId = (int) ($currentUser['office_id'] ?? 0);

if ($action === 'transfer_ticket') {
    $officeId = $userOfficeId;
}

if (!$isAdmin && $officeId !== $userOfficeId) {
    http_response_code(403);
    respond(['success' => false, 'code' => 'forbidden', 'message' => 'Office mismatch.']);
}

function getOfficeQueueMode(Database $db, int $officeId): string
{
    $stmt = $db->prepare("SELECT queue_mode FROM offices WHERE id = ?");
    $stmt->bind_param('i', $officeId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['queue_mode'] ?? 'unified';
}

function getServingWindowId(int $windowNumber): ?int
{
    return $windowNumber > 0 ? $windowNumber : null;
}

function staffActiveTicketConditionSql(): string
{
    return "office_id = ? AND status = 'serving' AND served_by = ?";
}

function accumulatedTicketTimeSpent(array $ticket): int
{
    $timeSpent = (int) ($ticket['time_spent'] ?? 0);
    if ($ticket['status'] === 'serving' && !empty($ticket['served_at'])) {
        $servedAt = strtotime($ticket['served_at']);
        if ($servedAt === false) {
            throw new RuntimeException('Invalid serving timestamp.');
        }
        $timeSpent += max(0, time() - $servedAt);
    }

    return $timeSpent;
}

function logQueueActivity(
    Database $db,
    int $staffId,
    string $activityType,
    int $ticketId,
    string $description,
    array $beforeValues,
    array $afterValues
): void {
    logActivity(
        $db->getConnection(),
        $activityType,
        'queue_ticket',
        $ticketId,
        $description,
        $beforeValues,
        $afterValues,
        $staffId
    );
}

function beginStaffTicketClaim(Database $db, int $staffId, int $officeId): array
{
    $connection = $db->getConnection();
    if (!$connection->begin_transaction()) {
        return ['success' => false, 'message' => 'Could not start the ticket claim. Please try again.'];
    }

    try {
        $staffStmt = $db->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
        $staffStmt->bind_param('i', $staffId);
        $staffStmt->execute();
        if (!$staffStmt->get_result()->fetch_assoc()) {
            $connection->rollback();
            return ['success' => false, 'message' => 'Staff account not found.'];
        }

        $activeStmt = $db->prepare("
            SELECT id FROM tickets
            WHERE " . staffActiveTicketConditionSql() . "
            LIMIT 1 FOR UPDATE
        ");
        $activeStmt->bind_param('ii', $officeId, $staffId);
        $activeStmt->execute();
        if ($activeStmt->get_result()->fetch_assoc()) {
            $connection->rollback();
            return ['success' => false, 'message' => 'You already have a ticket in progress.'];
        }

        return ['success' => true, 'connection' => $connection];
    } catch (Throwable $error) {
        $connection->rollback();
        return ['success' => false, 'message' => 'Could not verify the current ticket claim. Please try again.'];
    }
}

function canServeTicketAtWindow(
    Database $db,
    array $ticket,
    int $officeId,
    int $windowNumber,
    bool $respectTicketWindow = true
): ?bool
{
    if ($windowNumber <= 0) {
        return getOfficeQueueMode($db, $officeId) === 'level-based' ? false : true;
    }

    $stmt = $db->prepare("
        SELECT 1 FROM tickets t
        WHERE t.id = ? AND t.office_id = ?
          AND " . queueTicketWindowEligibilitySql('t', $respectTicketWindow) . "
    ");
    if (!$stmt) {
        return null;
    }
    $ticketId = (int) $ticket['id'];
    if ($respectTicketWindow) {
        $stmt->bind_param('iiiii', $ticketId, $officeId, $windowNumber, $windowNumber, $windowNumber);
    } else {
        $stmt->bind_param('iiii', $ticketId, $officeId, $windowNumber, $windowNumber);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $canServe = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $canServe;
}

function getTransferQueueOrderAt(Database $db, int $officeId, int $isPriority, string $placement, string $transferNow): string
{
    $direction = $placement === 'top' ? 'ASC' : 'DESC';
    $dateFunction = $placement === 'top' ? 'DATE_SUB' : 'DATE_ADD';
    $today = date('Y-m-d');
    $stmt = $db->prepare("
        SELECT DATE_FORMAT(
            {$dateFunction}(COALESCE(queue_order_at, created_at), INTERVAL 1 MICROSECOND),
            '%Y-%m-%d %H:%i:%s.%f'
        ) AS queue_order_at
        FROM tickets
        WHERE office_id = ?
          AND status = 'waiting'
          AND is_priority = ?
          AND DATE(created_at) = ?
        ORDER BY COALESCE(queue_order_at, created_at) {$direction}, id {$direction}
        LIMIT 1
    ");
    $stmt->bind_param('iis', $officeId, $isPriority, $today);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row['queue_order_at'] ?? ($transferNow . '.000000');
}

if ($action === 'get_stats') {
    $today = date('Y-m-d');
    $queueMode = getOfficeQueueMode($db, $officeId);
    $levelFilterInput = requestString($_POST, 'level_filter', 20);
    if ($levelFilterInput === null || ($levelFilterInput !== '' && !in_array($levelFilterInput, ['shs', 'college'], true))) {
        respond(['success' => false, 'message' => 'Invalid level filter.']);
    }
    $levelFilter = $levelFilterInput !== '' ? $levelFilterInput : null;
    $personalStats = strtolower(trim((string) ($currentUser['role'] ?? ''))) === 'staff';
    $staffStmt = $db->prepare("SELECT window_number, overflow_mode FROM users WHERE id = ? LIMIT 1");
    $staffStmt->bind_param('i', $staffId);
    $staffStmt->execute();
    $staffData = $staffStmt->get_result()->fetch_assoc() ?: [];
    $windowNumber = (int) ($staffData['window_number'] ?? 0);
    $overflowMode = (bool) ($staffData['overflow_mode'] ?? false);

    $servedSql = "SELECT COUNT(*) AS cnt FROM tickets
                  WHERE office_id=? AND status='completed'
                    AND DATE(completed_at) = ?";
    if ($personalStats) {
        $servedSql .= ' AND served_by = ?';
    }
    $stmt = $db->prepare($servedSql);
    if ($personalStats) {
        $stmt->bind_param('isi', $officeId, $today, $staffId);
    } else {
        $stmt->bind_param('is', $officeId, $today);
    }
    $stmt->execute();
    $served = (int) $stmt->get_result()->fetch_assoc()['cnt'];

    $skippedSql = "SELECT COUNT(*) AS cnt FROM tickets
                   WHERE office_id=? AND status='skipped'
                     AND DATE(created_at) = ?";
    if ($personalStats) {
        $skippedSql .= ' AND served_by = ?';
    }
    $stmt = $db->prepare($skippedSql);
    if ($personalStats) {
        $stmt->bind_param('isi', $officeId, $today, $staffId);
    } else {
        $stmt->bind_param('is', $officeId, $today);
    }
    $stmt->execute();
    $skipped = (int) $stmt->get_result()->fetch_assoc()['cnt'];

    $waitingSql = "SELECT COUNT(*) AS cnt FROM tickets t
                   WHERE t.office_id=? AND t.status='waiting'
                     AND DATE(t.created_at) = ?";
    $waitingTypes = 'is';
    $waitingParams = [$officeId, $today];
    if ($queueMode === 'service-based' && !$overflowMode && $windowNumber > 0) {
        $waitingSql .= ' AND ' . queueTicketWindowEligibilitySql('t');
        $waitingTypes .= 'iii';
        $waitingParams[] = $windowNumber;
        $waitingParams[] = $windowNumber;
        $waitingParams[] = $windowNumber;
    } elseif ($queueMode === 'service-based') {
        $waitingSql .= ' AND ' . queueTicketServiceEligibilitySql('t');
    } elseif ($queueMode === 'level-based' && $windowNumber > 0) {
        $waitingSql .= ' AND ' . queueTicketWindowEligibilitySql('t');
        $waitingTypes .= 'iii';
        $waitingParams[] = $windowNumber;
        $waitingParams[] = $windowNumber;
        $waitingParams[] = $windowNumber;
    }
    if ($queueMode === 'level-based' && $levelFilter !== null) {
        $waitingSql .= ' AND t.student_level = ?';
        $waitingTypes .= 's';
        $waitingParams[] = $levelFilter;
    }
    $stmt = $db->prepare($waitingSql);
    $stmt->bind_param($waitingTypes, ...$waitingParams);
    $stmt->execute();
    $waiting = (int) $stmt->get_result()->fetch_assoc()['cnt'];

    $avgSql = "
        SELECT AVG(
            COALESCE(time_spent, 0) +
            TIMESTAMPDIFF(SECOND, served_at, completed_at)
        ) AS avg_s
        FROM tickets
        WHERE office_id=? AND status='completed'
          AND served_at IS NOT NULL AND completed_at IS NOT NULL
          AND DATE(completed_at) = ?
    ";
    if ($personalStats) {
        $avgSql .= ' AND served_by = ?';
    }
    $stmt = $db->prepare($avgSql);
    if ($personalStats) {
        $stmt->bind_param('isi', $officeId, $today, $staffId);
    } else {
        $stmt->bind_param('is', $officeId, $today);
    }
    $stmt->execute();
    $avgRow  = $stmt->get_result()->fetch_assoc();
    $avgTime = $avgRow['avg_s'] !== null ? round((float)$avgRow['avg_s']) : null;

    respond(['success' => true, 'served' => $served, 'skipped' => $skipped, 'waiting' => $waiting, 'avg_time' => $avgTime]);
}

if ($action === 'get_active') {
    $stmt = $db->prepare("
        SELECT * FROM tickets
        WHERE " . staffActiveTicketConditionSql() . "
        ORDER BY served_at DESC
        LIMIT 1
    ");
    $stmt->bind_param('ii', $officeId, $staffId);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    respond(['success' => true, 'ticket' => $ticket ?: null]);
}

if ($action === 'get_queue') {
    $tab        = requestEnum($_POST, 'tab', ['waiting', 'on-hold', 'skipped', 'done', 'cancelled'], false) ?: 'waiting';
    $typeFilter = requestOptionalInt($_POST, 'type_filter') ?? 0;
    $levelFilterInput = requestString($_POST, 'level_filter', 20);
    if ($levelFilterInput === null || ($levelFilterInput !== '' && !in_array($levelFilterInput, ['shs', 'college'], true))) {
        respond(['success' => false, 'message' => 'Invalid level filter.']);
    }
    $levelFilter = $levelFilterInput !== '' ? $levelFilterInput : null;
    $queueMode  = getOfficeQueueMode($db, $officeId);

    $stmt = $db->prepare("SELECT window_number, overflow_mode FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $staffId);
    $stmt->execute();
    $staffData    = $stmt->get_result()->fetch_assoc();
    $windowNumber = (int)  ($staffData['window_number'] ?? 0);
    $overflowMode = (bool) ($staffData['overflow_mode']  ?? false);

    $statusMap = [
        'waiting'   => ['waiting'],
        'on-hold'   => ['on-hold'],
        'skipped'   => ['skipped'],
        'done'      => ['completed'],
        'cancelled' => ['cancelled'],
    ];
    $statuses = $statusMap[$tab] ?? ['waiting'];

    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $orderBy = ($tab === 'done') ? 'completed_at DESC' : 't.is_priority DESC, COALESCE(t.queue_order_at, t.created_at) ASC, t.id ASC';
    $today   = date('Y-m-d');

    $types  = 'is' . str_repeat('s', count($statuses));
    $params = array_merge([$officeId, $today], $statuses);
    if ($tab === 'done' && strtolower(trim((string) ($currentUser['role'] ?? ''))) === 'staff') {
        $types .= 'i';
        $params[] = $staffId;
        $ownerClause = ' AND t.served_by = ?';
    } else {
        $ownerClause = '';
    }

    $windowClause = '';
    if ($queueMode === 'service-based' && !in_array($tab, ['on-hold', 'done'], true)) {
        $windowClause = ' AND ' . queueTicketServiceEligibilitySql('t');
    }
    if (
        $queueMode === 'service-based'
        && $tab === 'on-hold'
        && !$overflowMode
        && $windowNumber > 0
    ) {
        $windowClause .= ' AND (' . queueTicketWindowEligibilitySql('t', false) . ' OR t.served_by = ?)';
        $types .= 'iii';
        $params[] = $windowNumber;
        $params[] = $windowNumber;
        $params[] = $staffId;
    } elseif ($queueMode === 'service-based' && !$overflowMode && $windowNumber > 0 && in_array($tab, ['waiting', 'skipped'])) {
        $windowClause .= ' AND ' . queueTicketWindowEligibilitySql('t');
        $types   .= 'iii';
        $params[] = $windowNumber;
        $params[] = $windowNumber;
        $params[] = $windowNumber;
    } elseif ($queueMode === 'level-based' && in_array($tab, ['waiting', 'on-hold', 'skipped'], true)) {
        if ($windowNumber > 0) {
            if ($tab === 'on-hold') {
                $windowClause .= ' AND (' . queueTicketWindowEligibilitySql('t', false) . ' OR t.served_by = ?)';
                $types .= 'iii';
                $params[] = $windowNumber;
                $params[] = $windowNumber;
                $params[] = $staffId;
            } else {
                $windowClause .= ' AND ' . queueTicketWindowEligibilitySql('t');
                $types .= 'iii';
                $params[] = $windowNumber;
                $params[] = $windowNumber;
                $params[] = $windowNumber;
            }
        }
    }

    $typeClause = '';
    if ($queueMode === 'service-based' && $typeFilter > 0) {
        $typeClause = " AND t.transaction_type = (SELECT name FROM transaction_types WHERE id=? AND office_id=? LIMIT 1)";
        $types  .= 'ii';
        $params[] = $typeFilter;
        $params[] = $officeId;
    } elseif ($queueMode === 'level-based' && $levelFilter !== null) {
        $typeClause = ' AND t.student_level = ?';
        $types .= 's';
        $params[] = $levelFilter;
    }

    $sql = "SELECT t.*, u.full_name AS staff_name
        FROM tickets t
        LEFT JOIN users u ON t.served_by = u.id
        WHERE t.office_id=?
          AND DATE(t.created_at) = ?
          AND t.status IN ($placeholders)
          $ownerClause
          $windowClause
          $typeClause
        ORDER BY $orderBy
        LIMIT 100";

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $countSql = "SELECT COUNT(*) AS cnt
        FROM tickets t
        WHERE t.office_id=?
          AND DATE(t.created_at) = ?
          AND t.status IN ($placeholders)
          $ownerClause
          $windowClause
          $typeClause";
    $countStmt = $db->prepare($countSql);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $tabCount = (int) $countStmt->get_result()->fetch_assoc()['cnt'];

    $queueMode = getOfficeQueueMode($db, $officeId);
    $waitingCountSql = "SELECT COUNT(*) AS cnt
        FROM tickets t
        WHERE t.office_id = ? AND t.status = 'waiting'
          AND DATE(t.created_at) = ?";
    $waitingCountTypes = 'is';
    $waitingCountParams = [$officeId, $today];
    if ($queueMode === 'service-based') {
        $waitingCountSql .= ' AND ' . queueTicketServiceEligibilitySql('t');
        if (!$overflowMode && $windowNumber > 0) {
            $waitingCountSql .= ' AND ' . queueTicketWindowEligibilitySql('t');
            $waitingCountTypes .= 'iii';
            $waitingCountParams[] = $windowNumber;
            $waitingCountParams[] = $windowNumber;
            $waitingCountParams[] = $windowNumber;
        }
    } elseif ($queueMode === 'level-based') {
        if ($windowNumber > 0) {
            $waitingCountSql .= ' AND ' . queueTicketWindowEligibilitySql('t');
            $waitingCountTypes .= 'iii';
            $waitingCountParams[] = $windowNumber;
            $waitingCountParams[] = $windowNumber;
            $waitingCountParams[] = $windowNumber;
        }
        if ($levelFilter !== null) {
            $waitingCountSql .= ' AND t.student_level = ?';
            $waitingCountTypes .= 's';
            $waitingCountParams[] = $levelFilter;
        }
    }
    $stmt2 = $db->prepare($waitingCountSql);
    $stmt2->bind_param($waitingCountTypes, ...$waitingCountParams);

    $stmt2->execute();
    $waitingCount = (int) $stmt2->get_result()->fetch_assoc()['cnt'];

    respond([
        'success' => true,
        'tickets' => $rows,
        'waiting_count' => $waitingCount,
        'tab_count' => $tabCount,
        'queue_mode' => $queueMode,
    ]);
}

if ($action === 'call_next') {
    $conn = $db->getConnection();
    if (!$conn->begin_transaction()) {
        respond(['success' => false, 'message' => 'Could not start the ticket claim. Please try again.']);
    }

    $stmt = $db->prepare("SELECT window_number, overflow_mode FROM users WHERE id = ? FOR UPDATE");
    if (!$stmt) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Could not verify your window assignment.']);
    }
    $stmt->bind_param('i', $staffId);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->rollback();
        respond(['success' => false, 'message' => 'Could not verify your window assignment.']);
    }
    $staffData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$staffData) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Staff account not found.']);
    }

    $stmt = $db->prepare("SELECT id FROM tickets WHERE " . staffActiveTicketConditionSql() . " LIMIT 1");
    if (!$stmt) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Could not check your active ticket.']);
    }
    $stmt->bind_param('ii', $officeId, $staffId);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->rollback();
        respond(['success' => false, 'message' => 'Could not check your active ticket.']);
    }
    if ($stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        $conn->rollback();
        respond(['success' => false, 'message' => 'You already have a ticket in progress.']);
    }
    $stmt->close();

    $windowNumber = (int)  ($staffData['window_number']  ?? 0);
    $overflowMode = (bool) ($staffData['overflow_mode']  ?? false);
    $queueMode    = getOfficeQueueMode($db, $officeId);

    $today  = date('Y-m-d');
    $params = [$officeId, $today];
    $types  = 'is';

    if ($queueMode === 'level-based') {
        if ($windowNumber <= 0) {
            $conn->rollback();
            respond(['success' => false, 'message' => 'Assign a window with a level before calling tickets.']);
        }
        $sql = "SELECT t.*
            FROM tickets t
            WHERE t.office_id = ?
            AND t.status = 'waiting'
            AND DATE(t.created_at) = ?
            AND " . queueTicketWindowEligibilitySql('t') . "
            ORDER BY t.is_priority DESC, COALESCE(t.queue_order_at, t.created_at) ASC, t.id ASC
            LIMIT 1";
        $types .= 'iii';
        $params[] = $windowNumber;
        $params[] = $windowNumber;
        $params[] = $windowNumber;
    } elseif ($queueMode !== 'unified' && !$overflowMode && $windowNumber > 0) {
        $sql = "SELECT t.*
            FROM tickets t
            WHERE t.office_id = ?
            AND t.status = 'waiting'
            AND DATE(t.created_at) = ?
            AND " . queueTicketWindowEligibilitySql('t') . "
            ORDER BY t.is_priority DESC, COALESCE(t.queue_order_at, t.created_at) ASC, t.id ASC
            LIMIT 1";
        $types   .= 'iii';
        $params[] = $windowNumber;
        $params[] = $windowNumber;
        $params[] = $windowNumber;
    } else {
        $sql = "SELECT t.*
                FROM tickets t
                WHERE t.office_id = ?
                  AND t.status = 'waiting'
                  AND DATE(t.created_at) = ?
                  " . ($queueMode === 'service-based' ? ' AND ' . queueTicketServiceEligibilitySql('t') : '') . "
                ORDER BY t.is_priority DESC, COALESCE(t.queue_order_at, t.created_at) ASC, t.id ASC
                LIMIT 1";
    }

    $stmt = $db->prepare($sql . ' FOR UPDATE');
    if (!$stmt) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Could not load the next ticket. Please try again.']);
    }
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->rollback();
        respond(['success' => false, 'message' => 'Could not load the next ticket. Please try again.']);
    }
    $ticket = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ticket) {
        $conn->rollback();
        $msg = $overflowMode
            ? 'No waiting tickets in queue.'
            : 'No waiting tickets for your window.';
        respond(['success' => false, 'message' => $msg]);
    }

    $assignedWindowId = getServingWindowId($windowNumber);

    $now  = date(DATETIME_FORMAT);
    $stmt = $db->prepare("
        UPDATE tickets
        SET status='serving', served_at=?, served_by=?, window_id=?
        WHERE id=? AND office_id=? AND status='waiting'
    ");
    if (!$stmt) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Could not claim the ticket. Please try again.']);
    }
    $stmt->bind_param('siiii', $now, $staffId, $assignedWindowId, $ticket['id'], $officeId);
    if (!$stmt->execute() || $stmt->affected_rows !== 1) {
        $stmt->close();
        $conn->rollback();
        respond(['success' => false, 'message' => 'Another staff member has already claimed that ticket. Please try again.']);
    }
    $stmt->close();

    try {
        recordTicketEvent(
            $db,
            (int) $ticket['id'],
            'ticket_called',
            'waiting',
            'serving',
            $staffId,
            $officeId,
            $assignedWindowId,
            ['ticket_number' => $ticket['ticket_number']]
        );
    } catch (Throwable $error) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Could not save ticket history. The ticket was not claimed.']);
    }

    if (!$conn->commit()) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Could not confirm the ticket claim. Please refresh the queue.']);
    }

    $ticket['status']    = 'serving';
    $ticket['served_at'] = $now;
    $ticket['window_id'] = $assignedWindowId;
    logQueueActivity(
        $db,
        $staffId,
        'TICKET_CALLED',
        (int) $ticket['id'],
        "Ticket {$ticket['ticket_number']} called at office {$officeId}.",
        ['status' => 'waiting', 'office_id' => $officeId],
        ['status' => 'serving', 'office_id' => $officeId, 'window_id' => $assignedWindowId]
    );
    respond(['success' => true, 'ticket' => $ticket]);
}

if ($action === 'serve_ticket') {
    $ticketId = requestInt($_POST, 'ticket_id') ?? 0;

    $stmt = $db->prepare("SELECT id FROM tickets WHERE " . staffActiveTicketConditionSql() . " LIMIT 1");
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

    if ($queueMode === 'level-based' || ($queueMode !== 'unified' && !$overflowMode && $windowNumber > 0)) {
        $canServe = canServeTicketAtWindow($db, $ticket, $officeId, $windowNumber);
        if ($canServe === null) {
            respond(['success' => false, 'message' => 'Could not verify the ticket window assignment.']);
        }
        if (!$canServe) {
            respond(['success' => false, 'message' => 'This ticket belongs to another window. Enable Peak Hours Mode to serve it.']);
        }
    }

    $claim = beginStaffTicketClaim($db, $staffId, $officeId);
    if (!$claim['success']) {
        respond(['success' => false, 'message' => $claim['message']]);
    }

    $assignedWindowId = getServingWindowId($windowNumber);
    $now  = date(DATETIME_FORMAT);
    $stmt = $db->prepare("
        UPDATE tickets
        SET status='serving', served_at=?, served_by=?, window_id=?
        WHERE id=? AND office_id=? AND status='waiting'
    ");
    $stmt->bind_param('siiii', $now, $staffId, $assignedWindowId, $ticketId, $officeId);
    if (!$stmt->execute() || $stmt->affected_rows !== 1) {
        $claim['connection']->rollback();
        respond(['success' => false, 'message' => 'Another staff member already claimed that ticket.']);
    }
    try {
        recordTicketEvent(
            $db,
            $ticketId,
            'ticket_served',
            'waiting',
            'serving',
            $staffId,
            $officeId,
            $assignedWindowId,
            ['ticket_number' => $ticket['ticket_number']]
        );
    } catch (Throwable $error) {
        $claim['connection']->rollback();
        respond(['success' => false, 'message' => 'Could not save ticket history. The ticket was not claimed.']);
    }
    if (!$claim['connection']->commit()) {
        $claim['connection']->rollback();
        respond(['success' => false, 'message' => 'Could not confirm the ticket claim. Please refresh the queue.']);
    }

    $ticket['status']    = 'serving';
    $ticket['served_at'] = $now;
    $ticket['window_id'] = $assignedWindowId;
    logQueueActivity(
        $db,
        $staffId,
        'TICKET_SERVED',
        $ticketId,
        "Ticket {$ticket['ticket_number']} served at office {$officeId}.",
        ['status' => 'waiting', 'office_id' => $officeId],
        ['status' => 'serving', 'office_id' => $officeId, 'window_id' => $assignedWindowId]
    );
    respond(['success' => true, 'ticket' => $ticket]);
}

if ($action === 'complete_ticket') {
    $ticketId = requestInt($_POST, 'ticket_id') ?? 0;

    $stmt = $db->prepare("SELECT * FROM tickets WHERE id=? AND office_id=? AND status='serving' AND served_by=?");
    $stmt->bind_param('iii', $ticketId, $officeId, $staffId);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    if (!$ticket) {
        respond(['success' => false, 'message' => 'Ticket not found or not in serving status.']);
    }

    $now  = date(DATETIME_FORMAT);
    $conn = $db->getConnection();
    if (!$conn->begin_transaction()) {
        respond(['success' => false, 'message' => 'Could not start ticket completion. Please try again.']);
    }
    $stmt = $db->prepare("
        UPDATE tickets SET status='completed', completed_at=?
        WHERE id=? AND office_id=? AND status='serving' AND served_by=?
    ");
    $stmt->bind_param('siii', $now, $ticketId, $officeId, $staffId);
    if (!$stmt->execute() || $stmt->affected_rows !== 1) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Ticket state changed before it could be completed. Please refresh the queue.']);
    }
    try {
        recordTicketEvent($db, $ticketId, 'ticket_completed', 'serving', 'completed', $staffId, $officeId, (int) ($ticket['window_id'] ?? 0) ?: null, ['ticket_number' => $ticket['ticket_number']]);
    } catch (Throwable $error) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Could not save ticket history. Completion was not recorded.']);
    }
    if (!$conn->commit()) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Could not confirm ticket completion. Please refresh the queue.']);
    }

    logQueueActivity(
        $db,
        $staffId,
        'TICKET_COMPLETED',
        $ticketId,
        "Ticket {$ticket['ticket_number']} completed at office {$officeId}.",
        ['status' => 'serving', 'office_id' => $officeId],
        ['status' => 'completed', 'office_id' => $officeId]
    );
    respond(['success' => true, 'message' => 'Ticket completed.']);
}

if ($action === 'skip_ticket') {
    $ticketId = requestInt($_POST, 'ticket_id') ?? 0;

    $stmt = $db->prepare("
        SELECT * FROM tickets
        WHERE id = ? AND office_id = ?
          AND (status = 'waiting' OR (status = 'serving' AND served_by = ?))
    ");
    $stmt->bind_param('iii', $ticketId, $officeId, $staffId);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();

    if (!$ticket) {
        respond(['success' => false, 'message' => 'Ticket not found or already processed.']);
    }

    $newSpent = accumulatedTicketTimeSpent($ticket);

    $conn = $db->getConnection();
    if (!$conn->begin_transaction()) {
        respond(['success' => false, 'message' => 'Could not start ticket skip. Please try again.']);
    }
    $ticketStatus = $ticket['status'];
    $stmt = $db->prepare("
        UPDATE tickets SET status='skipped', served_by=?, time_spent=?, served_at=NULL
        WHERE id=? AND office_id=? AND status=?
          AND (status <> 'serving' OR served_by=?)
    ");
    $stmt->bind_param('iiiisi', $staffId, $newSpent, $ticketId, $officeId, $ticketStatus, $staffId);
    if (!$stmt->execute() || $stmt->affected_rows !== 1) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Ticket state changed before it could be skipped. Please refresh the queue.']);
    }
    try {
        recordTicketEvent($db, $ticketId, 'ticket_skipped', $ticketStatus, 'skipped', $staffId, $officeId, (int) ($ticket['window_id'] ?? 0) ?: null, ['ticket_number' => $ticket['ticket_number'], 'time_spent' => $newSpent]);
    } catch (Throwable $error) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Could not save ticket history. The ticket was not skipped.']);
    }
    if (!$conn->commit()) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Could not confirm ticket skip. Please refresh the queue.']);
    }

    logQueueActivity(
        $db,
        $staffId,
        'TICKET_SKIPPED',
        $ticketId,
        "Ticket {$ticket['ticket_number']} skipped at office {$officeId}.",
        ['status' => $ticket['status'], 'office_id' => $officeId, 'time_spent' => (int) ($ticket['time_spent'] ?? 0)],
        ['status' => 'skipped', 'office_id' => $officeId, 'time_spent' => $newSpent]
    );
    respond(['success' => true, 'message' => 'Ticket skipped.']);
}

if ($action === 'recall_ticket') {
    $ticketId = requestInt($_POST, 'ticket_id') ?? 0;

    $stmt = $db->prepare("SELECT id FROM tickets WHERE " . staffActiveTicketConditionSql() . " LIMIT 1");
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

    $stmt = $db->prepare("SELECT window_number, overflow_mode FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $staffId);
    $stmt->execute();
    $staffData = $stmt->get_result()->fetch_assoc();
    $windowNumber = (int) ($staffData['window_number'] ?? 0);
    $overflowMode = (bool) ($staffData['overflow_mode'] ?? false);
    $queueMode = getOfficeQueueMode($db, $officeId);

    if ($queueMode === 'level-based' || ($queueMode !== 'unified' && !$overflowMode && $windowNumber > 0)) {
        $canServe = canServeTicketAtWindow($db, $ticket, $officeId, $windowNumber);
        if ($canServe === null) {
            respond(['success' => false, 'message' => 'Could not verify the ticket window assignment.']);
        }
        if (!$canServe) {
            respond(['success' => false, 'message' => 'This ticket belongs to another window.']);
        }
    }

    $claim = beginStaffTicketClaim($db, $staffId, $officeId);
    if (!$claim['success']) {
        respond(['success' => false, 'message' => $claim['message']]);
    }

    $assignedWindowId = getServingWindowId($windowNumber);
    $now  = date(DATETIME_FORMAT);
    $stmt = $db->prepare("
        UPDATE tickets
        SET status='serving', served_at=?, served_by=?, window_id=?
        WHERE id=? AND office_id=? AND status='skipped'
    ");
    $stmt->bind_param('siiii', $now, $staffId, $assignedWindowId, $ticketId, $officeId);
    if (!$stmt->execute() || $stmt->affected_rows !== 1) {
        $claim['connection']->rollback();
        respond(['success' => false, 'message' => 'Ticket is no longer available to recall. Please refresh the queue.']);
    }
    try {
        recordTicketEvent($db, $ticketId, 'ticket_recalled', 'skipped', 'serving', $staffId, $officeId, $assignedWindowId, ['ticket_number' => $ticket['ticket_number']]);
    } catch (Throwable $error) {
        $claim['connection']->rollback();
        respond(['success' => false, 'message' => 'Could not save ticket history. The ticket was not recalled.']);
    }
    if (!$claim['connection']->commit()) {
        $claim['connection']->rollback();
        respond(['success' => false, 'message' => 'Could not confirm the ticket claim. Please refresh the queue.']);
    }

    $ticket['status']    = 'serving';
    $ticket['served_at'] = $now;
    $ticket['window_id'] = $assignedWindowId;
    logQueueActivity(
        $db,
        $staffId,
        'TICKET_RECALLED',
        $ticketId,
        "Ticket {$ticket['ticket_number']} recalled at office {$officeId}.",
        ['status' => 'skipped', 'office_id' => $officeId],
        ['status' => 'serving', 'office_id' => $officeId, 'window_id' => $assignedWindowId]
    );
    respond(['success' => true, 'ticket' => $ticket]);
}

if ($action === 'hold_ticket') {
    $ticketId = requestInt($_POST, 'ticket_id') ?? 0;

    $stmt = $db->prepare("SELECT * FROM tickets WHERE id=? AND office_id=? AND status='serving' AND served_by=?");
    $stmt->bind_param('iii', $ticketId, $officeId, $staffId);
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

    $conn = $db->getConnection();
    if (!$conn->begin_transaction()) {
        respond(['success' => false, 'message' => 'Could not start placing the ticket on hold. Please try again.']);
    }
    $stmt = $db->prepare("
        UPDATE tickets SET status='on-hold', on_hold_at=?, time_spent=?, served_at=NULL
        WHERE id=? AND office_id=? AND status='serving' AND served_by=?
    ");
    $stmt->bind_param('siiii', $nowStr, $newSpent, $ticketId, $officeId, $staffId);
    if (!$stmt->execute() || $stmt->affected_rows !== 1) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Ticket state changed before it could be placed on hold. Please refresh the queue.']);
    }
    try {
        recordTicketEvent($db, $ticketId, 'ticket_held', 'serving', 'on-hold', $staffId, $officeId, (int) ($ticket['window_id'] ?? 0) ?: null, ['ticket_number' => $ticket['ticket_number'], 'time_spent' => $newSpent]);
    } catch (Throwable $error) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Could not save ticket history. The ticket was not placed on hold.']);
    }
    if (!$conn->commit()) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Could not confirm the ticket hold. Please refresh the queue.']);
    }

    logQueueActivity(
        $db,
        $staffId,
        'TICKET_HELD',
        $ticketId,
        "Ticket {$ticket['ticket_number']} placed on hold at office {$officeId}.",
        ['status' => 'serving', 'office_id' => $officeId],
        ['status' => 'on-hold', 'office_id' => $officeId, 'time_spent' => $newSpent]
    );
    respond(['success' => true, 'message' => 'Ticket placed on hold. Queue number: ' . $ticket['ticket_number']]);
}

if ($action === 'resume_ticket') {
    $ticketId = requestInt($_POST, 'ticket_id') ?? 0;

    $stmt = $db->prepare("SELECT id FROM tickets WHERE " . staffActiveTicketConditionSql() . " LIMIT 1");
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

    $stmt = $db->prepare("SELECT window_number, overflow_mode FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $staffId);
    $stmt->execute();
    $staffData = $stmt->get_result()->fetch_assoc();
    $windowNumber = (int) ($staffData['window_number'] ?? 0);
    $queueMode = getOfficeQueueMode($db, $officeId);
    if ($queueMode === 'level-based' || ($queueMode !== 'unified' && empty($staffData['overflow_mode']) && $windowNumber > 0)) {
        $canServe = canServeTicketAtWindow($db, $ticket, $officeId, $windowNumber, false);
        if ($canServe === null) {
            respond(['success' => false, 'message' => 'Could not verify the ticket window assignment.']);
        }
        if (!$canServe) {
            respond(['success' => false, 'message' => 'This ticket belongs to another window.']);
        }
    }
    $claim = beginStaffTicketClaim($db, $staffId, $officeId);
    if (!$claim['success']) {
        respond(['success' => false, 'message' => $claim['message']]);
    }

    $assignedWindowId = getServingWindowId($windowNumber);
    $now  = date(DATETIME_FORMAT);
    $stmt = $db->prepare("
        UPDATE tickets 
        SET status='serving',
            served_by=?,
            served_at=?,
            window_id=?
        WHERE id=? AND office_id=? AND status='on-hold'
    ");
    $stmt->bind_param('isiii', $staffId, $now, $assignedWindowId, $ticketId, $officeId);
    if (!$stmt->execute() || $stmt->affected_rows !== 1) {
        $claim['connection']->rollback();
        respond(['success' => false, 'message' => 'Ticket is no longer available to resume. Please refresh the queue.']);
    }
    try {
        recordTicketEvent($db, $ticketId, 'ticket_resumed', 'on-hold', 'serving', $staffId, $officeId, $assignedWindowId, ['ticket_number' => $ticket['ticket_number']]);
    } catch (Throwable $error) {
        $claim['connection']->rollback();
        respond(['success' => false, 'message' => 'Could not save ticket history. The ticket was not resumed.']);
    }
    if (!$claim['connection']->commit()) {
        $claim['connection']->rollback();
        respond(['success' => false, 'message' => 'Could not confirm the ticket claim. Please refresh the queue.']);
    }

    $ticket['status']    = 'serving';
    $ticket['served_at'] = $now;
    $ticket['window_id'] = $assignedWindowId;
    logQueueActivity(
        $db,
        $staffId,
        'TICKET_RESUMED',
        $ticketId,
        "Ticket {$ticket['ticket_number']} resumed at office {$officeId}.",
        ['status' => 'on-hold', 'office_id' => $officeId, 'time_spent' => (int) ($ticket['time_spent'] ?? 0)],
        ['status' => 'serving', 'office_id' => $officeId, 'window_id' => $assignedWindowId]
    );
    respond(['success' => true, 'ticket' => $ticket]);
}

if ($action === 'transfer_ticket') {
    $ticketId       = requestInt($_POST, 'ticket_id') ?? 0;
    $targetOfficeId = requestInt($_POST, 'target_office_id') ?? 0;
    $targetServiceId = requestOptionalInt($_POST, 'target_service_id') ?? 0;
    $placement      = requestEnum($_POST, 'placement', ['top', 'bottom', 'end'], false) ?: 'top';
    $placement = $placement === 'end' ? 'bottom' : $placement;

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
    if ((int) $ticket['office_id'] !== $officeId) {
        respond(['success' => false, 'message' => 'Ticket not found in this office.']);
    }
    if ($targetOfficeId === (int) $ticket['office_id']) {
        respond(['success' => false, 'message' => 'This ticket is already in that department. Use transfer_window instead.']);
    }
    if ($ticket['status'] === 'serving' && (int) $ticket['served_by'] !== $staffId) {
        respond(['success' => false, 'message' => 'Only the staff member serving this ticket can transfer it.']);
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
    $newStudentLevel = $ticket['student_level'] ?? null;

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
    } elseif ($targetQueueMode === 'level-based') {
        $newStudentLevel = requestEnum($_POST, 'target_level', ['shs', 'college']);
        if ($newStudentLevel === null) {
            respond(['success' => false, 'message' => 'Please select SHS or College for the destination department.']);
        }
        $levelWindowStmt = $db->prepare("
            SELECT 1
            FROM window_levels
            WHERE office_id = ?
              AND (student_level = ? OR student_level = 'both')
            LIMIT 1
        ");
        if (!$levelWindowStmt) {
            respond(['success' => false, 'message' => 'Could not verify destination windows.']);
        }
        $levelWindowStmt->bind_param('is', $targetOfficeId, $newStudentLevel);
        if (!$levelWindowStmt->execute()) {
            $levelWindowStmt->close();
            respond(['success' => false, 'message' => 'Could not verify destination windows.']);
        }
        if (!$levelWindowStmt->get_result()->fetch_assoc()) {
            $levelWindowStmt->close();
            respond(['success' => false, 'message' => 'No destination window is assigned to serve that student level.']);
        }
        $levelWindowStmt->close();
        $newTransactionType = null;
    }

    $newStatus      = 'waiting';
    $newPriority    = (int) $ticket['is_priority'];
    $newServedBy    = null;
    $newServedAt    = null;
    $newCompletedAt = null;
    $newOnHoldAt    = null;
    $newTimeSpent = accumulatedTicketTimeSpent($ticket);

    $transferNow = date(DATETIME_FORMAT);
    $ticketNumber = null;
    $oldTicketNumber = (string) $ticket['ticket_number'];
    $sourceTransactionType = $ticket['transaction_type'];
    $ticketStatus = $ticket['status'];
    $transferFailureMessage = null;
    $connection = $db->getConnection();
    if (!$connection->begin_transaction()) {
        respond(['success' => false, 'message' => 'Could not start ticket transfer. Please try again.']);
    }
    try {
        $updated = (new TicketGenerator($db))->withNextTicketNumber(
            $targetOfficeId,
            (bool) $newPriority,
            function (string $newTicketNumber) use (
                $db,
                $targetOfficeId,
                $newStatus,
                $newPriority,
                $newServedBy,
                $newServedAt,
                $newCompletedAt,
                $newOnHoldAt,
                $newTimeSpent,
                $transferNow,
                $placement,
                $newTransactionType,
                $newStudentLevel,
                $ticketId,
                $officeId,
                $ticketStatus,
                $staffId,
                $connection,
                $oldTicketNumber,
                $sourceTransactionType,
                $targetQueueMode,
                &$transferFailureMessage,
                &$ticketNumber
            ): bool {
                $modeStmt = $db->prepare('SELECT queue_mode FROM offices WHERE id = ? LIMIT 1');
                if (!$modeStmt) {
                    throw new RuntimeException('Could not verify the destination queue mode.');
                }
                $modeStmt->bind_param('i', $targetOfficeId);
                if (!$modeStmt->execute()) {
                    $modeStmt->close();
                    throw new RuntimeException('Could not verify the destination queue mode.');
                }
                $currentTargetMode = $modeStmt->get_result()->fetch_assoc()['queue_mode'] ?? null;
                $modeStmt->close();
                if ($currentTargetMode !== $targetQueueMode) {
                    $transferFailureMessage = 'Destination queue settings changed. Refresh and try the transfer again.';
                    return false;
                }
                if ($currentTargetMode === 'level-based') {
                    $levelWindowStmt = $db->prepare("
                        SELECT 1
                        FROM window_levels
                        WHERE office_id = ?
                          AND (student_level = ? OR student_level = 'both')
                        LIMIT 1
                    ");
                    if (!$levelWindowStmt) {
                        throw new RuntimeException('Could not verify destination windows.');
                    }
                    $levelWindowStmt->bind_param('is', $targetOfficeId, $newStudentLevel);
                    if (!$levelWindowStmt->execute()) {
                        $levelWindowStmt->close();
                        throw new RuntimeException('Could not verify destination windows.');
                    }
                    $hasEligibleWindow = (bool) $levelWindowStmt->get_result()->fetch_assoc();
                    $levelWindowStmt->close();
                    if (!$hasEligibleWindow) {
                        $transferFailureMessage = 'No destination window is assigned to serve that student level.';
                        return false;
                    }
                }

                $queueOrderAt = getTransferQueueOrderAt($db, $targetOfficeId, $newPriority, $placement, $transferNow);
                $stmt = $db->prepare("
        UPDATE tickets
        SET office_id = ?,
            ticket_number = ?,
            queue_order_at = ?,
            status = ?,
            is_priority = ?,
            served_by = ?,
            served_at = ?,
            completed_at = ?,
            on_hold_at = ?,
            time_spent = ?,
            created_at = ?,
            transaction_type = ?,
            student_level = ?,
            window_id = NULL
        WHERE id = ? AND office_id = ? AND status = ?
          AND (status <> 'serving' OR served_by = ?)
    ");
                $stmt->bind_param('isssiisssisssiisi',
                    $targetOfficeId,
                    $newTicketNumber,
                    $queueOrderAt,
                    $newStatus,
                    $newPriority,
                    $newServedBy,
                    $newServedAt,
                    $newCompletedAt,
                    $newOnHoldAt,
                    $newTimeSpent,
                    $transferNow,
                    $newTransactionType,
                    $newStudentLevel,
                    $ticketId,
                    $officeId,
                    $ticketStatus,
                    $staffId
                );
                $success = $stmt->execute() && $stmt->affected_rows === 1;
                $stmt->close();
                if ($success) {
                    recordTicketEvent(
                        $db,
                        $ticketId,
                        'department_transfer',
                        $ticketStatus,
                        'waiting',
                        $staffId,
                        $officeId,
                        null,
                        [
                            'source_office_id' => $officeId,
                            'destination_office_id' => $targetOfficeId,
                            'old_ticket_number' => $oldTicketNumber,
                            'new_ticket_number' => $newTicketNumber,
                            'source_service' => $sourceTransactionType,
                            'destination_service' => $newTransactionType,
                            'student_level' => $newStudentLevel,
                        ]
                    );
                    if (!$connection->commit()) {
                        throw new RuntimeException('Could not commit ticket transfer.');
                    }
                    $ticketNumber = $newTicketNumber;
                }
                return $success;
            }
        );
    } catch (Throwable $error) {
        $connection->rollback();
        respond(['success' => false, 'message' => 'Could not assign a new ticket number. Please try again.']);
    }

    if (!$updated) {
        $connection->rollback();
        respond(['success' => false, 'message' => $transferFailureMessage ?? 'Ticket transfer failed. Please try again.']);
    }

    logQueueActivity(
        $db,
        $staffId,
        'TICKET_TRANSFERRED',
        $ticketId,
        "Ticket {$oldTicketNumber} transferred from office {$officeId} to office {$targetOfficeId} and renumbered {$ticketNumber}.",
        ['ticket_number' => $oldTicketNumber, 'office_id' => $officeId, 'status' => $ticketStatus],
        ['ticket_number' => $ticketNumber, 'office_id' => $targetOfficeId, 'status' => 'waiting']
    );

    respond([
        'success' => true,
        'message' => ($placement === 'top')
            ? 'Ticket moved to the top of the target queue. New ticket number: ' . $ticketNumber . '.'
            : 'Ticket queued again at the end of the target queue. New ticket number: ' . $ticketNumber . '.',
        'ticket_id' => $ticketId,
        'ticket_number' => $ticketNumber,
        'target_office_id' => $targetOfficeId,
        'placement' => $placement,
    ]);
}

if ($action === 'transfer_window') {
    $ticketId = requestInt($_POST, 'ticket_id') ?? 0;
    $targetWindow = requestInt($_POST, 'target_window') ?? 0;
    $placement = requestEnum($_POST, 'placement', ['top', 'bottom', 'end'], false) ?: 'top';
    $placement = $placement === 'end' ? 'bottom' : $placement;

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
    if ($ticket['status'] === 'serving' && (int) $ticket['served_by'] !== $staffId) {
        respond(['success' => false, 'message' => 'Only the staff member serving this ticket can transfer it.']);
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

    if (in_array(($officeRow['queue_mode'] ?? 'unified'), ['service-based', 'level-based'], true)) {
        $serviceWindowStmt = $db->prepare("
            SELECT 1
            FROM tickets t
            WHERE t.id = ? AND t.office_id = ?
              AND " . queueTicketWindowEligibilitySql('t', false) . "
        ");
        $serviceWindowStmt->bind_param('iiii', $ticketId, $officeId, $targetWindow, $targetWindow);
        if (!$serviceWindowStmt->execute() || !$serviceWindowStmt->get_result()->fetch_assoc()) {
            respond(['success' => false, 'message' => 'That window is not eligible for this ticket.']);
        }
        $serviceWindowStmt->close();
    }

    $newPriority = (int) $ticket['is_priority'];
    $newTimeSpent = accumulatedTicketTimeSpent($ticket);
    $transferNow = date(DATETIME_FORMAT);
    $newStatus = 'waiting';
    $ticketStatus = $ticket['status'];
    $oldWindow = $ticket['window_id'] !== null ? (int) $ticket['window_id'] : null;
    $oldQueueOrderAt = $ticket['queue_order_at'];
    $oldServedBy = $ticket['served_by'] !== null ? (int) $ticket['served_by'] : null;
    $queueOrderAt = getTransferQueueOrderAt($db, $officeId, $newPriority, $placement, $transferNow);

    $connection = $db->getConnection();
    if (!$connection->begin_transaction()) {
        respond(['success' => false, 'message' => 'Could not start window transfer. Please try again.']);
    }
    $stmt = $db->prepare("
        UPDATE tickets
        SET status = ?, is_priority = ?, queue_order_at = ?, served_by = NULL, served_at = NULL,
            completed_at = NULL, on_hold_at = NULL, time_spent = ?, created_at = ?, window_id = ?
        WHERE id = ? AND office_id = ? AND status = ?
          AND (status <> 'serving' OR served_by = ?)
          AND window_id <=> ? AND queue_order_at <=> ? AND served_by <=> ?
    ");
    $stmt->bind_param(
        'sisisiiisiisi',
        $newStatus,
        $newPriority,
        $queueOrderAt,
        $newTimeSpent,
        $transferNow,
        $targetWindow,
        $ticketId,
        $officeId,
        $ticketStatus,
        $staffId,
        $oldWindow,
        $oldQueueOrderAt,
        $oldServedBy
    );
    if (!$stmt->execute() || $stmt->affected_rows !== 1) {
        $connection->rollback();
        respond(['success' => false, 'message' => 'Ticket state changed before it could be transferred. Please refresh the queue.']);
    }
    try {
        recordTicketEvent(
            $db,
            $ticketId,
            'window_transfer',
            $ticketStatus,
            'waiting',
            $staffId,
            $officeId,
            $targetWindow,
            [
                'ticket_number' => $ticket['ticket_number'],
                'source_window' => $oldWindow,
                'destination_window' => $targetWindow,
                'time_spent' => $newTimeSpent,
            ]
        );
    } catch (Throwable $error) {
        $connection->rollback();
        respond(['success' => false, 'message' => 'Could not save ticket history. The window transfer was not recorded.']);
    }
    if (!$connection->commit()) {
        $connection->rollback();
        respond(['success' => false, 'message' => 'Could not confirm window transfer. Please refresh the queue.']);
    }

    logQueueActivity(
        $db,
        $staffId,
        'TICKET_WINDOW_TRANSFERRED',
        $ticketId,
        "Ticket {$ticket['ticket_number']} transferred at office {$officeId} from window " .
            ($oldWindow === null ? 'unassigned' : $oldWindow) . " to window {$targetWindow}.",
        ['ticket_number' => $ticket['ticket_number'], 'office_id' => $officeId, 'window_id' => $oldWindow, 'status' => $ticketStatus],
        ['ticket_number' => $ticket['ticket_number'], 'office_id' => $officeId, 'window_id' => $targetWindow, 'status' => 'waiting']
    );

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

    if ($queueMode === 'level-based') {
        respond([
            'success' => true,
            'queue_mode' => $queueMode,
            'services' => [],
            'levels' => ['shs', 'college'],
        ]);
    }
    if ($queueMode !== 'service-based') {
        respond(['success' => true, 'queue_mode' => $queueMode, 'services' => [], 'levels' => []]);
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

    respond(['success' => true, 'queue_mode' => $queueMode, 'services' => $services, 'levels' => []]);
}

respond(['success' => false, 'message' => 'Unknown action.']);
