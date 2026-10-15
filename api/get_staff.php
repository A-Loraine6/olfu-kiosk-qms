<?php
require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/request_validation.php';
require_once __DIR__ . '/activity_logger.php';
require_once __DIR__ . '/../api/staff_functions.php';

if (!isLoggedIn()) {
    logAuthDenied($db->getConnection(), 'Unauthenticated attempt to fetch staff record', null, 'staff');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!hasPermission('view_staff') && !hasPermission('edit_staff')) {
    logPermissionDenied($db->getConnection(), 'User lacks view_staff/edit_staff permission for staff lookup', (int) ($_SESSION['user_id'] ?? 0), 'staff');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken(requestString($_POST, 'csrf_token', 255, true) ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
    exit;
}

$id = requestInt($_POST, 'staff_id');
if ($id === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$staff = getStaffById($db->getConnection(), $id);
if ($staff && isDepartmentHeadUser()) {
    $officeId = (int) ($_SESSION['office_id'] ?? 0);
    if ($id === (int) ($_SESSION['user_id'] ?? 0)
        || (int) $staff['office_id'] !== $officeId
        || strcasecmp((string) $staff['role'], 'staff') !== 0) {
        echo json_encode(['success' => false, 'message' => 'Staff member not found in your office.']);
        exit;
    }
    $staff = array_intersect_key($staff, array_flip([
        'id',
        'full_name',
        'email',
        'office',
        'window_number',
    ]));
}
if ($staff) {
    echo json_encode(['success' => true, 'staff' => $staff]);
} else {
    echo json_encode(['success' => false, 'message' => 'Staff not found']);
}
