<?php
require_once __DIR__ . '/../api/utils/QueueEligibility.php';

function getTodayQueueSummary($db, $office_id = null)
{
    $today = date('Y-m-d');

    $summary = array(
        'total_queued' => 0,
        'total_served' => 0,
        'total_skipped' => 0
    );

    if ($office_id !== null) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM tickets
                  WHERE DATE(created_at) = ?
                  AND office_id = ?
                  AND status IN ('waiting', 'serving')");
        $stmt->bind_param('si', $today, $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $summary['total_queued'] = (int)$row['count'];
        }

        $stmt = $db->prepare("SELECT COUNT(*) as count FROM tickets
                  WHERE DATE(completed_at) = ?
                  AND office_id = ?
                  AND status = 'completed'");
        $stmt->bind_param('si', $today, $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $summary['total_served'] = (int)$row['count'];
        }

        $stmt = $db->prepare("SELECT COUNT(*) as count FROM tickets
                  WHERE DATE(created_at) = ?
                  AND office_id = ?
                  AND status = 'skipped'");
        $stmt->bind_param('si', $today, $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $summary['total_skipped'] = (int)$row['count'];
        }

        return $summary;
    }

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tickets
              WHERE DATE(created_at) = ?
              AND status IN ('waiting', 'serving')");
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $summary['total_queued'] = (int)$row['count'];
    }

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tickets
              WHERE DATE(completed_at) = ?
              AND status = 'completed'");
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $summary['total_served'] = (int)$row['count'];
    }

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tickets
              WHERE DATE(created_at) = ?
              AND status = 'skipped'");
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $summary['total_skipped'] = (int)$row['count'];
    }

    return $summary;
}

