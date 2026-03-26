<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/activity_logger.php';

function getAllStaff($db, int $limit = 100, int $offset = 0): array
{
    $stmt = $db->prepare(
        "SELECT 
            u.id, u.full_name, u.email, u.role, o.name AS office_name, u.is_active, u.created_at, u.created_by
        FROM users u
        LEFT JOIN offices o ON u.office_id = o.id
        WHERE u.role = 'staff' AND u.is_deleted = 0
        ORDER BY u.full_name ASC
        LIMIT ? OFFSET ?"
    );

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $staff = [];

    while ($row = $result->fetch_assoc()) {
        $staff[] = $row;
    }

    $stmt->close();
    return $staff;
}

function getTotalStaffCount($db): int
{
    $stmt = $db->prepare(
        "SELECT COUNT(*) as total FROM users WHERE role = 'staff' AND is_deleted = 0"
    );

    if (!$stmt) {
        return 0;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return (int) ($row['total'] ?? 0);
}

function getStaffById($db, int $id): ?array
{
    $stmt = $db->prepare(
        "SELECT 
            u.id, u.full_name, u.email, u.office_id, o.name AS office, u.is_active, u.created_at, u.created_by
        FROM users u
        LEFT JOIN offices o ON u.office_id = o.id
        WHERE u.id = ? AND u.is_deleted = 0
        LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $staff = $result->fetch_assoc();
    $stmt->close();

    return $staff ?: null;
}

function addStaff(
    $db,
    string $full_name,
    string $email,
    string $password,
    string $office
): array {
    $full_name = trim($full_name);
    $email = strtolower(trim($email));
    $office = trim($office);
    $role = 'staff';

    if (mb_strlen($full_name) < 3 || mb_strlen($full_name) > 100) {
        return ['success' => false, 'message' => 'Full name must be between 3 and 100 characters.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address.'];
    }

    if (mb_strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    }

    $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    if (!$checkStmt) {
        return ['success' => false, 'message' => 'Database error occurred.'];
    }

    $checkStmt->bind_param('s', $email);
    $checkStmt->execute();
    $checkStmt->store_result();
    $exists = $checkStmt->num_rows > 0;
    $checkStmt->close();

    if ($exists) {
        return ['success' => false, 'message' => 'Email is already registered.'];
    }

    $officeStmt = $db->prepare("SELECT id FROM offices WHERE name = ? LIMIT 1");
    if (!$officeStmt) {
        return ['success' => false, 'message' => 'Database error occurred.'];
    }

    $officeStmt->bind_param('s', $office);
    $officeStmt->execute();
    $officeResult = $officeStmt->get_result();
    $officeRow = $officeResult->fetch_assoc();
    $officeStmt->close();

    $office_id = $officeRow ? $officeRow['id'] : null;
    if (!$office_id) {
        return ['success' => false, 'message' => 'Invalid office selected.'];
    }

    $currentUser = getCurrentUser();
    $created_by = $currentUser ? $currentUser['id'] : null;
    $hashed_password = hashPassword($password);
    $is_active = 1;
    $created_at = date('Y-m-d H:i:s');

    $insertStmt = $db->prepare(
        "INSERT INTO users (full_name, email, password, role, office_id, is_active, created_by, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$insertStmt) {
        return ['success' => false, 'message' => 'Database error occurred.'];
    }

    $insertStmt->bind_param(
        'ssssiiis',
        $full_name,
        $email,
        $hashed_password,
        $role,
        $office_id,
        $is_active,
        $created_by,
        $created_at
    );

    if ($insertStmt->execute()) {
        $new_id = $insertStmt->insert_id;
        $insertStmt->close();

        logActivity(
            $db,
            'STAFF_ADD',
            'USER',
            $new_id,
            "Added new staff member: $full_name ($email) with role $role",
            null,
            ['full_name' => $full_name, 'email' => $email, 'role' => $role, 'office' => $office]
        );

        return ['success' => true, 'message' => 'Staff member added successfully.', 'staff_id' => $new_id];
    } else {
        $insertStmt->close();
        return ['success' => false, 'message' => 'Failed to add staff member.'];
    }
}

function updateStaff(
    $db,
    int $id,
    string $full_name,
    string $email,
    string $office
): array {
    $full_name = trim($full_name);
    $email = strtolower(trim($email));
    $office = trim($office);
    $role = 'staff';

    if (mb_strlen($full_name) < 3) {
        return ['success' => false, 'message' => 'Full name must be at least 3 characters.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address.'];
    }

    $currentStaff = getStaffById($db, $id);
    if (!$currentStaff) {
        return ['success' => false, 'message' => 'Staff member not found.'];
    }

    if ($currentStaff['email'] !== $email) {
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        if (!$checkStmt) {
            return ['success' => false, 'message' => 'Database error occurred.'];
        }

        $checkStmt->bind_param('si', $email, $id);
        $checkStmt->execute();
        $checkStmt->store_result();
        $exists = $checkStmt->num_rows > 0;
        $checkStmt->close();

        if ($exists) {
            return ['success' => false, 'message' => 'Email is already in use.'];
        }
    }

    $officeStmt = $db->prepare("SELECT id FROM offices WHERE name = ? LIMIT 1");
    if (!$officeStmt) {
        return ['success' => false, 'message' => 'Database error occurred.'];
    }

    $officeStmt->bind_param('s', $office);
    $officeStmt->execute();
    $officeResult = $officeStmt->get_result();
    $officeRow = $officeResult->fetch_assoc();
    $officeStmt->close();

    $office_id = $officeRow ? $officeRow['id'] : null;
    if (!$office_id) {
        return ['success' => false, 'message' => 'Invalid office selected.'];
    }

    $updateStmt = $db->prepare(
        "UPDATE users SET full_name = ?, email = ?, role = ?, office_id = ? WHERE id = ?"
    );

    if (!$updateStmt) {
        return ['success' => false, 'message' => 'Database error occurred.'];
    }

    $updateStmt->bind_param('sssii', $full_name, $email, $role, $office_id, $id);

    if ($updateStmt->execute()) {
        $updateStmt->close();

        $before = [
            'full_name' => $currentStaff['full_name'],
            'email' => $currentStaff['email'],
            'office' => $currentStaff['office'],
        ];
        $after = ['full_name' => $full_name, 'email' => $email, 'office' => $office];

        logActivity(
            $db,
            'STAFF_EDIT',
            'USER',
            $id,
            "Updated staff member: $full_name",
            $before,
            $after
        );

        return ['success' => true, 'message' => 'Staff member updated successfully.'];
    } else {
        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to update staff member.'];
    }
}

function deleteStaff($db, int $id): array
{
    $staff = getStaffById($db, $id);
    if (!$staff) {
        return ['success' => false, 'message' => 'Staff member not found.'];
    }

    $updateStmt = $db->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?");
    if (!$updateStmt) {
        return ['success' => false, 'message' => 'Database error occurred.'];
    }

    $updateStmt->bind_param('i', $id);

    if ($updateStmt->execute()) {
        $updateStmt->close();

        logActivity(
            $db,
            'STAFF_DELETE',
            'USER',
            $id,
            "Soft-deleted staff member: {$staff['full_name']} ({$staff['email']})",
            $staff,
            null
        );

        return ['success' => true, 'message' => 'Staff member soft-deleted successfully.'];
    } else {
        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to soft-delete staff member.'];
    }
}

function activateStaff($db, int $id): array
{
    $staff = getStaffById($db, $id);
    if (!$staff) {
        return ['success' => false, 'message' => 'Staff member not found.'];
    }

    $is_active = 1;
    $updateStmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    if (!$updateStmt) {
        return ['success' => false, 'message' => 'Database error occurred.'];
    }

    $updateStmt->bind_param('ii', $is_active, $id);

    if ($updateStmt->execute()) {
        $updateStmt->close();

        logActivity(
            $db,
            'STAFF_ACTIVATE',
            'USER',
            $id,
            "Activated staff account: {$staff['full_name']}"
        );

        return ['success' => true, 'message' => 'Staff account activated.'];
    } else {
        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to activate account.'];
    }
}

function deactivateStaff($db, int $id): array
{
    $staff = getStaffById($db, $id);
    if (!$staff) {
        return ['success' => false, 'message' => 'Staff member not found.'];
    }

    $is_active = 0;
    $updateStmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    if (!$updateStmt) {
        return ['success' => false, 'message' => 'Database error occurred.'];
    }

    $updateStmt->bind_param('ii', $is_active, $id);

    if ($updateStmt->execute()) {
        $updateStmt->close();

        logActivity(
            $db,
            'STAFF_DEACTIVATE',
            'USER',
            $id,
            "Deactivated staff account: {$staff['full_name']}"
        );

        return ['success' => true, 'message' => 'Staff account deactivated.'];
    } else {
        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to deactivate account.'];
    }
}

function resetStaffPassword($db, int $id, string $new_password): array
{
    $staff = getStaffById($db, $id);
    if (!$staff) {
        return ['success' => false, 'message' => 'Staff member not found.'];
    }

    if (mb_strlen($new_password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    }

    $hashed_password = hashPassword($new_password);
    $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    if (!$updateStmt) {
        return ['success' => false, 'message' => 'Database error occurred.'];
    }

    $updateStmt->bind_param('si', $hashed_password, $id);

    if ($updateStmt->execute()) {
        $updateStmt->close();

        logActivity(
            $db,
            'PASSWORD_RESET',
            'USER',
            $id,
            "Reset password for staff: {$staff['full_name']}"
        );

        return ['success' => true, 'message' => 'Password reset successfully.'];
    } else {
        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to reset password.'];
    }
}

function getAvailableOffices($db = null): array
{
    if ($db) {
        $stmt = $db->prepare("SELECT name FROM offices ORDER BY name ASC");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $offices = [];
            while ($row = $result->fetch_assoc()) {
                $offices[] = $row['name'];
            }
            $stmt->close();
            return $offices;
        }
    }

    return [
        'Accounting',
        'Admissions',
        'Registrar',
    ];
}
