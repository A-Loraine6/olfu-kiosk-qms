<?php

function recordTicketEvent(
    Database $db,
    int $ticketId,
    string $event,
    ?string $fromStatus,
    ?string $toStatus,
    ?int $staffId,
    int $officeId,
    ?int $windowId,
    array $meta = []
): void {
    $metaJson = json_encode($meta, JSON_THROW_ON_ERROR);
    $stmt = $db->prepare("
        INSERT INTO ticket_events
            (ticket_id, `event`, from_status, to_status, staff_id, office_id, window_id, `meta`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        throw new RuntimeException('Could not prepare ticket history event.');
    }

    $stmt->bind_param(
        'isssiiis',
        $ticketId,
        $event,
        $fromStatus,
        $toStatus,
        $staffId,
        $officeId,
        $windowId,
        $metaJson
    );
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Could not write ticket history event: ' . $error);
    }
    $stmt->close();
}
