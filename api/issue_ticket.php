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
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$office_id    = isset($_POST['office_id'])    ? (int)$_POST['office_id']        : 0;
$service_name = isset($_POST['service_name']) ? trim($_POST['service_name'])     : '';

if (!$office_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Office ID is required']);
    exit;
}

try {
    $generator = new TicketGenerator($db);
    $result    = $generator->issueTicket($office_id, $service_name);

    if (!$result['success']) {
        http_response_code(400);
    }

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