function getQueueByOffice($db, $office_id = null)
{
    $today = date('Y-m-d');
    $distribution = array();

    if ($office_id !== null) {
        $stmt = $db->prepare("SELECT o.id, o.name, COUNT(t.id) as count
                  FROM offices o
                  LEFT JOIN tickets t ON o.id = t.office_id
                  AND DATE(t.created_at) = ?
                  AND t.status IN ('waiting', 'serving', 'completed')
                  WHERE o.id = ?
                  GROUP BY o.id, o.name
                  ORDER BY o.name");
        $stmt->bind_param('si', $today, $office_id);
    } else {
        $stmt = $db->prepare("SELECT o.id, o.name, COUNT(t.id) as count
                  FROM offices o
                  LEFT JOIN tickets t ON o.id = t.office_id
                  AND DATE(t.created_at) = ?
                  AND t.status IN ('waiting', 'serving', 'completed')
                  GROUP BY o.id, o.name
                  ORDER BY o.name");
        $stmt->bind_param('s', $today);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $distribution[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'count' => (int)$row['count']
            );
        }
    }

    return $distribution;
}

function getQueueTrendData($db, $office_id = null)
{
    $today = date('Y-m-d');
    $trendData = array();

    $stmt = $db->prepare("
        SELECT 
            open_time,
            close_time
        FROM offices
        WHERE (? IS NULL OR id = ?)
        LIMIT 1
    ");
    $stmt->bind_param('ii', $office_id, $office_id);
    $stmt->execute();
    $hours = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $openHour  = 6;
    $closeHour = 18;

    if ($hours) {
        if (!empty($hours['open_time'])) {
            $openHour = (int) explode(':', $hours['open_time'])[0];
        }
        if (!empty($hours['close_time'])) {
            $closeHour = (int) explode(':', $hours['close_time'])[0];
        }
    } else {
        $stmt = $db->prepare("
            SELECT 
                COALESCE(MIN(HOUR(open_time)), 8) AS min_open_hour,
                COALESCE(MAX(HOUR(close_time)), 17) AS max_close_hour
            FROM offices
            WHERE open_time IS NOT NULL 
              AND close_time IS NOT NULL
              AND HOUR(open_time) >= 6
              AND HOUR(close_time) <= 23
        ");
        $stmt->execute();
        $hours = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($hours) {
            $openHour  = $hours['min_open_hour'] ?: 8;
            $closeHour = $hours['max_close_hour'] ?: 17;
        }
    }

    $openHour = max(6, min($openHour, 23));
    $closeHour = max($openHour + 1, min($closeHour, 23));

    for ($hour = $openHour; $hour <= $closeHour; $hour++) {
        $hourStart = sprintf('%02d:00:00', $hour);
        $hourEnd = sprintf('%02d:59:59', $hour);

        $hourLabel = sprintf('%02d:00', $hour);

        if ($office_id !== null) {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM tickets
                  WHERE DATE(created_at) = ?
                  AND office_id = ?
                  AND TIME(created_at) BETWEEN ? AND ?
                      AND status IN ('waiting', 'serving', 'completed')");
            $stmt->bind_param('siss', $today, $office_id, $hourStart, $hourEnd);
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM tickets
                  WHERE DATE(created_at) = ?
                  AND TIME(created_at) BETWEEN ? AND ?
                      AND status IN ('waiting', 'serving', 'completed')");
            $stmt->bind_param('sss', $today, $hourStart, $hourEnd);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $count = 0;

        if ($result && $row = $result->fetch_assoc()) {
            $count = (int)$row['count'];
        }

        $trendData[] = array(
            'time' => $hourLabel,
            'count' => $count
        );
    }

    return $trendData;
}

function getAccountingQueueByType($db)
{
    $today = date('Y-m-d');
    $distribution = array();

    $stmt = $db->prepare("SELECT t.transaction_type, tt.category, COUNT(t.id) as count
              FROM tickets t
              LEFT JOIN transaction_types tt ON t.transaction_type = tt.name
              WHERE t.office_id = (SELECT id FROM offices WHERE code = 'ACC')
              AND DATE(t.created_at) = ?
              AND t.status IN ('waiting', 'serving', 'completed')
              GROUP BY t.transaction_type, tt.category
              ORDER BY CASE
                  WHEN tt.category = 'simple' THEN 1
                  WHEN tt.category = 'regular' THEN 2
                  WHEN tt.category = 'complicated' THEN 3
                  ELSE 4
              END");
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $distribution[] = array(
                'type' => $row['transaction_type'],
                'category' => $row['category'],
                'count' => (int)$row['count']
            );
        }
    }

    return $distribution;
}

function getOfficeInfo($db, $office_id)
{
    $office_id = (int)$office_id;
    $stmt = $db->prepare("SELECT id, name, code, window_count, queue_mode FROM offices WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $office_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

function hasQueueData($db)
{
    $query = "SELECT COUNT(*) as count FROM tickets LIMIT 1";
    $result = $db->query($query);

    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['count'] > 0;
    }

    return false;
}

function getOfficeQueueSummary($db, $office_id)
{
    $today     = date('Y-m-d');
    $office_id = (int)$office_id;

    $summary = array(
        'waiting'  => 0,
        'served'   => 0,
        'skipped'  => 0,
    );

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tickets
              WHERE DATE(created_at) = ?
              AND office_id = ?
              AND status IN ('waiting', 'serving')");
    $stmt->bind_param('si', $today, $office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $summary['waiting'] = (int)$row['count'];
    }

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tickets
              WHERE DATE(completed_at) = ?
              AND office_id = ?
              AND status = 'completed'");
    $stmt->bind_param('si', $today, $office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $summary['served'] = (int)$row['count'];
    }

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM tickets
              WHERE DATE(created_at) = ?
              AND office_id = ?
              AND status = 'skipped'");
    $stmt->bind_param('si', $today, $office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $summary['skipped'] = (int)$row['count'];
    }

    return $summary;
}

function getOfficeQueueTrend($db, $office_id)
{
    $today     = date('Y-m-d');
    $office_id = (int)$office_id;
    $trendData = array();

    $stmt = $db->prepare("SELECT open_time, close_time FROM offices WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $office_id);
    $stmt->execute();
    $officeRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $openHour  = 6;
    $closeHour = 18;

    if ($officeRow) {
        if (!empty($officeRow['open_time'])) {
            $openHour = (int)explode(':', $officeRow['open_time'])[0];
        }
        if (!empty($officeRow['close_time'])) {
            $closeHour = (int)explode(':', $officeRow['close_time'])[0];
        }
    }

    for ($hour = $openHour; $hour <= $closeHour; $hour++) {
        $hourStart = sprintf('%02d:00:00', $hour);
        $hourEnd   = sprintf('%02d:59:59', $hour);
        
        $h12 = ($hour % 12) ?: 12;
        $suffix = $hour < 12 ? 'AM' : 'PM';
        $hourLabel = $h12 . ':00 ' . $suffix;

        $stmt = $db->prepare("SELECT COUNT(*) as count FROM tickets
              WHERE DATE(created_at) = ?
              AND office_id = ?
              AND TIME(created_at) BETWEEN ? AND ?
                  AND status IN ('waiting', 'serving', 'completed')");
        $stmt->bind_param('siss', $today, $office_id, $hourStart, $hourEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        $count  = 0;

        if ($result && $row = $result->fetch_assoc()) {
            $count = (int)$row['count'];
        }

        $trendData[] = array(
            'hour'  => $hourLabel,
            'count' => $count,
        );
    }

    return $trendData;
}

function getStaffQueueSummary($db, int $staff_id, ?int $office_id): array
{
    $summary = ['waiting' => 0, 'served' => 0, 'skipped' => 0];
    if (!$office_id) {
        return $summary;
    }

    $today = date('Y-m-d');
    $staffStmt = $db->prepare("
        SELECT u.window_number, u.overflow_mode, o.queue_mode
        FROM users u
        LEFT JOIN offices o ON o.id = u.office_id
        WHERE u.id = ? AND u.office_id = ?
        LIMIT 1
    ");
    $staffStmt->bind_param('ii', $staff_id, $office_id);
    $staffStmt->execute();
    $staffData = $staffStmt->get_result()->fetch_assoc();
    $staffStmt->close();
    if (!$staffData) {
        return $summary;
    }

    $waitingSql = "
        SELECT COUNT(*) AS count
        FROM tickets t
        WHERE t.office_id = ?
          AND DATE(t.created_at) = ?
          AND t.status = 'waiting'
    ";
    $waitingTypes = 'is';
    $waitingParams = [$office_id, $today];
    if (($staffData['queue_mode'] ?? 'unified') === 'service-based') {
        $waitingSql .= ' AND ' . queueTicketServiceEligibilitySql('t');
        $windowNumber = (int) ($staffData['window_number'] ?? 0);
        if (empty($staffData['overflow_mode']) && $windowNumber > 0) {
            $waitingSql .= ' AND ' . queueTicketWindowEligibilitySql('t');
            $waitingTypes .= 'iii';
            $waitingParams[] = $windowNumber;
            $waitingParams[] = $windowNumber;
            $waitingParams[] = $windowNumber;
        }
    } elseif (($staffData['queue_mode'] ?? 'unified') === 'level-based') {
        $windowNumber = (int) ($staffData['window_number'] ?? 0);
        if ($windowNumber > 0) {
            $waitingSql .= ' AND ' . queueTicketWindowEligibilitySql('t');
            $waitingTypes .= 'iii';
            $waitingParams[] = $windowNumber;
            $waitingParams[] = $windowNumber;
            $waitingParams[] = $windowNumber;
        }
    }
    $waitingStmt = $db->prepare($waitingSql);
    $waitingStmt->bind_param($waitingTypes, ...$waitingParams);
    $waitingStmt->execute();
    $summary['waiting'] = (int) ($waitingStmt->get_result()->fetch_assoc()['count'] ?? 0);
    $waitingStmt->close();

    $staffStmt = $db->prepare("
        SELECT
            COUNT(CASE WHEN status = 'completed' AND DATE(completed_at) = ? THEN 1 END) AS served,
            COUNT(CASE WHEN status = 'skipped' AND DATE(created_at) = ? THEN 1 END) AS skipped
        FROM tickets
        WHERE office_id = ? AND served_by = ?
    ");
    $staffStmt->bind_param('ssii', $today, $today, $office_id, $staff_id);
    $staffStmt->execute();
    $staffCounts = $staffStmt->get_result()->fetch_assoc() ?: [];
    $staffStmt->close();
    $summary['served'] = (int) ($staffCounts['served'] ?? 0);
    $summary['skipped'] = (int) ($staffCounts['skipped'] ?? 0);

    return $summary;
}

function getStaffQueueTrend($db, int $staff_id, ?int $office_id): array
{
    if (!$office_id) {
        return [];
    }

    $today = date('Y-m-d');
    $hoursStmt = $db->prepare("
        SELECT open_time, close_time
        FROM offices
        WHERE id = ?
        LIMIT 1
    ");
    $hoursStmt->bind_param('i', $office_id);
    $hoursStmt->execute();
    $officeRow = $hoursStmt->get_result()->fetch_assoc();
    $hoursStmt->close();

    $openHour = !empty($officeRow['open_time']) ? (int) explode(':', $officeRow['open_time'])[0] : 6;
    $closeHour = !empty($officeRow['close_time']) ? (int) explode(':', $officeRow['close_time'])[0] : 18;
    $trendData = [];

    $countStmt = $db->prepare("
        SELECT COUNT(*) AS count
        FROM tickets
        WHERE office_id = ?
          AND served_by = ?
          AND status = 'completed'
          AND DATE(completed_at) = ?
          AND HOUR(completed_at) = ?
    ");
    for ($hour = $openHour; $hour <= $closeHour; $hour++) {
        $countStmt->bind_param('iisi', $office_id, $staff_id, $today, $hour);
        $countStmt->execute();
        $count = (int) ($countStmt->get_result()->fetch_assoc()['count'] ?? 0);
        $hourLabel = (($hour % 12) ?: 12) . ':00 ' . ($hour < 12 ? 'AM' : 'PM');
        $trendData[] = ['hour' => $hourLabel, 'count' => $count];
    }
    $countStmt->close();

    return $trendData;
}
