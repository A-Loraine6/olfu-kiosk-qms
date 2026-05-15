<?php
function getTodayQueueSummary($db)
{
    $today = date('Y-m-d');

    $summary = array(
        'total_queued' => 0,
        'total_served' => 0,
        'total_skipped' => 0
    );

    $query = "SELECT COUNT(*) as count FROM tickets 
              WHERE DATE(created_at) = '$today' 
              AND status IN ('waiting', 'serving')";
    $result = $db->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $summary['total_queued'] = (int)$row['count'];
    }

    $query = "SELECT COUNT(*) as count FROM tickets 
              WHERE DATE(completed_at) = '$today' 
              AND status = 'completed'";
    $result = $db->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $summary['total_served'] = (int)$row['count'];
    }

    $query = "SELECT COUNT(*) as count FROM tickets 
              WHERE DATE(created_at) = '$today' 
              AND status = 'skipped'";
    $result = $db->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $summary['total_skipped'] = (int)$row['count'];
    }

    return $summary;
}

function getQueueByOffice($db)
{
    $today = date('Y-m-d');
    $distribution = array();

    $query = "SELECT o.id, o.name, COUNT(t.id) as count 
              FROM offices o 
              LEFT JOIN tickets t ON o.id = t.office_id 
              AND DATE(t.created_at) = '$today'
              AND t.status IN ('waiting', 'serving', 'completed')
              GROUP BY o.id, o.name 
              ORDER BY o.name";

    $result = $db->query($query);

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

function getQueueTrendData($db)
{
    $today = date('Y-m-d');
    $trendData = array();

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

    $openHour  = $hours['min_open_hour'] ?: 8;
    $closeHour = $hours['max_close_hour'] ?: 17;

    $openHour = max(6, min($openHour, 23));
    $closeHour = max($openHour + 1, min($closeHour, 23));

    for ($hour = $openHour; $hour <= $closeHour; $hour++) {
        $hourStart = sprintf('%02d:00:00', $hour);
        $hourEnd = sprintf('%02d:59:59', $hour);
        
        $hourLabel = sprintf('%02d:00', $hour);

        $query = "SELECT COUNT(*) as count FROM tickets 
                  WHERE DATE(created_at) = '$today' 
                  AND TIME(created_at) BETWEEN '$hourStart' AND '$hourEnd'
                  AND status IN ('waiting', 'serving', 'completed')";

        $result = $db->query($query);
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

    $query = "SELECT t.transaction_type, tt.category, COUNT(t.id) as count 
              FROM tickets t 
              LEFT JOIN transaction_types tt ON t.transaction_type = tt.name
              WHERE t.office_id = (SELECT id FROM offices WHERE code = 'ACC')
              AND DATE(t.created_at) = '$today'
              AND t.status IN ('waiting', 'serving', 'completed')
              GROUP BY t.transaction_type, tt.category 
              ORDER BY CASE 
                  WHEN tt.category = 'simple' THEN 1
                  WHEN tt.category = 'regular' THEN 2
                  WHEN tt.category = 'complicated' THEN 3
                  ELSE 4
              END";

    $result = $db->query($query);

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
    $query = "SELECT id, name, code, window_count, queue_mode FROM offices WHERE id = $office_id LIMIT 1";
    $result = $db->query($query);

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

    $query = "SELECT COUNT(*) as count FROM tickets
              WHERE DATE(created_at) = '$today'
              AND office_id = $office_id
              AND status IN ('waiting', 'serving')";
    $result = $db->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $summary['waiting'] = (int)$row['count'];
    }

    $query = "SELECT COUNT(*) as count FROM tickets
              WHERE DATE(completed_at) = '$today'
              AND office_id = $office_id
              AND status = 'completed'";
    $result = $db->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $summary['served'] = (int)$row['count'];
    }

    $query = "SELECT COUNT(*) as count FROM tickets
              WHERE DATE(created_at) = '$today'
              AND office_id = $office_id
              AND status = 'skipped'";
    $result = $db->query($query);
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

        $query = "SELECT COUNT(*) as count FROM tickets
                  WHERE DATE(created_at) = '$today'
                  AND office_id = $office_id
                  AND TIME(created_at) BETWEEN '$hourStart' AND '$hourEnd'
                  AND status IN ('waiting', 'serving', 'completed')";

        $result = $db->query($query);
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
