<?php
require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

date_default_timezone_set('Asia/Manila');

function logActivity(
    $db,
    string  $activity_type,
    string  $entity_type,
    ?int    $entity_id    = null,
    ?string $description  = null,
    ?array  $before_values = null,
    ?array  $after_values  = null,
    ?int    $actor_id      = null
): bool {
    if ($actor_id === null) {
        $user = getCurrentUser();
        $actor_id = $user['id'] ?? null;
    }

    $allowNullActor = in_array($activity_type, [
        'LOGIN_FAILED',
        'AUTH_DENIED',
        'PERMISSION_DENIED',
        'API_SUSPICIOUS',
        'DATA_EXPORT',
        'ROLE_CHANGE',
        'ADMIN_ACTION',
        'CONFIG_CHANGE',
        'USER_DELETE',
        'SERVICE_DELETE',
    ], true);

    if ($actor_id === null && !$allowNullActor) {
        return false;
    }

    if ($before_values !== null && $after_values !== null) {
        $hasChanges = false;
        foreach ($after_values as $key => $newVal) {
            $oldVal = $before_values[$key] ?? null;
            if ($oldVal != $newVal) {
                $hasChanges = true;
                break;
            }
        }
        if (!$hasChanges) {
            return true;
        }
    }

    $user_id    = $actor_id;
    $ip_address = $_SERVER['REMOTE_ADDR']      ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT']   ?? '';
    $timestamp  = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');
    $before_json = !empty($before_values) ? json_encode($before_values) : null;
    $after_json  = !empty($after_values)  ? json_encode($after_values)  : null;

    $stmt = $db->prepare(
        "INSERT INTO activity_logs
            (user_id, activity_type, entity_type, entity_id, description,
             old_value, new_value, ip_address, user_agent, timestamp)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        error_log("Activity log prepare error: " . $db->error);
        return false;
    }

    $stmt->bind_param(
        'ississssss',
        $user_id,
        $activity_type,
        $entity_type,
        $entity_id,
        $description,
        $before_json,
        $after_json,
        $ip_address,
        $user_agent,
        $timestamp
    );

    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

if (!function_exists('logFailedLogin')) {
    function logFailedLogin($db, string $description, ?int $userId = null): void
    {
        logActivity($db, 'LOGIN_FAILED', $userId ? 'user' : 'auth', $userId, $description, null, null, $userId);
    }
}

if (!function_exists('logAuthDenied')) {
    function logAuthDenied($db, string $description, ?int $userId = null, ?string $entityType = 'auth', ?int $entityId = null): void
    {
        logActivity($db, 'AUTH_DENIED', $entityType ?? 'auth', $entityId, $description, null, null, $userId);
    }
}

if (!function_exists('logPermissionDenied')) {
    function logPermissionDenied($db, string $description, ?int $userId = null, ?string $entityType = 'auth', ?int $entityId = null): void
    {
        logActivity($db, 'PERMISSION_DENIED', $entityType ?? 'auth', $entityId, $description, null, null, $userId);
    }
}

if (!function_exists('logSuspiciousApiActivity')) {
    function logSuspiciousApiActivity($db, string $description, ?int $userId = null, ?string $entityType = 'api', ?int $entityId = null): void
    {
        logActivity($db, 'API_SUSPICIOUS', $entityType ?? 'api', $entityId, $description, null, null, $userId);
    }
}

if (!function_exists('logDataExport')) {
    function logDataExport($db, string $description, ?int $userId = null, ?string $entityType = 'report', ?int $entityId = null): void
    {
        logActivity($db, 'DATA_EXPORT', $entityType ?? 'report', $entityId, $description, null, null, $userId);
    }
}

if (!function_exists('logAdminAction')) {
    function logAdminAction($db, string $description, ?int $userId = null, ?string $entityType = 'admin', ?int $entityId = null, ?array $before = null, ?array $after = null): void
    {
        logActivity($db, 'ADMIN_ACTION', $entityType ?? 'admin', $entityId, $description, $before, $after, $userId);
    }
}

if (!function_exists('logConfigChange')) {
    function logConfigChange($db, string $entityType, ?int $entityId, string $description, ?array $before = null, ?array $after = null, ?int $userId = null): void
    {
        logActivity($db, 'CONFIG_CHANGE', $entityType, $entityId, $description, $before, $after, $userId);
    }
}

function getActivityLogs(
    $db,
    ?int    $user_id       = null,
    ?string $activity_type = null,
    ?string $entity_type   = null,
    ?string $start_date    = null,
    ?string $end_date      = null,
    ?string $search        = null,
    int     $limit         = 100,
    int     $offset        = 0
): array {
    $query = "SELECT
                al.id,
                al.user_id,
                u.full_name  AS user_name,
                al.activity_type,
                al.entity_type,
                al.entity_id,
                al.description,
                al.old_value,
                al.new_value,
                al.ip_address,
                al.timestamp AS created_at
              FROM activity_logs al
              LEFT JOIN users u ON al.user_id = u.id
              WHERE 1=1";

    $params = [];
    $types  = '';

    if ($user_id !== null) {
        $query   .= " AND al.user_id = ?";
        $params[] = $user_id;
        $types   .= 'i';
    }
    if ($activity_type !== null) {
        $query   .= " AND al.activity_type = ?";
        $params[] = $activity_type;
        $types   .= 's';
    }
    if ($entity_type !== null) {
        $query   .= " AND al.entity_type = ?";
        $params[] = $entity_type;
        $types   .= 's';
    }
    if ($start_date !== null) {
        $query   .= " AND DATE(al.timestamp) >= ?";
        $params[] = $start_date;
        $types   .= 's';
    }
    if ($end_date !== null) {
        $query   .= " AND DATE(al.timestamp) <= ?";
        $params[] = $end_date;
        $types   .= 's';
    }
    if ($search !== null && $search !== '') {
        $query   .= " AND (al.description LIKE ? OR al.activity_type LIKE ? OR u.full_name LIKE ?)";
        $like     = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types   .= 'sss';
    }

    $query   .= " ORDER BY al.timestamp DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types   .= 'ii';

    $stmt = $db->prepare($query);
    if (!$stmt) return [];

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $logs   = [];

    while ($row = $result->fetch_assoc()) {
        $row['description'] = formatActivityDescription($row['activity_type'], $row['description']);
        $row['before_values'] = $row['old_value'] ? json_decode($row['old_value'], true) : [];
        $row['after_values']  = $row['new_value'] ? json_decode($row['new_value'],  true) : [];
        $logs[] = $row;
    }

    $stmt->close();
    return $logs;
}

function getActivityLogsCount(
    $db,
    ?int    $user_id       = null,
    ?string $activity_type = null,
    ?string $entity_type   = null,
    ?string $start_date    = null,
    ?string $end_date      = null,
    ?string $search        = null
): int {
    $query  = "SELECT COUNT(*) AS total FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id WHERE 1=1";
    $params = [];
    $types  = '';

    if ($user_id !== null) {
        $query   .= " AND al.user_id = ?";
        $params[] = $user_id;
        $types   .= 'i';
    }
    if ($activity_type !== null) {
        $query   .= " AND al.activity_type = ?";
        $params[] = $activity_type;
        $types   .= 's';
    }
    if ($entity_type !== null) {
        $query   .= " AND al.entity_type = ?";
        $params[] = $entity_type;
        $types   .= 's';
    }
    if ($start_date !== null) {
        $query   .= " AND DATE(al.timestamp) >= ?";
        $params[] = $start_date;
        $types   .= 's';
    }
    if ($end_date !== null) {
        $query   .= " AND DATE(al.timestamp) <= ?";
        $params[] = $end_date;
        $types   .= 's';
    }
    if ($search !== null && $search !== '') {
        $query   .= " AND (al.description LIKE ? OR al.activity_type LIKE ? OR u.full_name LIKE ?)";
        $like     = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types   .= 'sss';
    }

    $stmt = $db->prepare($query);
    if (!$stmt) return 0;

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) ($row['total'] ?? 0);
}

