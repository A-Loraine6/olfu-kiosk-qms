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
    echo json_encode(['success' => false, 'code' => 'method_not_allowed', 'message' => 'Method not allowed']);
    exit;
}

$office_id    = isset($_POST['office_id'])    ? (int)$_POST['office_id']    : 0;
$service_name = isset($_POST['service_name']) ? trim($_POST['service_name']) : '';

if (!$office_id) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'code' => 'missing_office', 'message' => 'Office ID is required']);
    exit;
}

try {
    $generator = new TicketGenerator($db);
    $result    = $generator->issueTicket($office_id, $service_name);

    ob_end_clean();
    echo json_encode($result);
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'code'    => 'server_error',
        'message' => 'A server error occurred. Please try again or approach a staff member.',
    ]);
}
