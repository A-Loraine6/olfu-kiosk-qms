<?php
require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/request_validation.php';
require_once __DIR__ . '/../api/staff_functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = requestInt($_POST, 'staff_id');
if ($id === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$staff = getStaffById($db->getConnection(), $id);
if ($staff) {
    echo json_encode(['success' => true, 'staff' => $staff]);
} else {
    echo json_encode(['success' => false, 'message' => 'Staff not found']);
}
