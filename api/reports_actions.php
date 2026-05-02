<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

global $db;

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
$isFullView     = ($_SESSION['role_id'] ?? null) === 1
    || ($_SESSION['role'] ?? '') === 'admin'
    || hasPermission('view_reports');
$isScopedView   = !$isFullView && hasPermission('view_reports_office');
$isPersonalView = !$isFullView && !$isScopedView && hasPermission('view_own_metrics');

if ($isScopedView) {
    $input['office_id'] = (int) ($currentUser['office_id'] ?? 0);
}
$conn        = $db->getConnection();

if ($action === 'staff_metrics') {
    if (!$isPersonalView) {
        echo json_encode(['success' => false]);
        exit;
    }

    $userId   = (int) $currentUser['id'];
    $officeId = (int) ($currentUser['office_id'] ?? 0);
    $from     = date('Y-m-d', strtotime($input['date_from'] ?? date('Y-m-d')));
    $to       = date('Y-m-d', strtotime($input['date_to']   ?? date('Y-m-d')));

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
        WHERE office_id = ?
          AND status = 'skipped'
          AND DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt2->bind_param('iss', $officeId, $from, $to);
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

if ($action === 'daily_report') {
    if ($isPersonalView) {
        echo json_encode(['success' => false]);
        exit;
    }

    $from = $input['date_from'] ?? date('Y-m-d');
    $to   = $input['date_to']   ?? date('Y-m-d');
    $office_id = $input['office_id'] ?? null;

    $from = date('Y-m-d', strtotime($from));
    $to   = date('Y-m-d', strtotime($to));

    $where = "DATE(created_at) BETWEEN ? AND ?";
    if ($office_id) {
        $office_id = (int) $office_id;
        $where .= " AND office_id = $office_id";
    }

    $stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_queued,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) AS total_served,
        COUNT(CASE WHEN status = 'skipped' THEN 1 END) AS total_skipped,
        COUNT(CASE WHEN status = 'waiting' THEN 1 END) AS total_waiting,
        ROUND(
            COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0 / 
            NULLIF(COUNT(*), 0), 
            1
        ) AS completion_rate
    FROM tickets
    WHERE $where
    ");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    $from_dt = new DateTime($from);
    $to_dt = new DateTime($to);
    $interval = $from_dt->diff($to_dt)->days + 1;
    $prev_from = $from_dt->sub(new DateInterval('P' . $interval . 'D'))->format('Y-m-d');
    $prev_to = date('Y-m-d', strtotime($from . ' -1 day'));

    $stmt2 = $conn->prepare("
    SELECT
        COUNT(*) AS total_queued,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) AS total_served,
        COUNT(CASE WHEN status = 'skipped' THEN 1 END) AS total_skipped
    FROM tickets
    WHERE $where
    ");
    $stmt2->bind_param('ss', $prev_from, $prev_to);
    $stmt2->execute();
    $prev_row = $stmt2->get_result()->fetch_assoc();

    $current_queued = (int) ($row['total_queued'] ?? 0);
    $prev_queued = (int) ($prev_row['total_queued'] ?? 0);
    $queued_trend = $prev_queued > 0 ? round((($current_queued - $prev_queued) / $prev_queued) * 100) : 0;

    $current_served = (int) ($row['total_served'] ?? 0);
    $prev_served = (int) ($prev_row['total_served'] ?? 0);
    $served_trend = $prev_served > 0 ? round((($current_served - $prev_served) / $prev_served) * 100) : 0;

    echo json_encode([
        'success' => true,
        'report' => [
            'total_queued'      => $current_queued,
            'queued_trend'      => $queued_trend,
            'total_served'      => $current_served,
            'served_trend'      => $served_trend,
            'total_skipped'     => (int) ($row['total_skipped'] ?? 0),
            'total_waiting'     => (int) ($row['total_waiting'] ?? 0),
            'completion_rate'   => (float) ($row['completion_rate'] ?? 0),
        ],
    ]);
    exit;
}

