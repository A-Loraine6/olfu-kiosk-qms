<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/activity_logger.php';

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

function getAllStaff($db, int $limit = 100, int $offset = 0): array
{
    $currentUser = getCurrentUser();
    $officeId    = $currentUser['office_id'] ?? null;
    $isAdmin = ($_SESSION['role_id'] ?? null) === 1 || ($_SESSION['role'] ?? '') === 'admin';

    if (!$isAdmin && $officeId) {
        $stmt = $db->prepare(
            "SELECT u.id, u.full_name, u.email, u.role, u.role_id, o.name AS office_name,
                r.name AS role_name, u.window_number, u.is_active, u.created_at, u.created_by
             FROM users u
             LEFT JOIN offices o ON u.office_id = o.id
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE u.role_id IS NOT NULL AND u.role != 'admin' AND u.is_deleted = 0
             AND u.office_id = ? AND u.id != ?
             ORDER BY u.full_name ASC
             LIMIT ? OFFSET ?"
        );
        if (!$stmt) return [];
        $stmt->bind_param('iiii', $officeId, $currentUser['id'], $limit, $offset);
    } else {
        $stmt = $db->prepare(
            "SELECT u.id, u.full_name, u.email, u.role, u.role_id, o.name AS office_name,
                r.name AS role_name, u.window_number, u.is_active, u.created_at, u.created_by
             FROM users u
             LEFT JOIN offices o ON u.office_id = o.id
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE u.role_id IS NOT NULL AND u.role != 'admin' AND u.is_deleted = 0
             ORDER BY u.full_name ASC
             LIMIT ? OFFSET ?"
        );
        if (!$stmt) return [];
        $stmt->bind_param('ii', $limit, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $staff  = [];
    while ($row = $result->fetch_assoc()) {
        $staff[] = $row;
    }
    $stmt->close();
    return $staff;
}

function getTotalStaffCount($db): int
{
    $stmt = $db->prepare(
        "SELECT COUNT(*) AS total FROM users WHERE role = 'staff' AND is_deleted = 0"
    );
    if (!$stmt) return 0;

    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) ($row['total'] ?? 0);
}

function getStaffById($db, int $id): ?array
{
    $stmt = $db->prepare(
        "SELECT u.id, u.full_name, u.email, u.office_id, u.role_id,
            o.name AS office, u.window_number, u.is_active, u.created_at, u.created_by
            FROM users u
            LEFT JOIN offices o ON u.office_id = o.id
            WHERE u.id = ? AND u.is_deleted = 0
            LIMIT 1"
    );
    if (!$stmt) return null;

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function addStaff($db, string $full_name, string $email, string $password, string $office, ?int $window_number, ?int $role_id = null): array
{
    $full_name = trim($full_name);
    $email     = strtolower(trim($email));
    $office    = trim($office);
    $roleName = 'staff';
    if ($role_id !== null) {
        $roleStmt = $db->prepare("SELECT name FROM roles WHERE id = ? LIMIT 1");
        $roleStmt->bind_param('i', $role_id);
        $roleStmt->execute();
        $roleRow = $roleStmt->get_result()->fetch_assoc();
        $roleStmt->close();
        if ($roleRow) $roleName = $roleRow['name'];
    }
    $role = $roleName;

    if (mb_strlen($full_name) < 3 || mb_strlen($full_name) > 100)
        return ['success' => false, 'message' => 'Full name must be between 3 and 100 characters.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return ['success' => false, 'message' => 'Invalid email address.'];
    if (mb_strlen($password) < 8)
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];

    $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    if (!$checkStmt) return ['success' => false, 'message' => 'Database error occurred.'];
    $checkStmt->bind_param('s', $email);
    $checkStmt->execute();
    $checkStmt->store_result();
    $exists = $checkStmt->num_rows > 0;
    $checkStmt->close();
    if ($exists) return ['success' => false, 'message' => 'Email is already registered.'];

    $officeStmt = $db->prepare("SELECT id FROM offices WHERE name = ? LIMIT 1");
    if (!$officeStmt) return ['success' => false, 'message' => 'Database error occurred.'];
    $officeStmt->bind_param('s', $office);
    $officeStmt->execute();
    $officeRow = $officeStmt->get_result()->fetch_assoc();
    $officeStmt->close();
    $office_id = $officeRow['id'] ?? null;
    if (!$office_id) return ['success' => false, 'message' => 'Invalid office selected.'];

    if ($window_number !== null) {
        $wcStmt = $db->prepare("SELECT window_count FROM offices WHERE name = ? LIMIT 1");
        $wcStmt->bind_param('s', $office);
        $wcStmt->execute();
        $wcRow = $wcStmt->get_result()->fetch_assoc();
        $wcStmt->close();
        $maxWindows = (int) ($wcRow['window_count'] ?? 1);
        if ($window_number < 1 || $window_number > $maxWindows) {
            return ['success' => false, 'message' => "Window number must be between 1 and $maxWindows for this office."];
        }
    }

    $currentUser     = getCurrentUser();
    $created_by      = $currentUser ? $currentUser['id'] : null;
    $hashed_password = hashPassword($password);
    $is_active       = 1;
    $created_at      = date('Y-m-d H:i:s');

    $insertStmt = $db->prepare(
        "INSERT INTO users (full_name, email, password, role, role_id, office_id, window_number, is_active, created_by, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $insertStmt->bind_param('ssssiiiiis', $full_name, $email, $hashed_password, $role, $role_id, $office_id, $window_number, $is_active, $created_by, $created_at);

    if (!$insertStmt->execute()) {
        $insertStmt->close();
        return ['success' => false, 'message' => 'Failed to add staff member.'];
    }

    $new_id = $insertStmt->insert_id;
    $insertStmt->close();

    logActivity(
        $db,
        'STAFF_ADD',
        'USER',
        $new_id,
        "Added new staff member: $full_name)",
        null,
        ['full_name' => $full_name, 'email' => $email, 'role' => $role, 'office' => $office, 'window_number' => $window_number]
    );

    return ['success' => true, 'message' => 'Staff member added successfully.', 'staff_id' => $new_id];
}

function updateStaff($db, int $id, string $full_name, string $email, string $office, ?int $window_number, ?int $role_id = null): array
{
    $full_name = trim($full_name);
    $email     = strtolower(trim($email));
    $office    = trim($office);
    $roleName = 'staff';
    if ($role_id !== null) {
        $roleStmt = $db->prepare("SELECT name FROM roles WHERE id = ? LIMIT 1");
        $roleStmt->bind_param('i', $role_id);
        $roleStmt->execute();
        $roleRow = $roleStmt->get_result()->fetch_assoc();
        $roleStmt->close();
        if ($roleRow) $roleName = $roleRow['name'];
    }
    $role = $roleName;

    if (mb_strlen($full_name) < 3)
        return ['success' => false, 'message' => 'Full name must be at least 3 characters.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return ['success' => false, 'message' => 'Invalid email address.'];

    $currentStaff = getStaffById($db, $id);
    if (!$currentStaff) return ['success' => false, 'message' => 'Staff member not found.'];

    if ($window_number !== null) {
        $wcStmt = $db->prepare("SELECT window_count FROM offices WHERE name = ? LIMIT 1");
        $wcStmt->bind_param('s', $office);
        $wcStmt->execute();
        $wcRow = $wcStmt->get_result()->fetch_assoc();
        $wcStmt->close();
        $maxWindows = (int) ($wcRow['window_count'] ?? 1);
        if ($window_number < 1 || $window_number > $maxWindows) {
            return ['success' => false, 'message' => "Window number must be between 1 and $maxWindows for this office."];
        }
    }

    if ($currentStaff['email'] !== $email) {
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        if (!$checkStmt) return ['success' => false, 'message' => 'Database error occurred.'];
        $checkStmt->bind_param('si', $email, $id);
        $checkStmt->execute();
        $checkStmt->store_result();
        $exists = $checkStmt->num_rows > 0;
        $checkStmt->close();
        if ($exists) return ['success' => false, 'message' => 'Email is already in use.'];
    }

    $officeStmt = $db->prepare("SELECT id FROM offices WHERE name = ? LIMIT 1");
    if (!$officeStmt) return ['success' => false, 'message' => 'Database error occurred.'];
    $officeStmt->bind_param('s', $office);
    $officeStmt->execute();
    $officeRow = $officeStmt->get_result()->fetch_assoc();
    $officeStmt->close();
    $office_id = $officeRow['id'] ?? null;
    if (!$office_id) return ['success' => false, 'message' => 'Invalid office selected.'];

    $updateStmt = $db->prepare(
        "UPDATE users SET full_name = ?, email = ?, role = ?, role_id = ?, office_id = ?, window_number = ? WHERE id = ?"
    );
    $updateStmt->bind_param('sssiiii', $full_name, $email, $role, $role_id, $office_id, $window_number, $id);

    if (!$updateStmt->execute()) {
        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to update staff member.'];
    }
    $updateStmt->close();

    [$before, $after] = diffSnapshot(
        ['full_name' => $currentStaff['full_name'], 'email' => $currentStaff['email'], 'office' => $currentStaff['office'], 'window_number' => $currentStaff['window_number']],
        ['full_name' => $full_name,                 'email' => $email,                 'office' => $office,                 'window_number' => $window_number]
    );

    if (!empty($before)) {
        logActivity($db, 'STAFF_EDIT', 'USER', $id, "Updated staff member: $full_name", $before, $after);
    }

    return ['success' => true, 'message' => 'Staff member updated successfully.'];
}

function deleteStaff($db, int $id): array
{
    $staff = getStaffById($db, $id);
    if (!$staff) return ['success' => false, 'message' => 'Staff member not found.'];

    $updateStmt = $db->prepare("UPDATE users SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
    if (!$updateStmt) return ['success' => false, 'message' => 'Database error occurred.'];

    $updateStmt->bind_param('i', $id);
    if (!$updateStmt->execute()) {
        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to soft-delete staff member.'];
    }
    $updateStmt->close();

    logActivity(
        $db,
        'STAFF_DELETE',
        'USER',
        $id,
        "Deleted staff: {$staff['full_name']}"
    );

    return ['success' => true, 'message' => 'Staff member soft-deleted successfully.'];
}

function activateStaff($db, int $id): array
{
    $staff = getStaffById($db, $id);
    if (!$staff) return ['success' => false, 'message' => 'Staff member not found.'];

    $is_active  = 1;
    $updateStmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    if (!$updateStmt) return ['success' => false, 'message' => 'Database error occurred.'];

    $updateStmt->bind_param('ii', $is_active, $id);
    if (!$updateStmt->execute()) {
        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to activate account.'];
    }
    $updateStmt->close();

    logActivity(
        $db,
        'STAFF_ACTIVATE',
        'USER',
        $id,
        "Activated staff account: {$staff['full_name']}"
    );

    return ['success' => true, 'message' => 'Staff account activated.'];
}

function deactivateStaff($db, int $id): array
{
    $staff = getStaffById($db, $id);
    if (!$staff) return ['success' => false, 'message' => 'Staff member not found.'];

    $is_active  = 0;
    $updateStmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    if (!$updateStmt) return ['success' => false, 'message' => 'Database error occurred.'];

    $updateStmt->bind_param('ii', $is_active, $id);
    if (!$updateStmt->execute()) {
        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to deactivate account.'];
    }
    $updateStmt->close();

    logActivity(
        $db,
        'STAFF_DEACTIVATE',
        'USER',
        $id,
        "Deactivated staff account: {$staff['full_name']}"
    );

    return ['success' => true, 'message' => 'Staff account deactivated.'];
}

function resetStaffPassword($db, int $id, string $new_password): array
{
    $staff = getStaffById($db, $id);
    if (!$staff) return ['success' => false, 'message' => 'Staff member not found.'];

    if (mb_strlen($new_password) < 8)
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];

    $hashed_password = hashPassword($new_password);
    $updateStmt      = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    if (!$updateStmt) return ['success' => false, 'message' => 'Database error occurred.'];

    $updateStmt->bind_param('si', $hashed_password, $id);
    if (!$updateStmt->execute()) {
        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to reset password.'];
    }
    $updateStmt->close();

    logActivity(
        $db,
        'PASSWORD_RESET',
        'USER',
        $id,
        "Reset password for staff: {$staff['full_name']}",
        null,
        null
    );

    return ['success' => true, 'message' => 'Password reset successfully.'];
}

function getAvailableOffices($db = null): array
{
    if ($db) {
        $stmt = $db->prepare("SELECT name FROM offices ORDER BY name ASC");
        if ($stmt) {
            $stmt->execute();
            $result  = $stmt->get_result();
            $offices = [];
            while ($row = $result->fetch_assoc()) {
                $offices[] = $row['name'];
            }
            $stmt->close();
            return $offices;
        }
    }
    return ['Accounting', 'Admissions', 'Registrar'];
}
