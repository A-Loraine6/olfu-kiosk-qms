<?php
class TicketGenerator
{
    private Database $db;

    private const CUTOFF_SAFETY_BUFFER_MINUTES = 5;
    private const DEFAULT_AVG_SERVICE_SECONDS = 300;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Runs every gate a ticket must pass (closed, hard cutoff, queue full, soft cutoff-risk)
     * without inserting anything. Used by both the preview screen and actual issuance so the
     * person is blocked before they reach the confirm screen, not after they tap Confirm.
     */
    private function checkAvailability(array $office, int $office_id): array
    {
        $todayDate = date('Y-m-d');
        $nowTs     = time();

        $openTs  = $this->toTimestamp($todayDate, $office['open_time']  ?? null) ?? strtotime($todayDate . ' 00:00:00');
        $closeTs = $this->toTimestamp($todayDate, $office['close_time'] ?? null) ?? strtotime($todayDate . ' 23:59:59');

        if ($office['auto_close'] && ($nowTs < $openTs || $nowTs >= $closeTs)) {
            return ['ok' => false, 'code' => 'office_closed', 'message' => 'This office is currently closed.'];
        }

        $breakStartTs = $this->toTimestamp($todayDate, $office['break_start'] ?? null);
        $breakEndTs   = $this->toTimestamp($todayDate, $office['break_end']   ?? null);
        $onBreak      = $breakStartTs !== null && $breakEndTs !== null
            && $nowTs >= $breakStartTs && $nowTs < $breakEndTs;

        $cutoffTs = $this->toTimestamp($todayDate, $office['queue_cutoff'] ?? null);
        if ($cutoffTs !== null && $nowTs >= $cutoffTs) {
            return [
                'ok' => false, 'code' => 'cutoff',
                'message' => 'Queue is closed — cut-off time (' . date('g:i A', $cutoffTs) . ') has passed.',
            ];
        }

        $dailyCount = $this->countAllToday($office_id, $todayDate);
        if ($office['max_queue_limit'] > 0 && $dailyCount >= $office['max_queue_limit']) {
            return [
                'ok' => false, 'code' => 'queue_full',
                'message' => 'Maximum tickets for today have already been issued. Please come back tomorrow.',
            ];
        }

        $peopleAhead    = $this->countWaiting($office_id, $todayDate);
        $avgServiceSecs = $this->getAverageServiceSeconds($office_id, $todayDate);
        $anchorTs       = $onBreak ? $breakEndTs : $nowTs;
        $estWaitSecs    = $peopleAhead * $avgServiceSecs;
        $estReadyTs     = $anchorTs + $estWaitSecs;
        $estWaitMins    = (int) ceil(max(0, $estReadyTs - $nowTs) / 60);

        $boundaryTs = $cutoffTs ?? $closeTs;
        $bufferSecs = self::CUTOFF_SAFETY_BUFFER_MINUTES * 60;

        if ($boundaryTs !== null && ($estReadyTs + $bufferSecs) >= $boundaryTs) {
            return [
                'ok' => false, 'code' => 'cutoff_risk',
            'message' => 'This ticket cannot be accommodated before the office cut-off time.',
            ];
        }

        return [
            'ok' => true,
            'on_break' => $onBreak,
            'break_end_ts' => $breakEndTs,
            'people_ahead' => $peopleAhead,
            'est_wait_mins' => $estWaitMins,
        ];
    }