if ($action === 'office_performance') {
    if ($isPersonalView) {
        echo json_encode(['success' => false]);
        exit;
    }

    $from = $input['date_from'] ?? date('Y-m-d');
    $to   = $input['date_to']   ?? date('Y-m-d');
    $office_id = $input['office_id'] ?? null;

    $from = date('Y-m-d', strtotime($from));
    $to   = date('Y-m-d', strtotime($to));

    $office_id = $input['office_id'] ?? null;
    if ($office_id) {
        $office_id = (int) $office_id;
        $stmt = $conn->prepare("
    SELECT
        o.id, o.name,
        COUNT(t.id) AS total_transactions,
        COUNT(CASE WHEN t.status = 'completed' THEN 1 END) AS served,
        COUNT(CASE WHEN t.status = 'skipped' THEN 1 END) AS skipped,
        AVG(CASE
            WHEN t.served_at IS NOT NULL AND t.completed_at IS NOT NULL
            THEN TIMESTAMPDIFF(SECOND, t.served_at, t.completed_at)
            ELSE NULL
        END) AS avg_service_seconds,
        ROUND(COUNT(t.id) * 100.0 / NULLIF((SELECT COUNT(*) FROM tickets WHERE DATE(created_at) BETWEEN ? AND ? AND office_id = ?), 0), 1) AS volume_share
    FROM offices o
    LEFT JOIN tickets t ON o.id = t.office_id AND DATE(t.created_at) BETWEEN ? AND ?
    WHERE o.id = ?
    GROUP BY o.id, o.name
    ORDER BY total_transactions DESC
    ");
        $stmt->bind_param('ssissi', $from, $to, $office_id, $from, $to, $office_id);
    } else {
        $stmt = $conn->prepare("
    SELECT
        o.id, o.name,
        COUNT(t.id) AS total_transactions,
        COUNT(CASE WHEN t.status = 'completed' THEN 1 END) AS served,
        COUNT(CASE WHEN t.status = 'skipped' THEN 1 END) AS skipped,
        AVG(CASE
            WHEN t.served_at IS NOT NULL AND t.completed_at IS NOT NULL
            THEN TIMESTAMPDIFF(SECOND, t.served_at, t.completed_at)
            ELSE NULL
        END) AS avg_service_seconds,
        ROUND(COUNT(t.id) * 100.0 / NULLIF((SELECT COUNT(*) FROM tickets WHERE DATE(created_at) BETWEEN ? AND ?), 0), 1) AS volume_share
    FROM offices o
    LEFT JOIN tickets t ON o.id = t.office_id AND DATE(t.created_at) BETWEEN ? AND ?
    GROUP BY o.id, o.name
    ORDER BY total_transactions DESC
    ");
        $stmt->bind_param('ssss', $from, $to, $from, $to);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $avg_seconds = $row['avg_service_seconds'];
        $data[] = [
            'id'                 => (int) $row['id'],
            'name'               => $row['name'],
            'total_transactions' => (int) $row['total_transactions'],
            'served'             => (int) ($row['served'] ?? 0),
            'skipped'            => (int) ($row['skipped'] ?? 0),
            'avg_service_time'   => $avg_seconds !== null ? round($avg_seconds / 60, 1) : 0,
            'volume_share'       => (float) ($row['volume_share'] ?? 0),
        ];
    }

    echo json_encode([
        'success' => true,
        'performance' => $data,
    ]);
    exit;
}

if ($action === 'peak_hours') {
    if ($isPersonalView) {
        echo json_encode(['success' => false]);
        exit;
    }

    $from = $input['date_from'] ?? date('Y-m-d');
    $to   = $input['date_to']   ?? date('Y-m-d');
    $office_id = $input['office_id'] ?? null;

    $from = date('Y-m-d', strtotime($from));
    $to   = date('Y-m-d', strtotime($to));

    $where = "DATE(created_at) BETWEEN ? AND ?";
    if ($office_id) {
        $office_id = (int) $office_id;
        $where .= " AND office_id = $office_id";
    }

    $stmt = $conn->prepare("
    SELECT
        HOUR(created_at) AS hour,
        COUNT(*) AS queue_count
    FROM tickets
    WHERE $where
    GROUP BY HOUR(created_at)
    ORDER BY hour ASC
    ");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $result = $stmt->get_result();

    $hourly = [];
    for ($i = 0; $i < 24; $i++) {
        $hourly[$i] = 0;
    }
    while ($row = $result->fetch_assoc()) {
        $hourly[(int) $row['hour']] = (int) $row['queue_count'];
    }

    echo json_encode([
        'success' => true,
        'peak_hours' => array_values($hourly),
    ]);
    exit;
}

if ($action === 'department_demand') {
    if ($isPersonalView) {
        echo json_encode(['success' => false]);
        exit;
    }

    $from = $input['date_from'] ?? date('Y-m-d');
    $to   = $input['date_to']   ?? date('Y-m-d');
    $office_id = $input['office_id'] ?? null;

    $from = date('Y-m-d', strtotime($from));
    $to   = date('Y-m-d', strtotime($to));

    $stmt = $conn->prepare("
    SELECT
        o.name AS department,
        COUNT(t.id) AS demand_count
    FROM offices o
    LEFT JOIN tickets t ON o.id = t.office_id AND DATE(t.created_at) BETWEEN ? AND ?
    GROUP BY o.id, o.name
    ORDER BY demand_count DESC
    ");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $result = $stmt->get_result();

    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = [
            'name'  => $row['department'],
            'count' => (int) $row['demand_count'],
        ];
    }

    echo json_encode([
        'success' => true,
        'department_demand' => $departments,
    ]);
    exit;
}

if ($action === 'day_breakdown') {
    if ($isPersonalView) {
        echo json_encode(['success' => false]);
        exit;
    }

    $from = $input['date_from'] ?? date('Y-m-d');
    $to   = $input['date_to']   ?? date('Y-m-d');
    $office_id = $input['office_id'] ?? null;

    $from = date('Y-m-d', strtotime($from));
    $to   = date('Y-m-d', strtotime($to));

    $where = "DATE(created_at) BETWEEN ? AND ?";
    if ($office_id) {
        $office_id = (int) $office_id;
        $where .= " AND office_id = $office_id";
    }

    $stmt = $conn->prepare("
    SELECT
        DAYNAME(created_at) AS day_name,
        DATE(created_at) AS day_date,
        COUNT(*) AS queue_count
    FROM tickets
    WHERE $where
    GROUP BY DATE(created_at)
    ORDER BY created_at ASC
    ");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $result = $stmt->get_result();

    $days = [];
    while ($row = $result->fetch_assoc()) {
        $days[] = [
            'day'   => $row['day_name'],
            'date'  => $row['day_date'],
            'count' => (int) $row['queue_count'],
        ];
    }

    echo json_encode([
        'success' => true,
        'day_breakdown' => $days,
    ]);
    exit;
}

if ($action === 'day_of_week_volume') {
    if ($isPersonalView) {
        echo json_encode(['success' => false]);
        exit;
    }

    $from = $input['date_from'] ?? date('Y-m-d');
    $to   = $input['date_to']   ?? date('Y-m-d');
    $office_id = $input['office_id'] ?? null;

    $from = date('Y-m-d', strtotime($from));
    $to   = date('Y-m-d', strtotime($to));

    $where = "DATE(created_at) BETWEEN ? AND ?";
    if ($office_id) {
        $office_id = (int) $office_id;
        $where .= " AND office_id = $office_id";
    }

    $stmt = $conn->prepare("
    SELECT
        DAYNAME(created_at) AS day_name,
        DAYOFWEEK(created_at) AS day_num,
        COUNT(*) AS count
    FROM tickets
    WHERE $where
    GROUP BY DAYOFWEEK(created_at), DAYNAME(created_at)
    ORDER BY DAYOFWEEK(created_at) ASC
    ");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $result = $stmt->get_result();

    $dow = [];
    while ($row = $result->fetch_assoc()) {
        $dow[] = [
            'day'   => $row['day_name'],
            'count' => (int) $row['count'],
        ];
    }

    echo json_encode([
        'success' => true,
        'day_of_week_volume' => $dow,
    ]);
    exit;
}

if ($action === 'transaction_types') {
    if ($isPersonalView) {
        echo json_encode(['success' => false]);
        exit;
    }

    $from = $input['date_from'] ?? date('Y-m-d');
    $to   = $input['date_to']   ?? date('Y-m-d');
    $office_id = $input['office_id'] ?? null;

    $from = date('Y-m-d', strtotime($from));
    $to   = date('Y-m-d', strtotime($to));

    $where = "DATE(created_at) BETWEEN ? AND ?";
    if ($office_id) {
        $office_id = (int) $office_id;
        $where .= " AND office_id = $office_id";
    }

    $stmt = $conn->prepare("
    SELECT
        transaction_type,
        COUNT(*) AS type_count,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) AS completed_count,
        COUNT(CASE WHEN status = 'skipped' THEN 1 END) AS skipped_count,
        COUNT(CASE WHEN status = 'waiting' THEN 1 END) AS waiting_count
    FROM tickets
    WHERE $where
    GROUP BY transaction_type
    ORDER BY type_count DESC
    ");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $result = $stmt->get_result();

    $types = [];
    while ($row = $result->fetch_assoc()) {
        $types[] = [
            'type'      => $row['transaction_type'],
            'total'     => (int) $row['type_count'],
            'completed' => (int) $row['completed_count'],
            'skipped'   => (int) $row['skipped_count'],
            'waiting'   => (int) $row['waiting_count'],
        ];
    }

    echo json_encode([
        'success' => true,
        'transaction_types' => $types,
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
