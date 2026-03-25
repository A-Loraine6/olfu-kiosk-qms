<?php

/**
 * OLFU Queue Management System - Queue Functions
 * All functions query the actual database and return empty states when no data exists
 */

/**
 * Get today's queue summary statistics
 * Returns array with total_queued, total_served, total_skipped
 */
function getTodayQueueSummary($db)
{
    $today = date('Y-m-d');

    $summary = array(
        'total_queued' => 0,
        'total_served' => 0,
        'total_skipped' => 0
    );

    // Get total queued today (waiting + serving + completed)
    $query = "SELECT COUNT(*) as count FROM tickets 
              WHERE DATE(created_at) = '$today' 
              AND status IN ('waiting', 'serving', 'completed')";
    $result = $db->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $summary['total_queued'] = (int)$row['count'];
    }

    // Get total served today (completed)
    $query = "SELECT COUNT(*) as count FROM tickets 
              WHERE DATE(completed_at) = '$today' 
              AND status = 'completed'";
    $result = $db->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $summary['total_served'] = (int)$row['count'];
    }

    // Get total skipped today
    $query = "SELECT COUNT(*) as count FROM tickets 
              WHERE DATE(created_at) = '$today' 
              AND status = 'skipped'";
    $result = $db->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $summary['total_skipped'] = (int)$row['count'];
    }

    return $summary;
}

/**
 * Get queue distribution by office
 * Returns array of offices with their queue counts
 */
function getQueueByOffice($db)
{
    $today = date('Y-m-d');
    $distribution = array();

    // Get queue count per office for today
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

/**
 * Get queue trend data by hour
 * Returns array of hourly queue volumes
 */
function getQueueTrendData($db)
{
    $today = date('Y-m-d');
    $trendData = array();

    // Create hourly slots from 6 AM to 6 PM (typical office hours)
    for ($hour = 6; $hour <= 18; $hour++) {
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

/**
 * Get queue distribution by transaction type for Accounting
 * Returns array of transaction types with their queue counts
 */
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

/**
 * Get office information including window count and queue mode
 */
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

/**
 * Check if there is any data in the system
 * Used to determine if we should show empty state messages
 */
function hasQueueData($db)
{
    $query = "SELECT COUNT(*) as count FROM tickets LIMIT 1";
    $result = $db->query($query);

    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['count'] > 0;
    }

    return false;
}
