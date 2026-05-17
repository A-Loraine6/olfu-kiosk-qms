<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/archive_functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? '';
$csrf   = $input['csrf_token'] ?? '';

if (!verifyCsrfToken($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$isAdmin = ($_SESSION['role_id'] ?? null) === 1 || ($_SESSION['role'] ?? '') === 'admin';
$canDeleteStaff = $isAdmin;
$canDeleteService = $isAdmin;

if (!($canDeleteStaff || $canDeleteService)) {
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
        $id = (int) ($input['staff_id'] ?? 0);
        if ($id <= 0) {
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
        $result = restoreService($conn, (int) ($input['service_id'] ?? 0));
        echo json_encode($result);
        break;

    case 'delete_staff_permanently':
        if (!$canDeleteStaff) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to delete staff.']);
            exit;
        }
        $result = permanentlyDeleteStaff($conn, (int) ($input['staff_id'] ?? 0));
        echo json_encode($result);
        break;

    case 'delete_service_permanently':
        if (!$canDeleteService) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to delete services.']);
            exit;
        }
        $result = permanentlyDeleteService($conn, (int) ($input['service_id'] ?? 0));
        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
