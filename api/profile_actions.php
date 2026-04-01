<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/activity_logger.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';
$csrf   = $body['csrf_token'] ?? '';

if (!verifyCsrfToken($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
    exit;
}

$currentUser = getCurrentUser();
$conn        = $db->getConnection();

switch ($action) {

    case 'update_profile': {
            $fullName = trim(strip_tags($body['full_name'] ?? ''));
            $email    = trim($body['email'] ?? '');

            if ($fullName === '') {
                echo json_encode(['success' => false, 'message' => 'Full name is required.']);
                exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'A valid email address is required.']);
                exit;
            }

            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $stmt->bind_param('si', $email, $currentUser['id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $stmt->close();
                echo json_encode(['success' => false, 'message' => 'That email is already in use by another account.']);
                exit;
            }
            $stmt->close();

            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->bind_param('ssi', $fullName, $email, $currentUser['id']);
            $ok = $stmt->execute();
            $stmt->close();

            if (!$ok) {
                echo json_encode(['success' => false, 'message' => 'Failed to update profile. Please try again.']);
                exit;
            }

            logActivity(
                $conn,
                'PROFILE_UPDATE',
                'USER',
                $currentUser['id'],
                'Updated own profile',
                ['full_name' => $currentUser['full_name'], 'email' => $currentUser['email']],
                ['full_name' => $fullName, 'email' => $email]
            );

            echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
            break;
        }

    case 'change_password': {
            $currentPw = $body['current_password'] ?? '';
            $newPw     = $body['new_password']     ?? '';

            if ($currentPw === '' || $newPw === '') {
                echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
                exit;
            }
            if (strlen($newPw) < 8) {
                echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
                exit;
            }

            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $currentUser['id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row || !password_verify($currentPw, $row['password_hash'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
                exit;
            }

            $newHash = password_hash($newPw, PASSWORD_DEFAULT);
            $stmt    = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param('si', $newHash, $currentUser['id']);
            $ok = $stmt->execute();
            $stmt->close();

            if (!$ok) {
                echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again.']);
                exit;
            }

            logActivity(
                $conn,
                'PASSWORD_CHANGE',
                'USER',
                $currentUser['id'],
                'Changed own account password'
            );

            echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
            break;
        }

    case 'get_activity': {
            $offset = max(0, (int) ($body['offset'] ?? 0));
            $limit  = min(50, max(1, (int) ($body['limit'] ?? 20)));

            $logs = getActivityLogs(
                $conn,
                user_id: $currentUser['id'],
                limit: $limit,
                offset: $offset
            );

            $activityLabels = [
                'LOGIN'                    => 'User Login',
                'LOGOUT'                   => 'User Logout',
                'STAFF_ADD'                => 'Add Staff',
                'STAFF_EDIT'               => 'Edit Staff',
                'STAFF_DELETE'             => 'Delete Staff',
                'STAFF_ACTIVATE'           => 'Activate Staff',
                'STAFF_DEACTIVATE'         => 'Deactivate Staff',
                'PASSWORD_RESET'           => 'Staff Password Reset',
                'PASSWORD_CHANGE'          => 'Password Changed',
                'PROFILE_UPDATE'           => 'Profile Updated',
                'SERVICE_ADD'              => 'Add Service',
                'SERVICE_EDIT'             => 'Edit Service',
                'SERVICE_DELETE'           => 'Delete Service',
                'SERVICE_TOGGLE_STATUS'    => 'Toggle Service Status',
                'OFFICE_QUEUE_MODE_TOGGLE' => 'Toggle Queue Mode',
                'SETTINGS_SCHEDULE_UPDATE' => 'Update Schedule',
                'SETTINGS_QUEUE_UPDATE'    => 'Update Queue Config',
                'SETTINGS_KIOSK_UPDATE'    => 'Update Kiosk Settings',
            ];

            $items = array_map(fn($log) => [
                'activity_type'  => $log['activity_type'],
                'activity_label' => $activityLabels[$log['activity_type']] ?? $log['activity_type'],
                'description'    => $log['description'],
                'ip_address'     => $log['ip_address'],
                'formatted_time' => date('M d, Y H:i:s', strtotime($log['created_at'])),
                'before_values'  => $log['before_values'] ?? [],
                'after_values'   => $log['after_values']  ?? [],
            ], $logs);

            echo json_encode(['success' => true, 'items' => $items]);
            break;
        }

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