function getActivityLabel(string $activityType, ?string $description = null): string
{
    if ($activityType === 'ADMIN_ACTION' && $description !== null
        && strpos($description, 'Updated permissions for role') === 0) {
        return 'Permission Change';
    }

    $labels = [
        'LOGIN' => 'User Login',
        'LOGIN_FAILED' => 'Failed Login Attempt',
        'LOGOUT' => 'User Logout',
        'AUTH_DENIED' => 'Authentication Denied',
        'PERMISSION_DENIED' => 'Permission Denied',
        'API_SUSPICIOUS' => 'Suspicious API Activity',
        'DATA_EXPORT' => 'Data Export',
        'ADMIN_ACTION' => 'Admin Action',
        'CONFIG_CHANGE' => 'Configuration Change',
        'ROLE_CHANGE' => 'Role Change',
        'ROLE_PERMISSION_CHANGE' => 'Permission Change',
        'STAFF_ADD' => 'Add Staff',
        'STAFF_EDIT' => 'Edit Staff',
        'STAFF_DELETE' => 'Delete Staff',
        'STAFF_ACTIVATE' => 'Activate Staff',
        'STAFF_DEACTIVATE' => 'Deactivate Staff',
        'PASSWORD_RESET' => 'Password Reset',
        'OFFICE_ASSIGN' => 'Office Assignment',
        'SERVICE_ADD' => 'Add Service',
        'SERVICE_EDIT' => 'Edit Service',
        'SERVICE_DELETE' => 'Delete Service',
        'SERVICE_TOGGLE_STATUS' => 'Toggle Service Status',
        'OFFICE_QUEUE_MODE_TOGGLE' => 'Toggle Queue Mode',
        'SETTINGS_SCHEDULE_UPDATE' => 'Update Office Schedule',
        'SETTINGS_QUEUE_UPDATE' => 'Update Queue Configuration',
        'SETTINGS_KIOSK_UPDATE' => 'Update Kiosk Settings',
        'PASSWORD_CHANGE' => 'Password Changed',
        'PROFILE_UPDATE' => 'Profile Updated',
        'STAFF_RESTORE' => 'Restore Staff',
        'SERVICE_RESTORE' => 'Restore Service',
    ];

    return $labels[$activityType] ?? ucwords(str_replace('_', ' ', strtolower($activityType)));
}

