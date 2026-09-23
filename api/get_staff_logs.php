<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/request_validation.php';
require_once __DIR__ . '/activity_logger.php';

if (!isLoggedIn()) {
    logAuthDenied($db->getConnection(), 'Unauthenticated attempt to view staff activity history', null, 'staff');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!hasPermission('view_staff') && !hasPermission('edit_staff')) {
    logPermissionDenied($db->getConnection(), 'User lacks view_staff/edit_staff permission for staff activity logs', (int) ($_SESSION['user_id'] ?? 0), 'staff');
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$csrf  = requestString($input, 'csrf_token', 255, true);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $csrf === null || !verifyCsrfToken($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
    exit;
}

if (!requestOptionalIntIsValid($input, 'page', 1)) {
    echo json_encode(['success' => false, 'message' => 'Invalid page number.']);
    exit;
}

$staffId = requestInt($input, 'staff_id');
$page    = requestOptionalInt($input, 'page', 1) ?? 1;
$limit   = 25;

if ($staffId === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid staff ID.']);
    exit;
}

$search   = requestString($input, 'search', 100) ?? '';
$dateFrom = requestString($input, 'date_from', 10) ?? '';
$dateTo   = requestString($input, 'date_to', 10) ?? '';

if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = '';
if ($dateTo   !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = '';

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

// Build the shared WHERE clause once so the count query and the page query
// always stay in sync with each other.
$where  = "WHERE user_id = ?";
$params = [$staffId];
$types  = 'i';

if ($search !== '') {
    $where   .= " AND (description LIKE ? OR activity_type LIKE ?)";
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}
if ($dateFrom !== '') {
    $where   .= " AND DATE(timestamp) >= ?";
    $params[] = $dateFrom;
    $types   .= 's';
}
if ($dateTo !== '') {
    $where   .= " AND DATE(timestamp) <= ?";
    $params[] = $dateTo;
    $types   .= 's';
}

$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM activity_logs $where");
$countStmt->bind_param($types, ...$params);
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
     $where
     ORDER BY timestamp DESC
     LIMIT ? OFFSET ?"
);
$logsParams = $params;
$logsParams[] = $limit;
$logsParams[] = $offset;
$logsTypes    = $types . 'ii';
$logsStmt->bind_param($logsTypes, ...$logsParams);
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