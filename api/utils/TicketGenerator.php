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

        $transaction_type = ($service_name !== '') ? $service_name : null;

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
            $ticketNumber = $this->generateTicketNumber($office);
            $today        = date('Y-m-d');
            $waitingCount = $this->countWaiting($office_id, $today);
            $peopleAhead  = $waitingCount;

            $insert = $this->db->prepare("
                INSERT INTO tickets
                    (office_id, transaction_type, ticket_number, status, created_at)
                VALUES
                    (?, ?, ?, 'waiting', NOW())
            ");
            $insert->bind_param('iss', $office_id, $transaction_type, $ticketNumber);

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
            'success'       => true,
            'ticket_number' => $ticketNumber,
            'people_ahead'  => $peopleAhead,
            'waiting_count' => $waitingCount + 1
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

    private function fetchOffice(int $office_id): array|false
    {
        $stmt = $this->db->prepare("
            SELECT id, name, code, queue_prefix, queue_mode
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
    
    private function generateTicketNumber(array $office): string
    {
        $prefix = $office['queue_prefix'] ?: strtoupper(substr($office['code'], 0, 3));
        $today  = date('Y-m-d');

        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS cnt
            FROM   tickets
            WHERE  office_id        = ?
              AND  DATE(created_at) = ?
        ");
        $stmt->bind_param('is', $office['id'], $today);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $next = ((int)($row['cnt'] ?? 0)) + 1;

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
}
