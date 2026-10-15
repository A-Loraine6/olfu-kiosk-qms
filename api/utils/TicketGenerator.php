<?php
require_once __DIR__ . '/QueueEligibility.php';
require_once __DIR__ . '/TicketEvents.php';

class TicketGenerator
{
    private Database $db;

    private const CUTOFF_SAFETY_BUFFER_MINUTES = 5;
    private const DEFAULT_AVG_SERVICE_SECONDS = 300;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    private function checkAvailability(
        array $office,
        int $office_id,
        ?string $service_name,
        bool $is_priority,
        ?string $student_level = null
    ): array
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

        $serviceBased = ($office['queue_mode'] ?? 'unified') === 'service-based';
        $levelBased = ($office['queue_mode'] ?? 'unified') === 'level-based';
        $laneService = $serviceBased ? $service_name : null;
        $servingWindows = $serviceBased && $laneService !== null
            ? $this->getServiceWindowNumbers($office_id, $laneService, (int) $office['window_count'])
            : ($levelBased && $student_level !== null
                ? $this->getLevelWindowNumbers($office_id, $student_level)
                : range(1, max(1, (int) $office['window_count'])));
        if ($levelBased && !$servingWindows) {
            return [
                'ok' => false,
                'code' => 'no_eligible_windows',
                'message' => 'No windows are assigned to serve this student level.',
            ];
        }
        $peopleAhead = $this->countWaiting(
            $office_id,
            $todayDate,
            null,
            $laneService,
            $is_priority,
            ($serviceBased || $levelBased) ? $servingWindows : [],
            $levelBased ? $student_level : null
        );
        $avgServiceSecs = $this->getAverageServiceSeconds(
            $office_id,
            $todayDate,
            $laneService,
            $levelBased ? $student_level : null
        );
        $anchorTs       = $onBreak ? $breakEndTs : $nowTs;
        $estWaitSecs    = $peopleAhead * $avgServiceSecs / max(1, count($servingWindows));
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

