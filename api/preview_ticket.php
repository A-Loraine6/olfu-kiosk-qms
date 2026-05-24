<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/utils/TicketGenerator.php';

date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$office_id = isset($_POST['office_id']) ? (int)$_POST['office_id'] : 0;

if (!$office_id) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Office ID is required']);
    exit;
}

try {
    $generator     = new TicketGenerator($db);
    $previewTicket = $generator->previewNextTicketNumber($office_id);

    ob_end_clean();

    if ($previewTicket === false) {
        echo json_encode(['success' => false, 'message' => 'Office not found']);
        exit;
    }

    $queueStatus = $generator->getQueueStatus($office_id);
    if ($queueStatus['is_full']) {
        echo json_encode([
            'success' => false,
            'code'    => 'queue_full',
            'message' => 'Maximum tickets for today have already been issued. Please come back tomorrow.',
        ]);
        exit;
    }

    echo json_encode([
        'success'        => true,
        'preview_ticket' => $previewTicket,
    ]);
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load preview']);
}
