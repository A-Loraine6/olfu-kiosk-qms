<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../api/utils/TicketEvents.php';

$connection = $db->getConnection();
if (!$connection->begin_transaction()) {
    fwrite(STDERR, "Could not start stale-ticket cleanup.\n");
    exit(1);
}

try {
    $selectStmt = $db->prepare("
        SELECT id, office_id, window_id, status
        FROM tickets
        WHERE status IN ('waiting', 'on-hold', 'skipped', 'serving')
          AND DATE(created_at) < CURDATE()
        FOR UPDATE
    ");
    if (!$selectStmt->execute()) {
        throw new RuntimeException('Could not find stale queue tickets.');
    }
    $staleTickets = $selectStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $selectStmt->close();

    $updateStmt = $db->prepare("
        UPDATE tickets
        SET status = 'cancelled'
        WHERE id = ? AND status = ?
          AND DATE(created_at) < CURDATE()
    ");
    $cancelledCount = 0;
    foreach ($staleTickets as $ticket) {
        $ticketId = (int) $ticket['id'];
        $ticketStatus = $ticket['status'];
        $updateStmt->bind_param('is', $ticketId, $ticketStatus);
        if (!$updateStmt->execute()) {
            throw new RuntimeException('Could not cancel a stale queue ticket.');
        }
        if ($updateStmt->affected_rows !== 1) {
            continue;
        }

        recordTicketEvent(
            $db,
            $ticketId,
            'ticket_cancelled',
            $ticketStatus,
            'cancelled',
            null,
            (int) $ticket['office_id'],
            $ticket['window_id'] !== null ? (int) $ticket['window_id'] : null,
            ['source' => 'stale_ticket_cleanup']
        );
        $cancelledCount++;
    }
    $updateStmt->close();

    if (!$connection->commit()) {
        throw new RuntimeException('Could not commit stale-ticket cleanup.');
    }
    fwrite(STDOUT, sprintf("Cancelled %d stale ticket(s).\n", $cancelledCount));
} catch (Throwable $error) {
    $connection->rollback();
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}

$db->close();
