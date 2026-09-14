<?php
require_once __DIR__ . '/activity_logger.php';
require_once __DIR__ . '/../includes/request_validation.php';

function settingsResponse(bool $success, string $message): array
{
    return ['success' => $success, 'message' => $message];
}

function diffSnapshot(array $before, array $after): array
{
    $changedBefore = [];
    $changedAfter  = [];
    foreach ($after as $key => $newVal) {
        $oldVal = $before[$key] ?? null;
        if ($oldVal != $newVal) {
            $changedBefore[$key] = $oldVal;
            $changedAfter[$key]  = $newVal;
        }
    }
    return [$changedBefore, $changedAfter];
}

function normaliseTime(string $t): string
{
    $t = trim($t);
    if ($t === '') return '';
    if (preg_match('/^(\d{2}:\d{2})(:\d{2})?$/', $t, $m)) return $m[1];
    return '';
}

function isValidTime(string $t): bool
{
    return validTime($t) !== null;
}

function timeAfter(string $earlier, string $later): bool
{
    return $earlier < $later;
}

function getAllOffices(mysqli $conn): array
{
    $sql = "SELECT id, name, code, description, window_count, queue_mode,
                   open_time, close_time, break_start, break_end,
                   queue_cutoff, queue_prefix, queue_start_num,
                   daily_reset, max_queue_limit, auto_close
            FROM offices ORDER BY name ASC";

    $res = $conn->query($sql);
    if (!$res) return [];

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $row['id']              = (int)  $row['id'];
        $row['window_count']    = (int)  $row['window_count'];
        $row['queue_start_num'] = (int)  $row['queue_start_num'];
        $row['max_queue_limit'] = (int)  $row['max_queue_limit'];
        $row['daily_reset']     = (bool) $row['daily_reset'];
        $row['auto_close']      = (bool) $row['auto_close'];
        foreach (['open_time', 'close_time', 'break_start', 'break_end', 'queue_cutoff'] as $col) {
            $row[$col] = $row[$col] ? substr($row[$col], 0, 5) : '';
        }
        $rows[] = $row;
    }
    return $rows;
}

function getOfficeById(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare(
        "SELECT id, name, code, description, window_count, queue_mode,
                open_time, close_time, break_start, break_end,
                queue_cutoff, queue_prefix, queue_start_num,
                daily_reset, max_queue_limit, auto_close
         FROM offices WHERE id = ? LIMIT 1"
    );
    if (!$stmt) return null;

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) return null;

    $row['id']              = (int)  $row['id'];
    $row['window_count']    = (int)  $row['window_count'];
    $row['queue_start_num'] = (int)  $row['queue_start_num'];
    $row['max_queue_limit'] = (int)  $row['max_queue_limit'];
    $row['daily_reset']     = (bool) $row['daily_reset'];
    $row['auto_close']      = (bool) $row['auto_close'];
    foreach (['open_time', 'close_time', 'break_start', 'break_end', 'queue_cutoff'] as $col) {
        $row[$col] = $row[$col] ? substr($row[$col], 0, 5) : '';
    }
    return $row;
}

function saveOfficeSchedule(
    mysqli $conn,
    int    $officeId,
    string $open,
    string $close,
    string $breakStart,
    string $breakEnd,
    string $cutoff
): array {
    if ($officeId <= 0) return settingsResponse(false, 'Invalid office.');

    $office = getOfficeById($conn, $officeId);
    if (!$office) return settingsResponse(false, 'Office not found.');

    $open       = normaliseTime($open);
    $close      = normaliseTime($close);
    $breakStart = normaliseTime($breakStart);
    $breakEnd   = normaliseTime($breakEnd);
    $cutoff     = normaliseTime($cutoff);

    if (!isValidTime($open))  return settingsResponse(false, 'Opening time is required and must be valid.');
    if (!isValidTime($close)) return settingsResponse(false, 'Closing time is required and must be valid.');
    if (!timeAfter($open, $close))
        return settingsResponse(false, 'Closing time must be after opening time.');

    if ($breakStart !== '' || $breakEnd !== '') {
        if (!isValidTime($breakStart)) return settingsResponse(false, 'Break start time is invalid.');
        if (!isValidTime($breakEnd))   return settingsResponse(false, 'Break end time is invalid.');
        if (!timeAfter($breakStart, $breakEnd))
            return settingsResponse(false, 'Break end must be after break start.');
        if ($breakStart < $open || $breakEnd > $close)
            return settingsResponse(false, 'Break time must fall within office hours.');
    }

    if ($cutoff !== '') {
        if (!isValidTime($cutoff))       return settingsResponse(false, 'Cut-off time is invalid.');
        if (!timeAfter($open, $cutoff))  return settingsResponse(false, 'Cut-off must be after opening time.');
        if (!timeAfter($cutoff, $close)) return settingsResponse(false, 'Cut-off must be before closing time.');
    }

    $bsNull = $breakStart !== '' ? $breakStart : null;
    $beNull = $breakEnd   !== '' ? $breakEnd   : null;
    $coNull = $cutoff     !== '' ? $cutoff     : null;

    $stmt = $conn->prepare(
        "UPDATE offices
         SET open_time = ?, close_time = ?, break_start = ?, break_end = ?, queue_cutoff = ?
         WHERE id = ?"
    );
    if (!$stmt) return settingsResponse(false, 'Database error: ' . $conn->error);

    $stmt->bind_param('sssssi', $open, $close, $bsNull, $beNull, $coNull, $officeId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) return settingsResponse(false, 'Failed to save schedule. Please try again.');

    [$before, $after] = diffSnapshot(
        ['open_time' => $office['open_time'], 'close_time' => $office['close_time'], 'break_start' => $office['break_start'], 'break_end' => $office['break_end'], 'queue_cutoff' => $office['queue_cutoff']],
        ['open_time' => $open,                'close_time' => $close,                'break_start' => $bsNull ?? '',           'break_end' => $beNull ?? '',           'queue_cutoff' => $coNull ?? '']
    );

    if (!empty($before)) {
        logActivity(
            $conn,
            'SETTINGS_SCHEDULE_UPDATE',
            'OFFICE',
            $officeId,
            "Updated office schedule for \"{$office['name']}\"",
            $before,
            $after
        );
    }

    return settingsResponse(true, 'Schedule settings saved successfully.');
}