function formatActivityDescription(?string $activityType, ?string $description): ?string
{
    if ($description === null || !in_array($activityType, ['ADMIN_ACTION', 'ROLE_PERMISSION_CHANGE'], true)) {
        return $description;
    }

    return str_replace('_', ' ', $description);
}

function getActivitySummary($db, ?string $start_date = null, ?string $end_date = null): array
{
    $query  = "SELECT activity_type, COUNT(*) AS count FROM activity_logs WHERE 1=1";
    $params = [];
    $types  = '';

    if ($start_date !== null) {
        $query   .= " AND DATE(timestamp) >= ?";
        $params[] = $start_date;
        $types   .= 's';
    }
    if ($end_date !== null) {
        $query   .= " AND DATE(timestamp) <= ?";
        $params[] = $end_date;
        $types   .= 's';
    }

    $query .= " GROUP BY activity_type ORDER BY count DESC";

    $stmt = $db->prepare($query);
    if (!$stmt) return [];

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result  = $stmt->get_result();
    $summary = [];
    while ($row = $result->fetch_assoc()) {
        $summary[] = $row;
    }

    $stmt->close();
    return $summary;
}

function formatActivityLog(array $log): array
{
    return [
        'id'             => $log['id'],
        'user_name'      => $log['user_name'] ?? 'Anonymous',
        'activity_type'  => $log['activity_type'],
        'activity_label' => getActivityLabel($log['activity_type'], $log['description'] ?? null),
        'entity_type'    => $log['entity_type'],
        'entity_id'      => $log['entity_id'],
        'description'    => $log['description'],
        'before_values'  => $log['before_values'],
        'after_values'   => $log['after_values'],
        'ip_address'     => $log['ip_address'],
        'created_at'     => $log['created_at'],
        'formatted_time' => date('M d, Y H:i:s', strtotime($log['created_at'])),
    ];
}
