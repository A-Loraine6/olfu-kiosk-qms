<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/request_validation.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/utils/TicketGenerator.php';

date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!verifyCsrfToken(requestString($_POST, 'csrf_token', 255, true) ?? '')) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
    exit;
}

$office_id   = requestInt($_POST, 'office_id');
$is_priority = requestBool($_POST, 'is_priority', false);
$serviceInput = requestString($_POST, 'service_name', 100);
$service_name = $serviceInput === '' ? '' : ($serviceInput === null ? null : validServiceName($serviceInput, 100));
$studentLevelInput = requestString($_POST, 'student_level', 20);
$student_level = ($studentLevelInput === null || $studentLevelInput === '')
    ? null
    : requestEnum(['student_level' => $studentLevelInput], 'student_level', ['shs', 'college']);

if ($office_id === null || $is_priority === null || $service_name === null
    || ($studentLevelInput !== null && $studentLevelInput !== '' && $student_level === null)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Office ID is required']);
    exit;
}

try {
    $generator = new TicketGenerator($db);
    $result    = $generator->previewNextTicketNumber($office_id, $is_priority, $service_name, $student_level);

    ob_end_clean();
    echo json_encode($result);
    exit;
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load preview']);
    exit;
}