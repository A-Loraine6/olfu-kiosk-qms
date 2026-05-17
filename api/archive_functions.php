<?php
require_once __DIR__ . '/activity_logger.php';

function getDeletedStaff(mysqli $conn): array
{
    $sql = "SELECT u.id, u.full_name, u.email, u.role, u.office_id, u.is_active,
                   u.created_at, u.deleted_at, o.name AS office_name
            FROM users u
            LEFT JOIN offices o ON u.office_id = o.id
            WHERE u.is_deleted = 1 AND u.role != 'admin'
            ORDER BY u.full_name ASC";

    $result = $conn->query($sql);
    if (!$result) return [];

    $staff = [];
    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int) $row['id'];
        $staff[] = $row;
    }
    return $staff;
}

function getDeletedServices(mysqli $conn): array
{
    $sql = "SELECT tt.id, tt.name, tt.category, tt.description, tt.is_active,
                   tt.created_at, tt.deleted_at, o.name AS office_name
            FROM transaction_types tt
            LEFT JOIN offices o ON tt.office_id = o.id
            WHERE tt.is_deleted = 1
            ORDER BY tt.name ASC";

    $result = $conn->query($sql);
    if (!$result) return [];

    $services = [];
    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int) $row['id'];
        $services[] = $row;
    }
    return $services;
}

function restoreStaff(mysqli $conn, int $id): array
{
    $stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE id = ? AND is_deleted = 1");
    if (!$stmt) return ['success' => false, 'message' => 'Database error occurred.'];

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $staff = $result->fetch_assoc();
    $stmt->close();

    if (!$staff) return ['success' => false, 'message' => 'Staff member not found in archive.'];

    $updateStmt = $conn->prepare("UPDATE users SET is_deleted = 0, is_active = 0 WHERE id = ?");
    if (!$updateStmt) return ['success' => false, 'message' => 'Database error occurred.'];

    $updateStmt->bind_param('i', $id);
    if (!$updateStmt->execute()) {
        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to restore staff member.'];
    }
    $updateStmt->close();

    logActivity(
        $conn,
        'STAFF_RESTORE',
        'USER',
        $id,
        "Restored staff: {$staff['full_name']}"
    );

    return ['success' => true, 'message' => 'Staff member restored successfully.'];
}

function restoreService(mysqli $conn, int $id): array
{
    $stmt = $conn->prepare("SELECT id, name FROM transaction_types WHERE id = ? AND is_deleted = 1");
    if (!$stmt) return ['success' => false, 'message' => 'Database error occurred.'];

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $service = $result->fetch_assoc();
    $stmt->close();

    if (!$service) return ['success' => false, 'message' => 'Service not found in archive.'];

    $updateStmt = $conn->prepare("UPDATE transaction_types SET is_deleted = 0, is_active = 0 WHERE id = ?");
    if (!$updateStmt) return ['success' => false, 'message' => 'Database error occurred.'];

    $updateStmt->bind_param('i', $id);
    if (!$updateStmt->execute()) {
        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to restore service.'];
    }
    $updateStmt->close();

    logActivity(
        $conn,
        'SERVICE_RESTORE',
        'SERVICE',
        $id,
        "Restored service: {$service['name']}"
    );

    return ['success' => true, 'message' => 'Service restored successfully.'];
}

function permanentlyDeleteStaff(mysqli $conn, int $id): array
{
    $stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE id = ? AND is_deleted = 1");
    if (!$stmt) return ['success' => false, 'message' => 'Database error occurred.'];

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $staff = $result->fetch_assoc();
    $stmt->close();

    if (!$staff) return ['success' => false, 'message' => 'Staff member not found in archive.'];

    logActivity(
        $conn,
        'STAFF_PERMANENT_DELETE',
        'USER',
        $id,
        "Permanently deleted archived staff member: {$staff['full_name']} ({$staff['email']})",
        ['full_name' => $staff['full_name'], 'email' => $staff['email']],
        null
    );

    $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    if (!$deleteStmt) return ['success' => false, 'message' => 'Database error occurred.'];

    $deleteStmt->bind_param('i', $id);
    if (!$deleteStmt->execute()) {
        $deleteStmt->close();
        return ['success' => false, 'message' => 'Failed to permanently delete staff member.'];
    }
    $deleteStmt->close();

    return ['success' => true, 'message' => 'Staff member permanently deleted.'];
}

function permanentlyDeleteService(mysqli $conn, int $id): array
{
    $stmt = $conn->prepare("SELECT id, name FROM transaction_types WHERE id = ? AND is_deleted = 1");
    if (!$stmt) return ['success' => false, 'message' => 'Database error occurred.'];

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $service = $result->fetch_assoc();
    $stmt->close();

    if (!$service) return ['success' => false, 'message' => 'Service not found in archive.'];

    $deleteStmt = $conn->prepare("DELETE FROM transaction_types WHERE id = ?");
    if (!$deleteStmt) return ['success' => false, 'message' => 'Database error occurred.'];

    $deleteStmt->bind_param('i', $id);
    if (!$deleteStmt->execute()) {
        $deleteStmt->close();
        return ['success' => false, 'message' => 'Failed to permanently delete service.'];
    }
    $deleteStmt->close();

    logActivity(
        $conn,
        'SERVICE_PERMANENT_DELETE',
        'SERVICE',
        $id,
        "Permanently deleted archived service: {$service['name']}",
        ['name' => $service['name']],
        null
    );

    return ['success' => true, 'message' => 'Service permanently deleted.'];
}
