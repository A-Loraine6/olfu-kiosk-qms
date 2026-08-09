<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/feedback_functions.php';

$rawInput = file_get_contents('php://input');
$input = [];

if ($rawInput !== '') {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}

if (empty($input) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST;
}

$action = trim((string) ($input['action'] ?? ''));

if ($action !== 'submit_feedback') {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

$trackingToken = trim((string) ($input['tracking_token'] ?? ''));
$ticketId = (int) ($input['ticket_id'] ?? 0);
$ticketNumber = trim((string) ($input['ticket_number'] ?? ''));
$rating = (int) ($input['rating'] ?? 0);
$comment = trim((string) ($input['comment'] ?? ''));

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Please select a rating between 1 and 5.']);
    exit;
}

if (mb_strlen($comment) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Feedback comment is too long.']);
    exit;
}

if ($trackingToken === '' && $ticketId <= 0 && $ticketNumber === '') {
    echo json_encode(['success' => false, 'message' => 'Missing ticket reference.']);
    exit;
}

$ticket = null;
if ($trackingToken !== '') {
    $stmt = $db->prepare(
        'SELECT id, ticket_number, office_id, served_by, status FROM tickets WHERE tracking_token = ? LIMIT 1'
    );
    if ($stmt) {
        $stmt->bind_param('s', $trackingToken);
        $stmt->execute();
        $ticket = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
} elseif ($ticketId > 0) {
    $stmt = $db->prepare(
        'SELECT id, ticket_number, office_id, served_by, status FROM tickets WHERE id = ? LIMIT 1'
    );
    if ($stmt) {
        $stmt->bind_param('i', $ticketId);
        $stmt->execute();
        $ticket = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
} elseif ($ticketNumber !== '') {
    $stmt = $db->prepare(
        'SELECT id, ticket_number, office_id, served_by, status FROM tickets WHERE ticket_number = ? ORDER BY created_at DESC LIMIT 1'
    );
    if ($stmt) {
        $stmt->bind_param('s', $ticketNumber);
        $stmt->execute();
        $ticket = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$ticket) {
    echo json_encode(['success' => false, 'message' => 'Ticket not found.']);
    exit;
}

if (($ticket['status'] ?? '') !== 'completed') {
    echo json_encode(['success' => false, 'message' => 'Feedback can only be submitted after the transaction is completed.']);
    exit;
}

$staffId = (int) ($ticket['served_by'] ?? 0);
if ($staffId <= 0) {
    echo json_encode(['success' => false, 'message' => 'No staff member was assigned to this transaction.']);
    exit;
}

$result = saveStaffFeedback($db, [
    'staff_id' => $staffId,
    'office_id' => (int) ($ticket['office_id'] ?? 0),
    'ticket_id' => (int) ($ticket['id'] ?? 0),
    'ticket_number' => (string) ($ticket['ticket_number'] ?? ''),
    'rating' => $rating,
    'comment' => $comment,
]);

if (!$result['success']) {
    http_response_code(400);
}

echo json_encode($result);