    public function issueTicket(
        int $office_id,
        string $service_name = '',
        bool $is_priority = false,
        ?string $student_level = null
    ): array
    {
        $office = $this->fetchOffice($office_id);
        if (!$office) {
            return ['success' => false, 'message' => 'Office not found'];
        }

        $transaction_type = ($service_name !== '') ? $service_name : null;
        $serviceBased = ($office['queue_mode'] ?? 'unified') === 'service-based';
        $levelBased = ($office['queue_mode'] ?? 'unified') === 'level-based';
        if ($levelBased) {
            if (!in_array($student_level, ['shs', 'college'], true)) {
                return ['success' => false, 'message' => 'Please select SHS or College.'];
            }
            $transaction_type = null;
        } else {
            $student_level = null;
        }
        if ($serviceBased && $transaction_type === null) {
            return ['success' => false, 'message' => 'Please select a service for this department.'];
        }
        if ($transaction_type !== null && $this->resolveServiceId($office_id, $transaction_type) === null) {
            return ['success' => false, 'message' => 'Selected service is not available for this office.'];
        }

        $avail = $this->checkAvailability($office, $office_id, $transaction_type, $is_priority, $student_level);
        if (!$avail['ok']) {
            return ['success' => false, 'code' => $avail['code'], 'message' => $avail['message']];
        }

        $window_id = null;
        if ($transaction_type !== null) {
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

        $connection = $this->db->getConnection();
        $transactionStarted = false;
        try {
            $office = $this->fetchOffice($office_id);
            if (!$office) {
                return ['success' => false, 'message' => 'Office not found.'];
            }
            $serviceBased = ($office['queue_mode'] ?? 'unified') === 'service-based';
            $levelBased = ($office['queue_mode'] ?? 'unified') === 'level-based';
            if ($levelBased && !in_array($student_level, ['shs', 'college'], true)) {
                return ['success' => false, 'message' => 'Please select SHS or College.'];
            }
            if ($serviceBased && $transaction_type === null) {
                return ['success' => false, 'message' => 'Please select a service for this department.'];
            }
            if (!$levelBased) {
                $student_level = null;
            } else {
                $transaction_type = null;
            }
            if ($transaction_type !== null && $this->resolveServiceId($office_id, $transaction_type) === null) {
                return ['success' => false, 'message' => 'Selected service is not available for this office.'];
            }
            $window_id = $transaction_type !== null
                ? $this->getServiceWindowIdIfSingle($office_id, $transaction_type)
                : null;

            if (!$connection->begin_transaction()) {
                throw new RuntimeException('Could not start ticket issuance transaction.');
            }
            $transactionStarted = true;

            $recheck = $this->checkAvailability($office, $office_id, $transaction_type, $is_priority, $student_level);
            if (!$recheck['ok']) {
                $connection->rollback();
                $transactionStarted = false;
                return ['success' => false, 'code' => $recheck['code'], 'message' => $recheck['message']];
            }

            $ticketNumber  = $this->generateTicketNumber($office, $is_priority);
            $trackingToken = $this->generateTrackingToken();
            $isPriorityInt = $is_priority ? 1 : 0;

            $insert = $this->db->prepare("
                INSERT INTO tickets
                    (office_id, is_priority, transaction_type, student_level, ticket_number, tracking_token, window_id, status, created_at, original_created_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, 'waiting', NOW(), NOW())
            ");
            $insert->bind_param('iissssi', $office_id, $isPriorityInt, $transaction_type, $student_level, $ticketNumber, $trackingToken, $window_id);

            if (!$insert->execute()) {
                throw new RuntimeException('Failed to insert ticket: ' . $insert->error);
            }
            $ticketId = (int) $insert->insert_id;
            $insert->close();

            recordTicketEvent(
                $this->db,
                $ticketId,
                'ticket_issued',
                null,
                'waiting',
                null,
                $office_id,
                $window_id,
                [
                    'ticket_number' => $ticketNumber,
                    'transaction_type' => $transaction_type,
                    'student_level' => $student_level,
                    'is_priority' => $is_priority,
                ]
            );
            if (!$connection->commit()) {
                throw new RuntimeException('Could not commit ticket issuance.');
            }
            $transactionStarted = false;
        } catch (Throwable $error) {
            if ($transactionStarted) {
                $connection->rollback();
            }
            throw $error;
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
            'student_level'  => $student_level,
            'is_priority'    => $is_priority,
            'est_wait_mins'  => $recheck['est_wait_mins'],
            'on_break'       => $recheck['on_break'],
            'note'           => $recheck['on_break']
                ? 'Office is currently on break — service resumes at ' . date('g:i A', $recheck['break_end_ts']) . '. Your number is reserved.'
                : null,
        ];
    }

    public function previewNextTicketNumber(
        int $office_id,
        bool $is_priority = false,
        string $service_name = '',
        ?string $student_level = null
    ): array
    {
        $office = $this->fetchOffice($office_id);
        if (!$office) {
            return ['success' => false, 'code' => 'office_closed', 'message' => 'Office not found'];
        }

        $transaction_type = $service_name !== '' ? $service_name : null;
        $levelBased = ($office['queue_mode'] ?? 'unified') === 'level-based';
        if ($levelBased) {
            if (!in_array($student_level, ['shs', 'college'], true)) {
                return ['success' => false, 'message' => 'Please select SHS or College.'];
            }
            $transaction_type = null;
        } else {
            $student_level = null;
        }
        if (($office['queue_mode'] ?? 'unified') === 'service-based' && $transaction_type === null) {
            return ['success' => false, 'message' => 'Please select a service for this department.'];
        }
        if ($transaction_type !== null && $this->resolveServiceId($office_id, $transaction_type) === null) {
            return ['success' => false, 'message' => 'Selected service is not available for this office.'];
        }

        $avail = $this->checkAvailability($office, $office_id, $transaction_type, $is_priority, $student_level);
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

    private function getAverageServiceSeconds(
        int $office_id,
        string $today,
        ?string $service_name,
        ?string $student_level = null
    ): float
    {
        $sql = "
            SELECT AVG(
                COALESCE(time_spent, 0) +
                TIMESTAMPDIFF(SECOND, served_at, completed_at)
            ) AS avg_secs
            FROM tickets
            WHERE office_id = ?
              AND status = 'completed'
              AND DATE(completed_at) = ?
              AND served_at IS NOT NULL
              AND completed_at IS NOT NULL
        ";
        if ($service_name !== null) {
            $sql .= ' AND transaction_type = ?';
            $types = 'iss';
            $params = [$office_id, $today, $service_name];
        } else {
            $types = 'is';
            $params = [$office_id, $today];
        }
        if ($student_level !== null) {
            $sql .= ' AND student_level = ?';
            $types .= 's';
            $params[] = $student_level;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
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
                   window_count,
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

    private function countWaiting(
        int $office_id,
        string $today,
        ?bool $is_priority = null,
        ?string $service_name = null,
        ?bool $new_ticket_priority = null,
        array $eligible_windows = [],
        ?string $student_level = null
    ): int
    {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM   tickets
            WHERE  office_id        = ?
              AND  status IN ('waiting', 'serving')
              AND  DATE(created_at) = ?
        ";

        $types = 'is';
        $params = [$office_id, $today];
        if ($is_priority !== null) {
            $sql .= ' AND is_priority = ?';
            $types .= 'i';
            $params[] = $is_priority ? 1 : 0;
        }
        if ($service_name !== null) {
            $sql .= ' AND transaction_type = ?';
            $types .= 's';
            $params[] = $service_name;
        }
        if ($student_level !== null) {
            $sql .= ' AND student_level = ?';
            $types .= 's';
            $params[] = $student_level;
        }
        if ($new_ticket_priority !== null) {
            $priority = $new_ticket_priority ? 1 : 0;
            $sql .= " AND (
                is_priority > ?
                OR (is_priority = ? AND COALESCE(queue_order_at, created_at) <= NOW())
            )";
            $types .= 'ii';
            $params[] = $priority;
            $params[] = $priority;
        }
        if ($eligible_windows) {
            $sql .= ' AND ' . queueTicketEligibleOnAnyWindowSql('tickets', $eligible_windows);
            foreach ($eligible_windows as $windowNumber) {
                $types .= 'iii';
                $params[] = $windowNumber;
                $params[] = $windowNumber;
                $params[] = $windowNumber;
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['cnt'] ?? 0);
    }

    private function getServiceWindowNumbers(int $office_id, string $service_name, int $office_window_count): array
    {
        $serviceId = $this->resolveServiceId($office_id, $service_name);
        if ($serviceId === null) {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT DISTINCT window_number
            FROM service_windows
            WHERE service_id = ? AND office_id = ?
            ORDER BY window_number
        ");
        $stmt->bind_param('ii', $serviceId, $office_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (!$rows) {
            return range(1, max(1, $office_window_count));
        }

        return array_map(static fn(array $row): int => (int) $row['window_number'], $rows);
    }

    private function getLevelWindowNumbers(int $office_id, string $student_level): array
    {
        $stmt = $this->db->prepare("
            SELECT window_number
            FROM window_levels
            WHERE office_id = ?
              AND (student_level = ? OR student_level = 'both')
            ORDER BY window_number
        ");
        $stmt->bind_param('is', $office_id, $student_level);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return array_map(static fn(array $row): int => (int) $row['window_number'], $rows);
    }

    public function withNextTicketNumber(int $office_id, bool $is_priority, callable $callback): mixed
    {
        $office = $this->fetchOffice($office_id);
        if (!$office) {
            throw new RuntimeException('Office not found');
        }

        $lockName = "ticket_gen_{$office_id}";
        $lockStmt = $this->db->prepare("SELECT GET_LOCK(?, 5) AS acquired");
        if (!$lockStmt) {
            throw new RuntimeException('Database prepare failed for lock');
        }
        $lockStmt->bind_param('s', $lockName);
        $lockStmt->execute();
        $lockRow = $lockStmt->get_result()->fetch_assoc();
        $lockStmt->close();

        if (!$lockRow || !$lockRow['acquired']) {
            throw new RuntimeException('Server busy — please try again');
        }

        try {
            return $callback($this->generateTicketNumber($office, $is_priority));
        } finally {
            $unlockStmt = $this->db->prepare("SELECT RELEASE_LOCK(?)");
            $unlockStmt->bind_param('s', $lockName);
            $unlockStmt->execute();
            $unlockStmt->close();
        }
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