function saveQueueConfig(
    mysqli $conn,
    int    $officeId,
    string $prefix,
    int    $startNum,
    bool   $dailyReset,
    int    $maxLimit,
    bool   $autoClose
): array {
    if ($officeId <= 0) return settingsResponse(false, 'Invalid office.');

    $office = getOfficeById($conn, $officeId);
    if (!$office) return settingsResponse(false, 'Office not found.');

    $prefix = strtoupper(trim(strip_tags($prefix)));

    if ($prefix !== '' && validQueuePrefix($prefix) === null)
        return settingsResponse(false, 'Prefix must be 1–10 uppercase letters, digits, or hyphens.');
    if ($startNum < 1 || $startNum > 9999)
        return settingsResponse(false, 'Starting number must be between 1 and 9999.');
    if ($maxLimit < 0 || $maxLimit > 9999)
        return settingsResponse(false, 'Max limit must be between 0 (unlimited) and 9999.');

    $dailyResetInt = $dailyReset ? 1 : 0;
    $autoCloseInt  = $autoClose  ? 1 : 0;

    $stmt = $conn->prepare(
        "UPDATE offices
         SET queue_prefix = ?, queue_start_num = ?, daily_reset = ?, max_queue_limit = ?, auto_close = ?
         WHERE id = ?"
    );
    if (!$stmt) return settingsResponse(false, 'Database error: ' . $conn->error);

    $stmt->bind_param('siiiii', $prefix, $startNum, $dailyResetInt, $maxLimit, $autoCloseInt, $officeId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) return settingsResponse(false, 'Failed to save queue config. Please try again.');

    [$before, $after] = diffSnapshot(
        ['queue_prefix' => $office['queue_prefix'], 'queue_start_num' => $office['queue_start_num'], 'daily_reset' => $office['daily_reset'], 'max_queue_limit' => $office['max_queue_limit'], 'auto_close' => $office['auto_close']],
        ['queue_prefix' => $prefix,                 'queue_start_num' => $startNum,                  'daily_reset' => (bool) $dailyResetInt,   'max_queue_limit' => $maxLimit,                  'auto_close' => (bool) $autoCloseInt]
    );

    if (!empty($before)) {
        logActivity(
            $conn,
            'SETTINGS_QUEUE_UPDATE',
            'OFFICE',
            $officeId,
            "Updated queue configuration for \"{$office['name']}\"",
            $before,
            $after
        );
    }

    return settingsResponse(true, 'Queue configuration saved successfully.');
}

function getKioskSettings(mysqli $conn): array
{
    $res = $conn->query("SELECT kiosk_enabled, idle_timeout FROM kiosk_settings WHERE id = 1 LIMIT 1");
    if (!$res) return ['kiosk_enabled' => true, 'idle_timeout' => 60];

    $row = $res->fetch_assoc();
    if (!$row) return ['kiosk_enabled' => true, 'idle_timeout' => 60];

    return [
        'kiosk_enabled' => (bool) $row['kiosk_enabled'],
        'idle_timeout'  => (int)  $row['idle_timeout'],
    ];
}

function saveKioskSettings(mysqli $conn, bool $enabled, int $idleTimeout): array
{
    if ($idleTimeout < 10 || $idleTimeout > 600)
        return settingsResponse(false, 'Idle timeout must be between 10 and 600 seconds.');

    $snapshot   = getKioskSettings($conn);
    $enabledInt = $enabled ? 1 : 0;

    $stmt = $conn->prepare(
        "UPDATE kiosk_settings SET kiosk_enabled = ?, idle_timeout = ? WHERE id = 1"
    );
    if (!$stmt) return settingsResponse(false, 'Database error: ' . $conn->error);

    $stmt->bind_param('ii', $enabledInt, $idleTimeout);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) return settingsResponse(false, 'Failed to save kiosk settings. Please try again.');

    [$before, $after] = diffSnapshot(
        $snapshot,
        ['kiosk_enabled' => $enabled, 'idle_timeout' => $idleTimeout]
    );

    if (!empty($before)) {
        logActivity(
            $conn,
            'SETTINGS_KIOSK_UPDATE',
            'KIOSK',
            1,
            'Updated kiosk settings',
            $before,
            $after
        );
    }

    return settingsResponse(true, 'Kiosk settings saved successfully.');
}

function buildQueuePreview(string $prefix, int $start): string
{
    $num = str_pad((string) $start, 3, '0', STR_PAD_LEFT);
    return $prefix !== '' ? "{$prefix}-{$num}" : $num;
}
