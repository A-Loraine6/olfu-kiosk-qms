<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';

date_default_timezone_set('Asia/Manila');

try {
    $today = date('Y-m-d');
    $stmt = $db->prepare("
        SELECT o.id, COUNT(t.id) AS waiting
        FROM offices o
        LEFT JOIN tickets t
          ON t.office_id = o.id
         AND DATE(t.created_at) = ?
         AND t.status IN ('waiting', 'serving')
        GROUP BY o.id
    ");
    if (!$stmt) {
        throw new RuntimeException('Could not prepare kiosk queue counts query.');
    }

    $stmt->bind_param('s', $today);
    if (!$stmt->execute()) {
        throw new RuntimeException('Could not load kiosk queue counts.');
    }

    $offices = array_map(
        static fn(array $row): array => [
            'id' => (int) $row['id'],
            'waiting' => (int) $row['waiting'],
        ],
        $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    );
    $stmt->close();

    echo json_encode(['success' => true, 'offices' => $offices]);
} catch (Throwable $error) {
    error_log('Kiosk queue counts failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not refresh queue counts.']);
}
