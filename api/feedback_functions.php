<?php

function ensureStaffFeedbackTable($db): void
{
    $sql = "
        CREATE TABLE IF NOT EXISTS staff_feedback (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            staff_id INT UNSIGNED NOT NULL,
            office_id INT UNSIGNED DEFAULT NULL,
            ticket_id INT UNSIGNED DEFAULT NULL,
            ticket_number VARCHAR(50) DEFAULT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            comment TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_staff_feedback_staff (staff_id),
            KEY idx_staff_feedback_created (created_at),
            KEY idx_staff_feedback_ticket (ticket_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $db->query($sql);
}

function saveStaffFeedback($db, array $data): array
{
    $staffId = (int) ($data['staff_id'] ?? 0);
    $officeId = (int) ($data['office_id'] ?? 0);
    $ticketId = (int) ($data['ticket_id'] ?? 0);
    $ticketNumber = trim((string) ($data['ticket_number'] ?? ''));
    $rating = (int) ($data['rating'] ?? 0);
    $comment = trim((string) ($data['comment'] ?? ''));

    if ($staffId <= 0) {
        return ['success' => false, 'message' => 'Invalid staff member.'];
    }

    if ($rating < 1 || $rating > 5) {
        return ['success' => false, 'message' => 'Please select a rating between 1 and 5.'];
    }

    if (mb_strlen($comment) > 1000) {
        return ['success' => false, 'message' => 'Feedback comment is too long.'];
    }

    ensureStaffFeedbackTable($db);

    $stmt = $db->prepare(
        'INSERT INTO staff_feedback (staff_id, office_id, ticket_id, ticket_number, rating, comment, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())'
    );

    if (!$stmt) {
        return ['success' => false, 'message' => 'Unable to save feedback right now.'];
    }

    $stmt->bind_param('iiisis', $staffId, $officeId, $ticketId, $ticketNumber, $rating, $comment);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return ['success' => false, 'message' => 'Unable to save feedback right now.'];
    }

    return ['success' => true, 'message' => 'Thank you for your feedback.'];
}

function getStaffFeedbackForStaff($db, int $staffId, ?int $rating = null): array
{
    ensureStaffFeedbackTable($db);

    $sql = 'SELECT sf.*, u.full_name AS staff_name, o.name AS office_name, t.ticket_number AS queue_ticket_number
            FROM staff_feedback sf
            LEFT JOIN users u ON u.id = sf.staff_id
            LEFT JOIN offices o ON o.id = sf.office_id
            LEFT JOIN tickets t ON t.id = sf.ticket_id
            WHERE sf.staff_id = ?';

    $params = [$staffId];
    $types = 'i';

    if ($rating !== null && $rating >= 1 && $rating <= 5) {
        $sql .= ' AND sf.rating = ?';
        $params[] = $rating;
        $types .= 'i';
    }

    $sql .= ' ORDER BY sf.rating DESC, sf.created_at DESC';

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        return [];
    }

    if (count($params) === 1) {
        $stmt->bind_param($types, $params[0]);
    } else {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}
