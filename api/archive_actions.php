<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/request_validation.php';
require_once __DIR__ . '/archive_functions.php';
require_once __DIR__ . '/activity_logger.php';

if (!isLoggedIn()) {
    logAuthDenied($db->getConnection(), 'Unauthenticated access to archive API', null, 'archive');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = requestEnum($input, 'action', ['restore_staff', 'restore_service', 'delete_staff_permanently', 'delete_service_permanently']);
$csrf   = requestString($input, 'csrf_token', 255, true);

if ($action === null || $csrf === null || !verifyCsrfToken($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$isAdmin = ($_SESSION['role_id'] ?? null) === 1 || ($_SESSION['role'] ?? '') === 'admin';
$canDeleteStaff = $isAdmin;
$canDeleteService = $isAdmin;

if (!($canDeleteStaff || $canDeleteService)) {
    logPermissionDenied($db->getConnection(), 'User lacks admin permission for archive actions', (int) ($_SESSION['user_id'] ?? 0), 'archive');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conn = $db->getConnection();

switch ($action) {
    case 'restore_staff':
        if (!$canDeleteStaff) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to restore staff.']);
            exit;
        }
        $id = requestInt($input, 'staff_id');
        if ($id === null) {
            echo json_encode(['success' => false, 'message' => 'Invalid staff ID.']);
            exit;
        }
        $result = restoreStaff($conn, $id);
        echo json_encode($result);
        break;

    case 'restore_service':
        if (!$canDeleteService) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to restore services.']);
            exit;
        }
        $id = requestInt($input, 'service_id');
        if ($id === null) {
            echo json_encode(['success' => false, 'message' => 'Invalid service ID.']);
            exit;
        }
        $result = restoreService($conn, $id);
        echo json_encode($result);
        break;

    case 'delete_staff_permanently':
        if (!$canDeleteStaff) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to delete staff.']);
            exit;
        }
        $id = requestInt($input, 'staff_id');
        if ($id === null) {
            echo json_encode(['success' => false, 'message' => 'Invalid staff ID.']);
            exit;
        }
        $result = permanentlyDeleteStaff($conn, $id);
        echo json_encode($result);
        break;

    case 'delete_service_permanently':
        if (!$canDeleteService) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to delete services.']);
            exit;
        }
        $id = requestInt($input, 'service_id');
        if ($id === null) {
            echo json_encode(['success' => false, 'message' => 'Invalid service ID.']);
            exit;
        }
        $result = permanentlyDeleteService($conn, $id);
        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
