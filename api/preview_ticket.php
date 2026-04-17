<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/utils/TicketGenerator.php';

date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$office_id = isset($_POST['office_id']) ? (int)$_POST['office_id'] : 0;

if (!$office_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Office ID is required']);
    exit;
}

try {
    $generator    = new TicketGenerator($db);
    $previewTicket = $generator->previewNextTicketNumber($office_id);

    if ($previewTicket === false) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Office not found']);
        exit;
    }

    echo json_encode([
        'success'        => true,
        'preview_ticket' => $previewTicket,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
