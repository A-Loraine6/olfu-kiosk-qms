<?php
require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/request_validation.php';
require_once __DIR__ . '/settings_functions.php';
require_once __DIR__ . '/activity_logger.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    logAuthDenied($db->getConnection(), 'Unauthenticated access to settings API', null, 'settings');
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = requestEnum($body, 'action', ['save_schedule', 'save_queue', 'save_kiosk']);
$csrf   = requestString($body, 'csrf_token', 255, true);

if ($action === null || $csrf === null || !verifyCsrfToken($csrf)) {
    logSuspiciousApiActivity($db->getConnection(), 'Invalid CSRF token or malformed settings request', (int) ($_SESSION['user_id'] ?? 0), 'settings');
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
    exit;
}

if (!hasPermission('manage_settings')) {
    logPermissionDenied($db->getConnection(), 'Attempted settings change without manage_settings permission', (int) ($_SESSION['user_id'] ?? 0), 'settings');
    echo json_encode(['success' => false, 'message' => 'You do not have permission to change system settings.']);
    exit;
}

switch ($action) {

    case 'save_schedule':
        $result = saveOfficeSchedule(
            $db->getConnection(),
            requestInt($body, 'office_id') ?? 0,
            requestString($body, 'open', 8) ?? '',
            requestString($body, 'close', 8) ?? '',
            requestString($body, 'break_start', 8) ?? '',
            requestString($body, 'break_end', 8) ?? '',
            requestString($body, 'cutoff', 8) ?? ''
        );
        echo json_encode($result);
        break;

    case 'save_queue':
        if (!requestOptionalIntIsValid($body, 'queue_start', 1) || !requestOptionalIntIsValid($body, 'max_limit', 0) || !requestBoolIsValid($body, 'daily_reset') || !requestBoolIsValid($body, 'auto_close')) {
            echo json_encode(['success' => false, 'message' => 'Invalid queue settings.']);
            break;
        }
        $result = saveQueueConfig(
            $db->getConnection(),
            requestInt($body, 'office_id') ?? 0,
            requestString($body, 'queue_prefix', 10) ?? '',
            requestOptionalInt($body, 'queue_start', 1) ?? 1,
            requestBool($body, 'daily_reset', false) ?? false,
            requestOptionalInt($body, 'max_limit', 0) ?? 0,
            requestBool($body, 'auto_close', false) ?? false
        );
        echo json_encode($result);
        break;

    case 'save_kiosk':
        if (!requestOptionalIntIsValid($body, 'idle_timeout', 1) || !requestBoolIsValid($body, 'kiosk_enabled')) {
            echo json_encode(['success' => false, 'message' => 'Invalid kiosk settings.']);
            break;
        }
        $result = saveKioskSettings(
            $db->getConnection(),
            requestBool($body, 'kiosk_enabled', false) ?? false,
            requestOptionalInt($body, 'idle_timeout', 1) ?? 60
        );
        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
