<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

date_default_timezone_set('Asia/Manila');

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!$token || strlen($token) !== 64 || !ctype_xdigit($token)) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($payload['action'] ?? '') === 'cancel_ticket') {
    $selectStmt = $db->prepare("SELECT t.id, t.ticket_number, t.status, t.completed_at, t.served_at, t.transaction_type, o.name AS office_name
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

    if (in_array($ticketToCancel['status'], ['completed', 'skipped', 'cancelled'], true)) {
        echo json_encode([
            'success' => true,
            'message' => 'This ticket is already closed.',
            'status' => $ticketToCancel['status'] === 'completed' ? 'completed' : 'cancelled',
            'cancelled' => $ticketToCancel['status'] !== 'completed',
            'ticket_number' => $ticketToCancel['ticket_number'],
            'office_name' => $ticketToCancel['office_name'],
            'service_name' => $ticketToCancel['transaction_type'] ?? 'General Queue',
        ]);
        exit;
    }

    $updateStmt = $db->prepare("UPDATE tickets SET status = 'cancelled', completed_at = NOW() WHERE tracking_token = ?");
    $updateStmt->bind_param('s', $token);
    $updated = $updateStmt->execute();
    $updateStmt->close();

    if (!$updated) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to cancel ticket.']);
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
    ]);
    exit;
}

$rateLimitKey = 'track_' . $token;
$cacheFile = sys_get_temp_dir() . '/' . md5($rateLimitKey) . '.tmp';

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 5) {
    echo file_get_contents($cacheFile);
    exit;
}

$stmt = $db->prepare("
    SELECT t.id, t.ticket_number, t.status, t.office_id,
           t.transaction_type, t.created_at, t.served_at, t.completed_at,
           t.window_id,
           o.name AS office_name, o.window_count
    FROM   tickets t
    JOIN   offices o ON o.id = t.office_id
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

    file_put_contents($cacheFile, json_encode($response));

    echo json_encode($response);
    exit;
}

$finalStatuses = ['completed', 'skipped', 'cancelled'];
$cancelled = ($ticket['status'] === 'cancelled');

if (in_array($ticket['status'], $finalStatuses, true)) {

    $response = [
        'success'          => true,
        'ticket_number'    => $ticket['ticket_number'],
        'office_name'      => $ticket['office_name'],
        'service_name'     => $ticket['transaction_type'] ?? 'General Queue',
        'status'           => $ticket['status'],
        'cancelled'        => $cancelled,
        'people_ahead'     => 0,
        'now_serving'      => null,
        'total_in_queue'   => 0,
        'counter_window'   => null,
        'service_hours'    => null,
        'location_info'    => null,
        'expired'          => true,
    ];

    file_put_contents($cacheFile, json_encode($response));

    echo json_encode($response);
    exit;
}

$today = date('Y-m-d');

$activeQueueStatuses = ['waiting', 'serving'];

$aheadStmt = $db->prepare("
    SELECT COUNT(*) AS cnt
    FROM   tickets
    WHERE  office_id        = ?
      AND  status IN ('waiting', 'serving')
      AND  DATE(created_at) = ?
      AND  id               < ?
");

$aheadStmt->bind_param('isi', $ticket['office_id'], $today, $ticket['id']);
$aheadStmt->execute();

$aheadRow = $aheadStmt->get_result()->fetch_assoc();

$aheadStmt->close();

$peopleAhead = (int)($aheadRow['cnt'] ?? 0);

$servingStmt = $db->prepare("
    SELECT ticket_number
    FROM   tickets
    WHERE  office_id        = ?
      AND  status           = 'serving'
      AND  DATE(created_at) = ?
    ORDER BY id DESC
    LIMIT  1
");

$servingStmt->bind_param('is', $ticket['office_id'], $today);

$servingStmt->execute();

$servingRow = $servingStmt->get_result()->fetch_assoc();

$servingStmt->close();

$nowServing = $servingRow ? $servingRow['ticket_number'] : null;

$avgStmt = $db->prepare("
    SELECT AVG(TIMESTAMPDIFF(SECOND, served_at, completed_at)) AS avg_secs
    FROM tickets
    WHERE office_id = ?
      AND status = 'completed'
      AND DATE(completed_at) = ?
      AND served_at IS NOT NULL
      AND completed_at IS NOT NULL
");
$avgStmt->bind_param('is', $ticket['office_id'], $today);
$avgStmt->execute();
$avgRow = $avgStmt->get_result()->fetch_assoc();
$avgStmt->close();

$avgSecsPerTicket = isset($avgRow['avg_secs']) && $avgRow['avg_secs'] !== null
    ? (float) $avgRow['avg_secs']
    : 300;

$estWaitMins = (int) ceil(($peopleAhead * $avgSecsPerTicket) / 60);

$totalStmt = $db->prepare("
    SELECT COUNT(*) AS cnt
    FROM   tickets
    WHERE  office_id        = ?
      AND  status IN ('waiting', 'serving')
      AND  DATE(created_at) = ?
");

$totalStmt->bind_param('is', $ticket['office_id'], $today);
$totalStmt->execute();
$totalRow = $totalStmt->get_result()->fetch_assoc();
$totalStmt->close();

$totalInQueue = (int)($totalRow['cnt'] ?? 0);

$response = [
    'success'           => true,
    'ticket_number'     => $ticket['ticket_number'],
    'office_name'       => $ticket['office_name'],
    'service_name'      => $ticket['transaction_type'] ?? 'General Queue',
    'status'            => $ticket['status'],
    'cancelled'         => false,
    'people_ahead'      => $peopleAhead,
    'now_serving'       => $nowServing,
    'est_wait_mins'     => $estWaitMins,
    'total_in_queue'    => $totalInQueue,
    'counter_window'    => isset($ticket['window_id']) && $ticket['window_id'] !== null ? (int) $ticket['window_id'] : null,
    'service_hours'     => null,
    'location_info'     => null,
    'expired'           => false,
];

file_put_contents($cacheFile, json_encode($response));

echo json_encode($response);
exit;
