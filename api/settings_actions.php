<?php
require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/settings_functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';
$csrf   = $body['csrf_token'] ?? '';

if (!verifyCsrfToken($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
    exit;
}

if (!hasPermission('manage_settings')) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to change system settings.']);
    exit;
}

switch ($action) {

    case 'save_schedule':
        $result = saveOfficeSchedule(
            $db->getConnection(),
            (int)   ($body['office_id']    ?? 0),
            $body['open']        ?? '',
            $body['close']       ?? '',
            $body['break_start'] ?? '',
            $body['break_end']   ?? '',
            $body['cutoff']      ?? ''
        );
        echo json_encode($result);
        break;

    case 'save_queue':
        $result = saveQueueConfig(
            $db->getConnection(),
            (int)   ($body['office_id']   ?? 0),
            $body['queue_prefix']  ?? '',
            (int)   ($body['queue_start'] ?? 1),
            (bool)  ($body['daily_reset'] ?? false),
            (int)   ($body['max_limit']   ?? 0),
            (bool)  ($body['auto_close']  ?? false)
        );
        echo json_encode($result);
        break;

    case 'save_kiosk':
        $result = saveKioskSettings(
            $db->getConnection(),
            (bool)  ($body['kiosk_enabled'] ?? false),
            (int)   ($body['idle_timeout']  ?? 60)
        );
        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
