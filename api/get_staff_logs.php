<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!hasPermission('view_staff') && !hasPermission('edit_staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$staffId = (int) ($input['staff_id'] ?? 0);
$page    = max(1, (int) ($input['page']  ?? 1));
$limit   = 25;

if (!$staffId) {
    echo json_encode(['success' => false, 'message' => 'Invalid staff ID.']);
    exit;
}

$conn = $db->getConnection();

$infoStmt = $conn->prepare(
    "SELECT u.full_name, u.email, o.name AS office_name, r.name AS role_name, u.is_active
     FROM users u
     LEFT JOIN offices o ON u.office_id = o.id
     LEFT JOIN roles r ON u.role_id = r.id
     WHERE u.id = ? AND u.is_deleted = 0
     LIMIT 1"
);
$infoStmt->bind_param('i', $staffId);
$infoStmt->execute();
$staffInfo = $infoStmt->get_result()->fetch_assoc();
$infoStmt->close();

if (!$staffInfo) {
    echo json_encode(['success' => false, 'message' => 'Staff member not found.']);
    exit;
}

$countStmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM activity_logs WHERE user_id = ?"
);
$countStmt->bind_param('i', $staffId);
$countStmt->execute();
$countRow   = $countStmt->get_result()->fetch_assoc();
$countStmt->close();
$totalCount = (int) ($countRow['total'] ?? 0);
$totalPages = max(1, (int) ceil($totalCount / $limit));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $limit;

$logsStmt = $conn->prepare(
    "SELECT id, activity_type, entity_type, entity_id,
            old_value, new_value, description, ip_address, timestamp
     FROM activity_logs
     WHERE user_id = ?
     ORDER BY timestamp DESC
     LIMIT ? OFFSET ?"
);
$logsStmt->bind_param('iii', $staffId, $limit, $offset);
$logsStmt->execute();
$logsResult = $logsStmt->get_result();
$logs = [];
while ($row = $logsResult->fetch_assoc()) {
    $logs[] = $row;
}
$logsStmt->close();

echo json_encode([
    'success'    => true,
    'staff'      => $staffInfo,
    'logs'       => $logs,
    'count'      => $totalCount,
    'page'       => $page,
    'totalPages' => $totalPages,
    'limit'      => $limit,
]);