    public function issueTicket(int $office_id, string $service_name = '', bool $is_priority = false): array
    {
        $office = $this->fetchOffice($office_id);
        if (!$office) {
            return ['success' => false, 'message' => 'Office not found'];
        }

        $avail = $this->checkAvailability($office, $office_id);
        if (!$avail['ok']) {
            return ['success' => false, 'code' => $avail['code'], 'message' => $avail['message']];
        }

        $transaction_type = ($service_name !== '') ? $service_name : null;

        $window_id = null;
        if ($transaction_type !== null) {
            if ($this->resolveServiceId($office_id, $transaction_type) === null) {
                return ['success' => false, 'message' => 'Selected service is not available for this office.'];
            }
            $window_id = $this->getServiceWindowIdIfSingle($office_id, $transaction_type);
        }

        $lockName = "ticket_gen_{$office_id}";

        $lockStmt = $this->db->prepare("SELECT GET_LOCK(?, 5) AS acquired");
        if (!$lockStmt) {
            return ['success' => false, 'message' => 'Database prepare failed for lock'];
        }
        $lockStmt->bind_param('s', $lockName);
        $lockStmt->execute();
        $lockRow = $lockStmt->get_result()->fetch_assoc();
        $lockStmt->close();

        if (!$lockRow || !$lockRow['acquired']) {
            return ['success' => false, 'message' => 'Server busy — please try again'];
        }

        try {
            // Re-check inside the lock in case state changed between the outer check and acquiring it.
            $recheck = $this->checkAvailability($office, $office_id);
            if (!$recheck['ok']) {
                return ['success' => false, 'code' => $recheck['code'], 'message' => $recheck['message']];
            }

            $ticketNumber  = $this->generateTicketNumber($office, $is_priority);
            $trackingToken = $this->generateTrackingToken();
            $isPriorityInt = $is_priority ? 1 : 0;

            $insert = $this->db->prepare("
                INSERT INTO tickets
                    (office_id, is_priority, transaction_type, ticket_number, tracking_token, window_id, status, created_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, 'waiting', NOW())
            ");
            $insert->bind_param('iisssi', $office_id, $isPriorityInt, $transaction_type, $ticketNumber, $trackingToken, $window_id);

            if (!$insert->execute()) {
                throw new RuntimeException('Failed to insert ticket: ' . $insert->error);
            }
            $insert->close();
        } finally {
            $unlockStmt = $this->db->prepare("SELECT RELEASE_LOCK(?)");
            $unlockStmt->bind_param('s', $lockName);
            $unlockStmt->execute();
            $unlockStmt->close();
        }

        return [
            'success'        => true,
            'ticket_number'  => $ticketNumber,
            'tracking_token' => $trackingToken,
            'people_ahead'   => $recheck['people_ahead'],
            'waiting_count'  => $recheck['people_ahead'] + 1,
            'window_id'      => $window_id,
            'window_number'  => $window_id,
            'is_priority'    => $is_priority,
            'est_wait_mins'  => $recheck['est_wait_mins'],
            'on_break'       => $recheck['on_break'],
            'note'           => $recheck['on_break']
                ? 'Office is currently on break — service resumes at ' . date('g:i A', $recheck['break_end_ts']) . '. Your number is reserved.'
                : null,
        ];
    }

    /** Preview screen now runs the same gate the real issuance does, so blocking happens before confirm. */
    public function previewNextTicketNumber(int $office_id, bool $is_priority = false): array
    {
        $office = $this->fetchOffice($office_id);
        if (!$office) {
            return ['success' => false, 'code' => 'office_closed', 'message' => 'Office not found'];
        }

        $avail = $this->checkAvailability($office, $office_id);
        if (!$avail['ok']) {
            return ['success' => false, 'code' => $avail['code'], 'message' => $avail['message']];
        }

        return [
            'success'        => true,
            'preview_ticket' => $this->generateTicketNumber($office, $is_priority),
            'on_break'       => $avail['on_break'],
            'est_wait_mins'  => $avail['est_wait_mins'],
            'people_ahead'   => $avail['people_ahead'],
        ];
    }

    private function generateTrackingToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function toTimestamp(string $dateStr, ?string $time): ?int
    {
        if (empty($time)) {
            return null;
        }
        $ts = strtotime($dateStr . ' ' . substr($time, 0, 8));
        return $ts !== false ? $ts : null;
    }

    private function getAverageServiceSeconds(int $office_id, string $today): float
    {
        $stmt = $this->db->prepare("
            SELECT AVG(TIMESTAMPDIFF(SECOND, served_at, completed_at)) AS avg_secs
            FROM tickets
            WHERE office_id = ?
              AND status = 'completed'
              AND DATE(completed_at) = ?
              AND served_at IS NOT NULL
              AND completed_at IS NOT NULL
        ");
        $stmt->bind_param('is', $office_id, $today);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['avg_secs']) && $row['avg_secs'] !== null
            ? (float) $row['avg_secs']
            : self::DEFAULT_AVG_SERVICE_SECONDS;
    }

    private function fetchOffice(int $office_id): array|false
    {
        $stmt = $this->db->prepare("
            SELECT id, name, code, queue_prefix, priority_prefix, queue_mode,
                   max_queue_limit, queue_start_num, daily_reset,
                   auto_close, open_time, close_time,
                   break_start, break_end, queue_cutoff
            FROM   offices
            WHERE  id = ?
            LIMIT  1
        ");
        $stmt->bind_param('i', $office_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: false;
    }

    private function resolveServiceId(int $office_id, string $service_name): ?int
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM   transaction_types
            WHERE  office_id  = ?
              AND  name       = ?
              AND  is_active  = 1
              AND  is_deleted = 0
            LIMIT  1
        ");
        $stmt->bind_param('is', $office_id, $service_name);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? (int)$row['id'] : null;
    }

    private function getServiceWindowIdIfSingle(int $office_id, string $service_name): ?int
    {
        $service_id = $this->resolveServiceId($office_id, $service_name);
        if (!$service_id) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT window_number
            FROM   service_windows
            WHERE  service_id = ? AND office_id = ?
        ");
        $stmt->bind_param('ii', $service_id, $office_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return count($rows) === 1 ? (int)$rows[0]['window_number'] : null;
    }

    private function generateTicketNumber(array $office, bool $is_priority = false): string
    {
        $basePrefix = $office['queue_prefix'] ?: strtoupper(substr($office['code'], 0, 3));

        $prefix = $is_priority
            ? ($office['priority_prefix'] ?: ($basePrefix . '-P'))
            : $basePrefix;

        $today = date('Y-m-d');
        $isPriorityInt = $is_priority ? 1 : 0;

        if ($office['daily_reset']) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS cnt FROM tickets WHERE office_id = ? AND is_priority = ? AND DATE(created_at) = ?"
            );
            $stmt->bind_param('iis', $office['id'], $isPriorityInt, $today);
        } else {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS cnt FROM tickets WHERE office_id = ? AND is_priority = ?"
            );
            $stmt->bind_param('ii', $office['id'], $isPriorityInt);
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $startNum = max(1, (int)($office['queue_start_num'] ?? 1));
        $next     = ((int)($row['cnt'] ?? 0)) + $startNum;

        return sprintf('%s-%d', $prefix, $next);
    }

    private function countWaiting(int $office_id, string $today, ?bool $is_priority = null): int
    {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM   tickets
            WHERE  office_id        = ?
              AND  status IN ('waiting', 'serving')
              AND  DATE(created_at) = ?
        ";

        if ($is_priority !== null) {
            $sql .= " AND is_priority = ?";
            $stmt = $this->db->prepare($sql);
            $isPriorityInt = $is_priority ? 1 : 0;
            $stmt->bind_param('isi', $office_id, $today, $isPriorityInt);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('is', $office_id, $today);
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['cnt'] ?? 0);
    }

    private function countAllToday(int $office_id, string $today): int
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) AS cnt FROM tickets
        WHERE office_id = ? AND DATE(created_at) = ?
    ");
        $stmt->bind_param('is', $office_id, $today);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['cnt'] ?? 0);
    }

    public function getQueueStatus(int $office_id): array
    {
        $office = $this->fetchOffice($office_id);
        if (!$office) {
            return ['is_full' => false];
        }

        $today      = date('Y-m-d');
        $dailyCount = $this->countAllToday($office_id, $today);

        return [
            'is_full'          => $office['max_queue_limit'] > 0 && $dailyCount >= $office['max_queue_limit'],
            'count'            => $dailyCount,
            'limit'            => $office['max_queue_limit'],
            'waiting_regular'  => $this->countWaiting($office_id, $today, false),
            'waiting_priority' => $this->countWaiting($office_id, $today, true),
        ];
    }
}