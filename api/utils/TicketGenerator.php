<?php
class TicketGenerator
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function issueTicket(int $office_id, string $service_name = ''): array
    {
        $office = $this->fetchOffice($office_id);
        if (!$office) {
            return ['success' => false, 'message' => 'Office not found'];
        }

        $now   = date('H:i');
        $open  = substr($office['open_time']  ?? '00:00', 0, 5);
        $close = substr($office['close_time'] ?? '23:59', 0, 5);

        if ($office['auto_close'] && ($now < $open || $now >= $close)) {
            return ['success' => false, 'message' => 'This office is currently closed.'];
        }

        if (!empty($office['break_start']) && !empty($office['break_end'])) {
            $bs = substr($office['break_start'], 0, 5);
            $be = substr($office['break_end'],   0, 5);
            if ($now >= $bs && $now < $be) {
                return [
                    'success' => false,
                    'message' => "Office is on break from {$bs} to {$be}. Please return after the break.",
                ];
            }
        }

        if (!empty($office['queue_cutoff'])) {
            $cutoff = substr($office['queue_cutoff'], 0, 5);
            if ($now >= $cutoff) {
                return ['success' => false, 'message' => "Queue is closed — cut-off time ({$cutoff}) has passed."];
            }
        }

        $transaction_type = ($service_name !== '') ? $service_name : null;

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

        try {
            $today        = date('Y-m-d');
            $dailyCount   = $this->countAllToday($office_id, $today);
            $waitingCount = $this->countWaiting($office_id, $today);
            $peopleAhead  = $waitingCount;

            if ($office['max_queue_limit'] > 0 && $dailyCount >= $office['max_queue_limit']) {
                return [
                    'success' => false,
                    'code'    => 'queue_full',
                    'message' => 'Maximum tickets for today have already been issued. Please come back tomorrow.',
                ];
            }

            $ticketNumber  = $this->generateTicketNumber($office);
            $trackingToken = $this->generateTrackingToken();

            $insert = $this->db->prepare("
                INSERT INTO tickets
                    (office_id, transaction_type, ticket_number, tracking_token, window_id, status, created_at)
                VALUES
                    (?, ?, ?, ?, ?, 'waiting', NOW())
            ");
            $insert->bind_param('isssi', $office_id, $transaction_type, $ticketNumber, $trackingToken, $window_id);

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
            'people_ahead'   => $peopleAhead,
            'waiting_count'  => $waitingCount + 1,
            'window_id'      => $window_id,
        ];
    }

    public function previewNextTicketNumber(int $office_id): string|false
    {
        $office = $this->fetchOffice($office_id);
        if (!$office) {
            return false;
        }
        return $this->generateTicketNumber($office);
    }


    private function generateTrackingToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function fetchOffice(int $office_id): array|false
    {
        $stmt = $this->db->prepare("
            SELECT id, name, code, queue_prefix, queue_mode,
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

    private function generateTicketNumber(array $office): string
    {
        $prefix = $office['queue_prefix'] ?: strtoupper(substr($office['code'], 0, 3));
        $today  = date('Y-m-d');

        if ($office['daily_reset']) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS cnt FROM tickets WHERE office_id = ? AND DATE(created_at) = ?"
            );
            $stmt->bind_param('is', $office['id'], $today);
        } else {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS cnt FROM tickets WHERE office_id = ?"
            );
            $stmt->bind_param('i', $office['id']);
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $startNum = max(1, (int)($office['queue_start_num'] ?? 1));
        $next     = ((int)($row['cnt'] ?? 0)) + $startNum;

        return sprintf('%s-%03d', $prefix, $next);
    }

    private function countWaiting(int $office_id, string $today): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS cnt
            FROM   tickets
            WHERE  office_id        = ?
              AND  status IN ('waiting', 'serving')
              AND  DATE(created_at) = ?
        ");
        $stmt->bind_param('is', $office_id, $today);
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
        if ($office['max_queue_limit'] <= 0) {
            return ['is_full' => false];
        }
        $today      = date('Y-m-d');
        $dailyCount = $this->countAllToday($office_id, $today);
        return [
            'is_full' => $dailyCount >= $office['max_queue_limit'],
            'count'   => $dailyCount,
            'limit'   => $office['max_queue_limit'],
        ];
    }
}
