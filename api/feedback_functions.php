<?php

require_once __DIR__ . '/../includes/request_validation.php';

/**
 * Basic profanity filter. Case-insensitive, word-boundary aware,
 * masks the middle of each matched word with asterisks.
 * Keep the list in one place so it's easy to extend later.
 */
function getProfanityWordList(): array
    {
    return [
        'damn', 'dammit', 'hell', 'crap', 'shit', 'sh1t',
        'fuck', 'fck', 'fuk', 'f*ck', 'f@ck', 'f4ck',
        'fucking', 'fcking', 'fuckin',
        'bitch', 'b1tch', 'b!tch', 'biatch',
        'ass', 'a$$', 'arse', 'asshole', 'a$$hole',
        'bastard', 'dick', 'd1ck', 'd!ck',
        'piss', 'p1ss', 'slut', 'slutty', 'whore',
        'w*ore', 'bullshit', 'bs',

        'putangina', 'putang ina', 'putanginamo', 'putang ina mo',
        'tangina', 'tang ina', 'tanginamo', 'tang ina mo',
        'p*tangina', 'p4tangina', 'p*t4ngina',
        'gago', 'g4go', 'g@go',
        'gaga', 'g@g@',
        'tarantado', 'tarantada', 't@rantado',
        'ulol', 'ul0l', 'ul!l',
        'bobo', 'b0bo', 'b0b0', 'b*bo',
        'tanga', 't@nga', 't4nga',
        'kupal', 'kup@l', 'kup4l',
        'leche', 'letse', 'l3che', 'l3tse',
        'leche ka', 'letse ka',
        'hayop', 'hayup', 'h@yop',
        'animal', 'an1mal',
        'siraulo', 'sira ulo',
        'siraulo ka',
        'walanghiya', 'walang hiya',
        'walanghiyang',
        'hinayupak', 'hinayupak',
        'punyeta', 'punyeta', 'p*nyeta',
        'peste', 'p3ste',
        'bwisit', 'bw1sit', 'bwis!t',
        'yawa', 'y@wa', 'y4wa',
        'pakyu', 'pakyow', 'pakyo',
        'p@kyu', 'p4kyu',
        'inutil', '1nutil',
        'tonto', 't0nto',
        'engot', '3ngot',

        'g4g0', 'g4g@',
        'b0b0', 'b0bo', 'b@bo',
        't4ng4', 't@ng@',
        'k*pal', 'kup4l',
        'ul0l', 'uLOL',
        'y4w4', 'y@w@',
        'bw1s1t', 'bw!s!t',
        'p4kyu', 'p@kyu',
        'pnyeta', 'pny3ta',
        'tarant4do', 't@rant4do',
        'h@yup', 'h4yop',
        'w4langhiya',

        'fuck you', 'fck you', 'fuk u',
        'fuck u', 'f*ck you',
        'putang ina mo', 'tangina mo',
        'tang ina mo', 'gago ka', 'gaga ka',
        'bobo ka', 'tanga ka', 'ulol ka',
        'kupal ka', 'tarantado ka',
        'hayop ka', 'animal ka',
        'walang hiya ka', 'yawa ka',
    ];
}

function getLeetSubstitutionMap(): array
{
    return [
        'a' => 'a4@∆',
        'b' => 'b8',
        'c' => 'c(<{',
        'e' => 'e3€',
        'g' => 'g69',
        'i' => 'i1!|¡',
        'l' => 'l1|!',
        'o' => 'o0()',
        's' => 's$5z',
        't' => 't7+',
        'u' => 'uv',
    ];
}

function buildFlexiblePattern(string $word): string
{
    $subs = getLeetSubstitutionMap();
    $letters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);

    $parts = [];
    foreach ($letters as $letter) {
        $lower = mb_strtolower($letter);
        $classChars = $lower;
        if (isset($subs[$lower])) {
            $classChars = $subs[$lower];
        }
        $classChars = preg_replace('/([\^\]\\\\\-])/', '\\\\$1', $classChars);
        $parts[] = '[' . $classChars . ']+[\s\-_.*]{0,2}';
    }

    $body = rtrim(implode('', $parts), '[\s\-_.*]{0,2}');
    $pattern = implode('', $parts);

    return '/(?<![a-z0-9])' . $pattern . '(?![a-z0-9])/iu';
}

function censorProfanity(string $text): string
{
    $words = getProfanityWordList();
    if (empty($words) || $text === '') {
        return $text;
    }

    usort($words, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));

    foreach ($words as $word) {
        $pattern = buildFlexiblePattern($word);

        $text = preg_replace_callback($pattern, function ($m) {
            $matched = $m[0];
            $len = mb_strlen($matched);
            if ($len <= 2) {
                return str_repeat('*', $len);
            }
            return mb_substr($matched, 0, 1) . str_repeat('*', $len - 1);
        }, $text);
    }

    return $text;
}

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
    $staffId = filter_var($data['staff_id'] ?? null, FILTER_VALIDATE_INT);
    $officeId = filter_var($data['office_id'] ?? null, FILTER_VALIDATE_INT);
    $ticketId = filter_var($data['ticket_id'] ?? null, FILTER_VALIDATE_INT);
    $ticketNumber = validTicketNumber((string) ($data['ticket_number'] ?? ''));
    $rating = filter_var($data['rating'] ?? null, FILTER_VALIDATE_INT);
    $comment = validComment((string) ($data['comment'] ?? ''));

    if ($staffId === false || $staffId <= 0 || $officeId === false || $officeId <= 0 || $ticketId === false || $ticketId <= 0) {
        return ['success' => false, 'message' => 'Invalid staff member.'];
    }

    if ($rating === false || $rating < 1 || $rating > 5) {
        return ['success' => false, 'message' => 'Please select a rating between 1 and 5.'];
    }

    if ($comment === null) {
        return ['success' => false, 'message' => 'Feedback comment is too long.'];
    }

    $comment = censorProfanity($comment);

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
