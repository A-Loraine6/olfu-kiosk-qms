<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store');

require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/request_validation.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/utils/QueueEligibility.php';
require_once __DIR__ . '/utils/TicketEvents.php';

date_default_timezone_set('Asia/Manila');

$token = requestToken($_GET, 'token');

if ($token === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$payload = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawBody = file_get_contents('php://input');
    if ($rawBody !== '') {
        $decodedPayload = json_decode($rawBody, true);
        if (is_array($decodedPayload)) {
            $payload = $decodedPayload;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && requestEnum($payload, 'action', ['cancel_ticket']) === 'cancel_ticket') {
    if (!verifyCsrfToken(requestString($payload, 'csrf_token', 255, true) ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
        exit;
    }
    $selectStmt = $db->prepare("SELECT t.id, t.ticket_number, t.status, t.office_id, t.window_id, t.completed_at, t.served_at, t.transaction_type, t.student_level, o.name AS office_name
        FROM tickets t
        JOIN offices o ON o.id = t.office_id
        WHERE t.tracking_token = ?
        LIMIT 1");
    $selectStmt->bind_param('s', $token);
    $selectStmt->execute();
    $ticketToCancel = $selectStmt->get_result()->fetch_assoc();
    $selectStmt->close();

    if (!$ticketToCancel) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ticket not found']);
        exit;
    }

    if (in_array($ticketToCancel['status'], ['completed', 'cancelled'], true)) {
        echo json_encode([
            'success' => true,
            'message' => 'This ticket is already closed.',
            'status' => $ticketToCancel['status'] === 'completed' ? 'completed' : 'cancelled',
            'cancelled' => $ticketToCancel['status'] !== 'completed',
            'ticket_number' => $ticketToCancel['ticket_number'],
            'office_name' => $ticketToCancel['office_name'],
            'service_name' => $ticketToCancel['transaction_type'] ?? 'General Queue',
            'student_level' => $ticketToCancel['student_level'] ?? null,
        ]);
        exit;
    }

    if (!in_array($ticketToCancel['status'], ['waiting', 'on-hold', 'skipped'], true)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'A ticket already being served cannot be cancelled.']);
        exit;
    }

    $connection = $db->getConnection();
    if (!$connection->begin_transaction()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to start ticket cancellation.']);
        exit;
    }
    $updateStmt = $db->prepare("
        UPDATE tickets SET status = 'cancelled', completed_at = NOW()
        WHERE tracking_token = ? AND status = ?
    ");
    $cancelStatus = $ticketToCancel['status'];
    $updateStmt->bind_param('ss', $token, $cancelStatus);
    $updated = $updateStmt->execute() && $updateStmt->affected_rows === 1;
    $updateStmt->close();

    if (!$updated) {
        $connection->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ticket state changed before it could be cancelled.']);
        exit;
    }
    try {
        recordTicketEvent(
            $db,
            (int) $ticketToCancel['id'],
            'ticket_cancelled',
            $cancelStatus,
            'cancelled',
            null,
            (int) $ticketToCancel['office_id'],
            $ticketToCancel['window_id'] !== null ? (int) $ticketToCancel['window_id'] : null,
            ['ticket_number' => $ticketToCancel['ticket_number'], 'source' => 'student_tracker']
        );
    } catch (Throwable $error) {
        $connection->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to save ticket history; cancellation was not recorded.']);
        exit;
    }
    if (!$connection->commit()) {
        $connection->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to confirm ticket cancellation.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Ticket cancelled successfully.',
        'status' => 'cancelled',
        'cancelled' => true,
        'ticket_number' => $ticketToCancel['ticket_number'],
        'office_name' => $ticketToCancel['office_name'],
        'service_name' => $ticketToCancel['transaction_type'] ?? 'General Queue',
        'student_level' => $ticketToCancel['student_level'] ?? null,
    ]);
    exit;
}

$stmt = $db->prepare("
    SELECT t.id, t.ticket_number, t.status, t.office_id, t.is_priority,
           t.transaction_type, t.student_level, t.created_at, t.queue_order_at, t.served_at, t.completed_at,
           t.window_id, t.served_by,
           u.full_name AS served_by_name,
           o.name AS office_name, o.window_count, o.queue_mode
    FROM   tickets t
    JOIN   offices o ON o.id = t.office_id
    LEFT JOIN users u ON u.id = t.served_by
    WHERE  t.tracking_token = ?
    LIMIT  1
");
$stmt->bind_param('s', $token);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    http_response_code(404);

    $response = [
        'success' => false,
        'message' => 'Ticket not found'
    ];

    echo json_encode($response);
    exit;
}

$finalStatuses = ['completed', 'cancelled'];
$cancelled = ($ticket['status'] === 'cancelled');

if (in_array($ticket['status'], $finalStatuses, true)) {

    $response = [
        'success'          => true,
        'ticket_number'    => $ticket['ticket_number'],
        'ticket_id'        => (int) $ticket['id'],
        'office_name'      => $ticket['office_name'],
        'service_name'     => $ticket['transaction_type'] ?? 'General Queue',
        'student_level'    => $ticket['student_level'] ?? null,
        'status'           => $ticket['status'],
        'staff_id'         => (int) ($ticket['served_by'] ?? 0),
        'staff_name'       => $ticket['served_by_name'] ?? null,
        'cancelled'        => $cancelled,
        'people_ahead'     => 0,
        'now_serving'      => null,
        'total_in_queue'   => 0,
        'counter_window'   => null,
        'service_hours'    => null,
        'location_info'    => null,
        'expired'          => true,
    ];

    echo json_encode($response);
    exit;
}

$today = date('Y-m-d');

if ($ticket['status'] === 'waiting') {
    $aheadSql = "
        SELECT COUNT(*) AS cnt
        FROM tickets t
        WHERE t.office_id = ?
          AND t.status IN ('waiting', 'serving')
          AND DATE(t.created_at) = ?
    ";
    $aheadTypes = 'is';
    $aheadParams = [(int) $ticket['office_id'], $today];
    $laneWindowCount = 1;

    if ($ticket['queue_mode'] === 'service-based') {
        $serviceName = (string) ($ticket['transaction_type'] ?? '');
        $windowNumbers = [];
        if ($ticket['window_id'] !== null) {
            $windowNumbers[] = (int) $ticket['window_id'];
        } elseif ($serviceName !== '') {
            $windowStmt = $db->prepare("
                SELECT DISTINCT sw.window_number
                FROM transaction_types tt
                JOIN service_windows sw
                  ON sw.service_id = tt.id AND sw.office_id = tt.office_id
                WHERE tt.office_id = ? AND tt.name = ?
            ");
            $windowStmt->bind_param('is', $ticket['office_id'], $serviceName);
            $windowStmt->execute();
            $windowRows = $windowStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $windowStmt->close();
            $windowNumbers = array_map(
                static fn(array $row): int => (int) $row['window_number'],
                $windowRows
            );
            if (!$windowNumbers) {
                $windowNumbers = range(1, max(1, (int) $ticket['window_count']));
            }
        }

        if ($serviceName === '' || !$windowNumbers) {
            $aheadSql .= ' AND 1 = 0';
        } else {
            $laneWindowCount = max(1, count($windowNumbers));
            $aheadSql .= " AND t.transaction_type = ?
              AND (
                  t.is_priority > ?
                  OR (t.is_priority = ? AND (
                      COALESCE(t.queue_order_at, t.created_at) < ?
                      OR (COALESCE(t.queue_order_at, t.created_at) = ? AND t.id < ?)
                  ))
              )
              AND " . queueTicketEligibleOnAnyWindowSql('t', $windowNumbers);
            $aheadTypes .= 'siissi' . str_repeat('iii', count($windowNumbers));
            $ticketQueueOrder = $ticket['queue_order_at'] ?: $ticket['created_at'];
            array_push(
                $aheadParams,
                $serviceName,
                (int) $ticket['is_priority'],
                (int) $ticket['is_priority'],
                $ticketQueueOrder,
                $ticketQueueOrder,
                (int) $ticket['id']
            );
            foreach ($windowNumbers as $windowNumber) {
                $aheadParams[] = $windowNumber;
                $aheadParams[] = $windowNumber;
                $aheadParams[] = $windowNumber;
            }
        }
    } elseif ($ticket['queue_mode'] === 'level-based') {
        $studentLevel = $ticket['student_level'];
        $windowNumbers = [];
        if (in_array($studentLevel, ['shs', 'college'], true)) {
            $windowStmt = $db->prepare("
                SELECT window_number
                FROM window_levels
                WHERE office_id = ?
                  AND (student_level = ? OR student_level = 'both')
                ORDER BY window_number
            ");
            $windowStmt->bind_param('is', $ticket['office_id'], $studentLevel);
            $windowStmt->execute();
            $windowRows = $windowStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $windowStmt->close();
            $windowNumbers = array_map(
                static fn(array $row): int => (int) $row['window_number'],
                $windowRows
            );
        }

        if (!$windowNumbers) {
            $aheadSql .= ' AND 1 = 0';
        } else {
            $laneWindowCount = max(1, count($windowNumbers));
            $aheadSql .= " AND t.student_level = ?
              AND (
                  t.is_priority > ?
                  OR (t.is_priority = ? AND (
                      COALESCE(t.queue_order_at, t.created_at) < ?
                      OR (COALESCE(t.queue_order_at, t.created_at) = ? AND t.id < ?)
                  ))
              )
              AND " . queueTicketEligibleOnAnyWindowSql('t', $windowNumbers);
            $aheadTypes .= 'siissi' . str_repeat('iii', count($windowNumbers));
            $ticketQueueOrder = $ticket['queue_order_at'] ?: $ticket['created_at'];
            array_push(
                $aheadParams,
                $studentLevel,
                (int) $ticket['is_priority'],
                (int) $ticket['is_priority'],
                $ticketQueueOrder,
                $ticketQueueOrder,
                (int) $ticket['id']
            );
            foreach ($windowNumbers as $windowNumber) {
                $aheadParams[] = $windowNumber;
                $aheadParams[] = $windowNumber;
                $aheadParams[] = $windowNumber;
            }
        }
    } else {
        $aheadSql .= " AND (
              t.is_priority > ?
              OR (t.is_priority = ? AND (
                  COALESCE(t.queue_order_at, t.created_at) < ?
                  OR (COALESCE(t.queue_order_at, t.created_at) = ? AND t.id < ?)
              ))
          )";
        $aheadTypes .= 'iissi';
        $ticketQueueOrder = $ticket['queue_order_at'] ?: $ticket['created_at'];
        array_push(
            $aheadParams,
            (int) $ticket['is_priority'],
            (int) $ticket['is_priority'],
            $ticketQueueOrder,
            $ticketQueueOrder,
            (int) $ticket['id']
        );
    }

    $aheadStmt = $db->prepare($aheadSql);
    $aheadStmt->bind_param($aheadTypes, ...$aheadParams);
    $aheadStmt->execute();
    $aheadRow = $aheadStmt->get_result()->fetch_assoc();
    $aheadStmt->close();
    $peopleAhead = (int) ($aheadRow['cnt'] ?? 0);
} else {
    $peopleAhead = 0;
}

$servingSql = "
    SELECT ticket_number
    FROM   tickets
    WHERE  office_id        = ?
      AND  status           = 'serving'
      AND  DATE(created_at) = ?
";
$servingTypes = 'is';
$servingParams = [(int) $ticket['office_id'], $today];
if ($ticket['queue_mode'] === 'level-based') {
    if (in_array($ticket['student_level'], ['shs', 'college'], true)) {
        $servingSql .= ' AND student_level = ?';
        $servingTypes .= 's';
        $servingParams[] = $ticket['student_level'];
    } else {
        $servingSql .= ' AND 1 = 0';
    }
}
$servingSql .= ' ORDER BY id DESC LIMIT 1';
$servingStmt = $db->prepare($servingSql);

$servingStmt->bind_param($servingTypes, ...$servingParams);

$servingStmt->execute();

$servingRow = $servingStmt->get_result()->fetch_assoc();

$servingStmt->close();

$nowServing = $servingRow ? $servingRow['ticket_number'] : null;

$avgSql = "
    SELECT AVG(
        COALESCE(time_spent, 0) +
        TIMESTAMPDIFF(SECOND, served_at, completed_at)
    ) AS avg_secs
    FROM tickets
    WHERE office_id = ?
      AND status = 'completed'
      AND DATE(completed_at) = ?
      AND served_at IS NOT NULL
      AND completed_at IS NOT NULL
";
$avgTypes = 'is';
$avgParams = [(int) $ticket['office_id'], $today];
if ($ticket['queue_mode'] === 'level-based') {
    if (in_array($ticket['student_level'], ['shs', 'college'], true)) {
        $avgSql .= ' AND student_level = ?';
        $avgTypes .= 's';
        $avgParams[] = $ticket['student_level'];
    } else {
        $avgSql .= ' AND 1 = 0';
    }
}
$avgStmt = $db->prepare($avgSql);
$avgStmt->bind_param($avgTypes, ...$avgParams);
$avgStmt->execute();
$avgRow = $avgStmt->get_result()->fetch_assoc();
$avgStmt->close();

$avgSecsPerTicket = isset($avgRow['avg_secs']) && $avgRow['avg_secs'] !== null
    ? (float) $avgRow['avg_secs']
    : 300;

$estWaitMins = (int) ceil(($peopleAhead * $avgSecsPerTicket) / $laneWindowCount / 60);

$totalSql = "
    SELECT COUNT(*) AS cnt
    FROM   tickets
    WHERE  office_id        = ?
      AND  status IN ('waiting', 'serving')
      AND  DATE(created_at) = ?
";
$totalTypes = 'is';
$totalParams = [(int) $ticket['office_id'], $today];
if ($ticket['queue_mode'] === 'level-based') {
    if (in_array($ticket['student_level'], ['shs', 'college'], true)) {
        $totalSql .= ' AND student_level = ?';
        $totalTypes .= 's';
        $totalParams[] = $ticket['student_level'];
    } else {
        $totalSql .= ' AND 1 = 0';
    }
}
$totalStmt = $db->prepare($totalSql);

$totalStmt->bind_param($totalTypes, ...$totalParams);
$totalStmt->execute();
$totalRow = $totalStmt->get_result()->fetch_assoc();
$totalStmt->close();

$totalInQueue = (int)($totalRow['cnt'] ?? 0);

$response = [
    'success'           => true,
    'ticket_number'     => $ticket['ticket_number'],
    'office_name'       => $ticket['office_name'],
    'service_name'      => $ticket['transaction_type'] ?? 'General Queue',
    'student_level'     => $ticket['student_level'] ?? null,
    'status'            => $ticket['status'],
    'cancelled'         => false,
    'people_ahead'      => $peopleAhead,
    'now_serving'       => $nowServing,
    'est_wait_mins'     => $estWaitMins,
    'total_in_queue'    => $totalInQueue,
    'counter_window'    => $ticket['status'] === 'serving' && (int) ($ticket['window_id'] ?? 0) > 0
        ? (int) $ticket['window_id']
        : null,
    'service_hours'     => null,
    'location_info'     => null,
    'expired'           => false,
];

echo json_encode($response);
exit;
