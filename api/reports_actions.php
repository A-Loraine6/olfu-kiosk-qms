<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$currentUser = getCurrentUser();
$isStaff     = ($currentUser['role'] ?? '') === 'staff';
$conn        = $db->getConnection();

if ($action === 'staff_metrics') {
    if (!$isStaff) {
        echo json_encode(['success' => false]);
        exit;
    }

    $userId = (int) $currentUser['id'];
    $from   = $input['date_from'] ?? date('Y-m-d');
    $to     = $input['date_to']   ?? date('Y-m-d');

    $from = date('Y-m-d', strtotime($from));
    $to   = date('Y-m-d', strtotime($to));

    $stmt = $conn->prepare("
    SELECT
        COUNT(*) AS served,
        AVG(CASE
            WHEN served_at IS NOT NULL AND completed_at IS NOT NULL
            THEN TIMESTAMPDIFF(SECOND, served_at, completed_at)
            ELSE NULL
        END) AS avg_seconds
    FROM tickets
    WHERE served_by = ?
      AND status = 'completed'
      AND DATE(created_at) BETWEEN ? AND ?
");
    $stmt->bind_param('iss', $userId, $from, $to);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    $stmt2 = $conn->prepare("
    SELECT COUNT(*) AS skipped
    FROM tickets
    WHERE served_by = ?
      AND status = 'skipped'
      AND DATE(created_at) BETWEEN ? AND ?
");
    $stmt2->bind_param('iss', $userId, $from, $to);
    $stmt2->execute();
    $skipped = (int) ($stmt2->get_result()->fetch_assoc()['skipped'] ?? 0);

    echo json_encode([
        'success' => true,
        'metrics' => [
            'served'      => (int) ($row['served'] ?? 0),
            'skipped'     => $skipped,
            'avg_minutes' => $row['avg_seconds'] !== null
                ? round($row['avg_seconds'] / 60, 1) : null,
        ],
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
