<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/request_validation.php';
require_once __DIR__ . '/activity_logger.php';

if (!isLoggedIn()) {
    logAuthDenied($db->getConnection(), 'Unauthenticated access to role management API', null, 'role');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (($_SESSION['role_id'] ?? null) !== 1 && ($_SESSION['role'] ?? '') !== 'admin') {
    logPermissionDenied($db->getConnection(), 'Forbidden access to role management API', (int) ($_SESSION['user_id'] ?? 0), 'role');
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = requestEnum($input, 'action', ['add_role', 'edit_role', 'delete_role', 'toggle_permission']);
$csrf   = requestString($input, 'csrf_token', 255, true);

if ($action === null || $csrf === null || !verifyCsrfToken($csrf)) {
    logSuspiciousApiActivity($db->getConnection(), 'Invalid CSRF token or malformed role action request', (int) ($_SESSION['user_id'] ?? 0), 'role');
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$conn = $db->getConnection();
$userId = (int) ($_SESSION['user_id'] ?? 0);

switch ($action) {

    case 'add_role':
        $nameInput = requestString($input, 'name', 50, true);
        $name = $nameInput === null ? null : validName($nameInput, 2, 50);
        if ($name === null) {
            logSuspiciousApiActivity($conn, 'Attempted to create a role with invalid name', $userId, 'role');
            echo json_encode(['success' => false, 'message' => 'Role name must be at least 2 characters.']);
        } else {
            $stmt = $conn->prepare("INSERT INTO roles (name) VALUES (?)");
            $stmt->bind_param('s', $name);
            if ($stmt->execute()) {
                $roleId = (int) $stmt->insert_id;
                logActivity($conn, 'ROLE_CHANGE', 'ROLE', $roleId, "Created role \"{$name}\"", null, ['name' => $name], $userId);
                echo json_encode(['success' => true, 'message' => 'Role added.', 'id' => $roleId]);
            } else {
                logSuspiciousApiActivity($conn, 'Failed to create a role due to database error or existing duplicate', $userId, 'role');
                echo json_encode(['success' => false, 'message' => 'Role name already exists.']);
            }
            $stmt->close();
        }
        exit;

    case 'edit_role':
        $id   = requestInt($input, 'id');
        $nameInput = requestString($input, 'name', 50, true);
        $name = $nameInput === null ? null : validName($nameInput, 2, 50);
        if ($id === null || $name === null) {
            logSuspiciousApiActivity($conn, 'Attempted to edit a role with invalid data', $userId, 'role', $id);
            echo json_encode(['success' => false, 'message' => 'Invalid data.']);
            exit;
        }

        $existing = $conn->prepare("SELECT name FROM roles WHERE id = ? LIMIT 1");
        $existing->bind_param('i', $id);
        $existing->execute();
        $existingRow = $existing->get_result()->fetch_assoc();
        $existing->close();

        $stmt = $conn->prepare("UPDATE roles SET name = ? WHERE id = ? AND id != 1");
        $stmt->bind_param('si', $name, $id);
        $stmt->execute();
        $stmt->close();

        $oldRoleName = $existingRow['name'] ?? (string) $id;
        logActivity($conn, 'ROLE_CHANGE', 'ROLE', $id, "Updated role \"{$oldRoleName}\" to \"{$name}\"", $existingRow ? ['name' => $existingRow['name']] : null, ['name' => $name], $userId);
        echo json_encode(['success' => true, 'message' => 'Role updated.']);
        exit;

    case 'delete_role':
        $id = requestInt($input, 'id');
        if ($id === null) {
            logSuspiciousApiActivity($conn, 'Attempted to delete a role with invalid id', $userId, 'role');
            echo json_encode(['success' => false, 'message' => 'Invalid role.']);
            exit;
        }

        $existing = $conn->prepare("SELECT name FROM roles WHERE id = ? LIMIT 1");
        $existing->bind_param('i', $id);
        $existing->execute();
        $existingRow = $existing->get_result()->fetch_assoc();
        $existing->close();

        $stmt = $conn->prepare("UPDATE users SET role_id = NULL WHERE role_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM roles WHERE id = ? AND id != 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $deletedRoleName = $existingRow['name'] ?? (string) $id;
        logActivity($conn, 'ROLE_CHANGE', 'ROLE', $id, "Deleted role \"{$deletedRoleName}\"", $existingRow ? ['name' => $existingRow['name']] : null, null, $userId);
        echo json_encode(['success' => true, 'message' => 'Role deleted.']);
        exit;

    case 'toggle_permission':
        $roleId  = requestInt($input, 'role_id') ?? 0;
        $permKey = requestString($input, 'perm_key', 100, true) ?? '';
        $enable  = requestBool($input, 'enable');

        if (!$roleId || !$permKey || $enable === null) {
            logSuspiciousApiActivity($conn, 'Attempted to change permissions with invalid data', $userId, 'role', $roleId);
            echo json_encode(['success' => false, 'message' => 'Invalid data.']);
            exit;
        }

        $roleStmt = $conn->prepare("SELECT name FROM roles WHERE id = ? LIMIT 1");
        $roleStmt->bind_param('i', $roleId);
        $roleStmt->execute();
        $roleRow = $roleStmt->get_result()->fetch_assoc();
        $roleStmt->close();
        $roleName = $roleRow['name'] ?? (string) $roleId;

        $implies = [
            'manage_queue'    => ['view_queue'],
            'edit_services'   => ['view_services'],
            'add_services'    => ['view_services'],
            'edit_staff'      => ['view_staff'],
            'add_staff'       => ['view_staff'],
            'toggle_staff'    => ['view_staff'],
        ];

        $dependents = [
            'view_queue'      => ['manage_queue'],
            'view_services'   => ['edit_services', 'add_services'],
            'view_staff'      => ['edit_staff', 'add_staff', 'toggle_staff'],
        ];

        $keysToProcess = [$permKey];
        if ($enable && isset($implies[$permKey])) {
            $keysToProcess = array_merge($keysToProcess, $implies[$permKey]);
        }
        if (!$enable && isset($dependents[$permKey])) {
            $keysToProcess = array_merge($keysToProcess, $dependents[$permKey]);
        }

        $keysToProcess = array_unique($keysToProcess);
        $affected = [];

        foreach ($keysToProcess as $key) {
            $stmt = $conn->prepare("SELECT id FROM permissions WHERE `key` = ? LIMIT 1");
            $stmt->bind_param('s', $key);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$row) continue;

            $pid = $row['id'];
            if ($enable) {
                $stmt = $conn->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            } else {
                $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?");
            }
            $stmt->bind_param('ii', $roleId, $pid);
            $stmt->execute();
            $stmt->close();
            $affected[] = $key;
        }

        logActivity($conn, 'ROLE_PERMISSION_CHANGE', 'ROLE', $roleId, "Updated permissions for role \"{$roleName}\": " . implode(', ', $affected), null, ['permission_keys' => $affected, 'enabled' => $enable], $userId);
        echo json_encode([
            'success'  => true,
            'message'  => 'Permission updated.',
            'affected' => $affected,
        ]);
        exit;

    default:
        logSuspiciousApiActivity($conn, 'Unknown role management action requested', $userId, 'role');
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        exit;
}