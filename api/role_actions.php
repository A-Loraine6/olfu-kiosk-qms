<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/request_validation.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (($_SESSION['role_id'] ?? null) !== 1 && ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = requestEnum($input, 'action', ['add_role', 'edit_role', 'delete_role', 'toggle_permission']);
$csrf   = requestString($input, 'csrf_token', 255, true);

if ($action === null || $csrf === null || !verifyCsrfToken($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$conn = $db->getConnection();

switch ($action) {

    case 'add_role':
        $nameInput = requestString($input, 'name', 50, true);
        $name = $nameInput === null ? null : validName($nameInput, 2, 50);
        if ($name === null) {
            echo json_encode(['success' => false, 'message' => 'Role name must be at least 2 characters.']);
        } else {
            $stmt = $conn->prepare("INSERT INTO roles (name) VALUES (?)");
            $stmt->bind_param('s', $name);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Role added.', 'id' => $stmt->insert_id]);
            } else {
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
            echo json_encode(['success' => false, 'message' => 'Invalid data.']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE roles SET name = ? WHERE id = ? AND id != 1");
        $stmt->bind_param('si', $name, $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Role updated.']);
        exit;

    case 'delete_role':
        $id = requestInt($input, 'id');
        if ($id === null) {
            echo json_encode(['success' => false, 'message' => 'Invalid role.']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE users SET role_id = NULL WHERE role_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM roles WHERE id = ? AND id != 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Role deleted.']);
        exit;

    case 'toggle_permission':
        $roleId  = requestInt($input, 'role_id') ?? 0;
        $permKey = requestString($input, 'perm_key', 100, true) ?? '';
        $enable  = requestBool($input, 'enable');

        if (!$roleId || !$permKey || $enable === null) {
            echo json_encode(['success' => false, 'message' => 'Invalid data.']);
            exit;
        }

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

        echo json_encode([
            'success'  => true,
            'message'  => 'Permission updated.',
            'affected' => $affected,
        ]);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        exit;
}
