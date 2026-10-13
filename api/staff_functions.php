<?php
require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/activity_logger.php';
require_once __DIR__ . '/../includes/request_validation.php';

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

function beginWindowAssignment($db, int $officeId, ?int $windowNumber, int $excludeStaffId = 0): string
{
    if (!$db->begin_transaction()) {
        return 'error';
    }

    $officeStmt = $db->prepare("SELECT id FROM offices WHERE id = ? FOR UPDATE");
    if (!$officeStmt) {
        $db->rollback();
        return 'error';
    }
    $officeStmt->bind_param('i', $officeId);
    if (!$officeStmt->execute() || !$officeStmt->get_result()->fetch_assoc()) {
        $officeStmt->close();
        $db->rollback();
        return 'error';
    }
    $officeStmt->close();

    if ($windowNumber === null) {
        return 'available';
    }

    $assignedStmt = $db->prepare(
        "SELECT id FROM users
         WHERE office_id = ? AND window_number = ? AND is_deleted = 0 AND id != ?
         LIMIT 1"
    );
    if (!$assignedStmt) {
        $db->rollback();
        return 'error';
    }
    $assignedStmt->bind_param('iii', $officeId, $windowNumber, $excludeStaffId);
    if (!$assignedStmt->execute()) {
        $assignedStmt->close();
        $db->rollback();
        return 'error';
    }
    $isAssigned = (bool) $assignedStmt->get_result()->fetch_assoc();
    $assignedStmt->close();

    if ($isAssigned) {
        $db->rollback();
        return 'assigned';
    }

    return 'available';
}

function addStaff($db, string $full_name, string $email, string $password, string $office, ?int $window_number, ?int $role_id = null): array
{
    $full_name = validName($full_name);
    $email     = validEmail($email);
    $office    = trim($office);
    $roleName = 'staff';
    if ($role_id !== null) {
        $roleStmt = $db->prepare("SELECT name FROM roles WHERE id = ? LIMIT 1");
        $roleStmt->bind_param('i', $role_id);
        $roleStmt->execute();
        $roleRow = $roleStmt->get_result()->fetch_assoc();
        $roleStmt->close();
        if (!$roleRow || strcasecmp($roleRow['name'], 'admin') === 0) {
            return ['success' => false, 'message' => 'Invalid role selected.'];
        }
        $roleName = $roleRow['name'];
    }
    $role = $roleName;

    if ($full_name === null)
        return ['success' => false, 'message' => 'Name must contain letters and spaces only, and be 3 to 100 characters.'];
    if ($email === null)
        return ['success' => false, 'message' => 'Invalid email address.'];
    if (validPassword($password) === null)
        return ['success' => false, 'message' => 'Password must be between 8 and 255 characters.'];

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

    $assignmentStatus = beginWindowAssignment($db, (int) $office_id, $window_number);
    if ($assignmentStatus !== 'available') {
        return [
            'success' => false,
            'message' => $assignmentStatus === 'assigned'
                ? 'That window is already assigned to another staff member.'
                : 'Database error occurred while checking the window assignment.',
        ];
    }

    $insertStmt = $db->prepare(
        "INSERT INTO users (full_name, email, password, role, role_id, office_id, window_number, is_active, created_by, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$insertStmt) {
        $db->rollback();
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
    $insertStmt->bind_param('ssssiiiiis', $full_name, $email, $hashed_password, $role, $role_id, $office_id, $window_number, $is_active, $created_by, $created_at);

    if (!$insertStmt->execute()) {
        $insertStmt->close();
        $db->rollback();
        return ['success' => false, 'message' => 'Failed to add staff member.'];
    }

    $new_id = $insertStmt->insert_id;
    $insertStmt->close();
    if (!$db->commit()) {
        $db->rollback();
        return ['success' => false, 'message' => 'Failed to add staff member.'];
    }

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
    $full_name = validName($full_name);
    $email     = validEmail($email);
    $office    = trim($office);
    $roleName = 'staff';
    if ($role_id !== null) {
        $roleStmt = $db->prepare("SELECT name FROM roles WHERE id = ? LIMIT 1");
        $roleStmt->bind_param('i', $role_id);
        $roleStmt->execute();
        $roleRow = $roleStmt->get_result()->fetch_assoc();
        $roleStmt->close();
        if (!$roleRow || strcasecmp($roleRow['name'], 'admin') === 0) {
            return ['success' => false, 'message' => 'Invalid role selected.'];
        }
        $roleName = $roleRow['name'];
    }
    $role = $roleName;

    if ($full_name === null)
        return ['success' => false, 'message' => 'Name must contain letters and spaces only, and be 3 to 100 characters.'];
    if ($email === null)
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

    $assignmentStatus = beginWindowAssignment($db, (int) $office_id, $window_number, $id);
    if ($assignmentStatus !== 'available') {
        return [
            'success' => false,
            'message' => $assignmentStatus === 'assigned'
                ? 'That window is already assigned to another staff member.'
                : 'Database error occurred while checking the window assignment.',
        ];
    }

    $updateStmt = $db->prepare(
        "UPDATE users SET full_name = ?, email = ?, role = ?, role_id = ?, office_id = ?, window_number = ? WHERE id = ?"
    );
    if (!$updateStmt) {
        $db->rollback();
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
    $updateStmt->bind_param('sssiiii', $full_name, $email, $role, $role_id, $office_id, $window_number, $id);

    if (!$updateStmt->execute()) {
        $updateStmt->close();
        $db->rollback();
        return ['success' => false, 'message' => 'Failed to update staff member.'];
    }
    $updateStmt->close();
    if (!$db->commit()) {
        $db->rollback();
        return ['success' => false, 'message' => 'Failed to update staff member.'];
    }

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
    $currentUser = getCurrentUser();
    $isAdmin = ($_SESSION['role_id'] ?? null) === 1 || ($_SESSION['role'] ?? '') === 'admin';
    $isOfficeScoped = in_array(strtolower(trim((string) ($_SESSION['role'] ?? ''))), [
        'supervisor',
        'department head',
        'department_head',
        'department-head',
    ], true);
    $staff = getStaffById($db, $id);
    if (!$staff) return ['success' => false, 'message' => 'Staff member not found.'];

    if (!$isAdmin && $isOfficeScoped && (int) ($staff['office_id'] ?? 0) !== (int) ($currentUser['office_id'] ?? 0)) {
        return ['success' => false, 'message' => 'You can only reset passwords for employees in your office.'];
    }

    if (validPassword($new_password) === null)
        return ['success' => false, 'message' => 'Password must be between 8 and 255 characters.'];

    $hashed_password = hashPassword($new_password);
    $updateStmt      = $db->prepare("UPDATE users SET password = ?, must_change_password = 1 WHERE id = ?");
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
