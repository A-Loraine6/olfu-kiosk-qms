<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache');

require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';

date_default_timezone_set('Asia/Manila');

try {
    $officesResult = $db->query("
        SELECT id, name, code, window_count, queue_prefix, queue_mode
        FROM offices
        ORDER BY name ASC
    ");
    $offices = $officesResult->fetch_all(MYSQLI_ASSOC);
    $officeIds = array_column($offices, 'id');

    if (empty($officeIds)) {
        echo json_encode(['offices' => []]);
        exit;
    }

    $today = date('Y-m-d');
    $placeholders = implode(',', array_fill(0, count($officeIds), '?'));
    $types = str_repeat('i', count($officeIds));

    $stmt = $db->prepare("
        SELECT
            t.office_id,
            t.ticket_number,
            t.transaction_type,
            t.window_id,
            t.status,
            t.served_at,
            t.created_at
        FROM tickets t
        WHERE t.office_id IN ($placeholders)
          AND DATE(t.created_at) = ?
          AND t.status IN ('serving', 'waiting', 'completed', 'skipped')
        ORDER BY t.is_priority DESC, COALESCE(t.queue_order_at, t.created_at) ASC, t.id ASC
    ");
    $params = array_merge($officeIds, [$today]);
    $stmt->bind_param($types . 's', ...$params);
    $stmt->execute();
    $allTickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $byOffice = [];
    foreach ($allTickets as $t) {
        $byOffice[$t['office_id']][] = $t;
    }

    $displayData = [];
    foreach ($offices as $office) {
        $oid = $office['id'];
        $tickets = $byOffice[$oid] ?? [];

        $serving   = array_values(array_filter($tickets, fn($t) => $t['status'] === 'serving'));
        $waiting   = array_values(array_filter($tickets, fn($t) => $t['status'] === 'waiting'));
        $completed = array_values(array_filter($tickets, fn($t) => $t['status'] === 'completed'));

        usort(
            $serving,
            fn($a, $b) =>
            strtotime($b['served_at'] ?? '0') - strtotime($a['served_at'] ?? '0')
        );
        usort(
            $completed,
            fn($a, $b) =>
            strtotime($b['served_at'] ?? $b['created_at']) -
                strtotime($a['served_at'] ?? $a['created_at'])
        );

        $displayData[] = [
            'id'            => $oid,
            'name'          => $office['name'],
            'code'          => $office['code'],
            'prefix'        => $office['queue_prefix'],
            'mode'          => $office['queue_mode'],
            'window_count'  => max(1, (int) $office['window_count']),
            'now_serving'   => $serving,
            'next_up'       => $waiting,
            'recently_done' => array_slice($completed, 0, 3),
            'waiting_count' => count($waiting),
            'served_today'  => count($completed),
        ];
    }

    echo json_encode(['offices' => $displayData]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
