<?php
require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/request_validation.php';
require_once __DIR__ . '/../includes/password_reset.php';
require_once __DIR__ . '/activity_logger.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    logAuthDenied($db->getConnection(), 'Unauthenticated access to profile API', null, 'user');
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = requestEnum($body, 'action', ['update_profile', 'request_password_change_otp', 'change_password', 'get_activity']);
$csrf   = requestString($body, 'csrf_token', 255, true);

if ($action === null || $csrf === null || !verifyCsrfToken($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
    exit;
}

$currentUser = getCurrentUser();
$conn        = $db->getConnection();

switch ($action) {

    case 'request_password_change_otp': {
            $currentPw = is_string($body['current_password'] ?? null)
                ? validPassword($body['current_password']) : null;
            $newPw = is_string($body['new_password'] ?? null)
                ? validPassword($body['new_password']) : null;
            $isPrivileged = ($currentUser['role_id'] ?? null) === 1
                || strtolower(trim((string) ($currentUser['role'] ?? ''))) === 'admin'
                || in_array(strtolower(trim((string) ($currentUser['role'] ?? ''))), ['department head', 'department_head', 'department-head'], true);

            if (!$isPrivileged) {
                echo json_encode(['success' => false, 'message' => 'Email verification is not required for this account.']);
                exit;
            }
            if ($currentPw === null) {
                echo json_encode(['success' => false, 'message' => 'Current password is required.']);
                exit;
            }
            if ($newPw === null) {
                echo json_encode(['success' => false, 'message' => 'New password is required.']);
                exit;
            }

            $stmt = $conn->prepare('SELECT id, full_name, email, password FROM users WHERE id = ? AND is_active = 1 AND is_deleted = 0 LIMIT 1');
            $stmt->bind_param('i', $currentUser['id']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($currentPw, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
                exit;
            }
            if (password_verify($newPw, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'New password must be different from your current password.']);
                exit;
            }
            $otpIdentifiers = passwordOtpIdentifiers($user['email']);
            if (getLoginLockoutSeconds($conn, $otpIdentifiers) > 0) {
                echo json_encode(['success' => false, 'message' => 'Too many verification requests. Please wait and try again.']);
                exit;
            }
            recordLoginFailure($conn, $otpIdentifiers);
            if (!createAndSendPasswordChangeOtp($conn, $user)) {
                echo json_encode(['success' => false, 'message' => 'Unable to send the verification code. Please try again later.']);
                exit;
            }

            echo json_encode(['success' => true, 'message' => 'A verification code was sent to your email.']);
            break;
        }

    case 'update_profile': {
            $fullNameInput   = requestString($body, 'full_name', 100, true);
            $emailInput      = requestString($body, 'email', 100, true);
            $fullName        = $fullNameInput === null ? null : validFullName($fullNameInput);
            $email           = $emailInput === null ? null : validEmail($emailInput, 100);
            $confirmPassword = is_string($body['confirm_password'] ?? null)
                ? validPassword($body['confirm_password']) : null;

            if ($fullName === null) {
                echo json_encode(['success' => false, 'message' => 'Full name must be 3 to 100 characters and use only letters, spaces, periods, hyphens, or apostrophes.']);
                exit;
            }
            if ($email === null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'A valid email address is required.']);
                exit;
            }
            if ($confirmPassword === null) {
                echo json_encode(['success' => false, 'message' => 'Password confirmation is required.']);
                exit;
            }

            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $currentUser['id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row || !password_verify($confirmPassword, $row['password'])) {
                echo json_encode(['success' => false, 'message' => 'Incorrect password. Changes not saved.']);
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

            $_SESSION['full_name'] = $fullName;
            $_SESSION['email']     = $email;

            echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
            break;
        }

    case 'change_password': {
            $currentPw = is_string($body['current_password'] ?? null)
                ? validPassword($body['current_password']) : null;
            $newPw     = is_string($body['new_password'] ?? null) ? validPassword($body['new_password']) : null;
            $confirmPw = is_string($body['confirm_password'] ?? null) ? validPassword($body['confirm_password']) : null;

            if ($currentPw === null || $newPw === null) {
                echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
                exit;
            }
            if ($confirmPw === null) {
                echo json_encode(['success' => false, 'message' => 'Please confirm your new password.']);
                exit;
            }
            if ($newPw !== $confirmPw) {
                echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
                exit;
            }
            if (
                strlen($newPw) < 8
                || !preg_match('/[a-z]/', $newPw)
                || !preg_match('/[A-Z]/', $newPw)
                || !preg_match('/\d/', $newPw)
                || !preg_match('/[^A-Za-z0-9]/', $newPw)
            ) {
                echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters and include uppercase, lowercase, number, and special character.']);
                exit;
            }

            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $currentUser['id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row || !password_verify($currentPw, $row['password'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
                exit;
            }

            if (password_verify($newPw, $row['password'])) {
                echo json_encode(['success' => false, 'message' => 'New password must be different from your current password.']);
                exit;
            }

            $isPrivileged = ($currentUser['role_id'] ?? null) === 1
                || strtolower(trim((string) ($currentUser['role'] ?? ''))) === 'admin'
                || in_array(strtolower(trim((string) ($currentUser['role'] ?? ''))), ['department head', 'department_head', 'department-head'], true);
            if ($isPrivileged) {
                $otp = requestString($body, 'verification_otp', 6, true) ?? '';
                $verificationTicket = preg_match('/^\d{6}$/', $otp)
                    ? verifyResetOtp($conn, $currentUser['email'], $otp)
                    : null;
                $verifiedUser = $verificationTicket
                    ? findUserByResetTicket($conn, $currentUser['email'], $verificationTicket)
                    : null;
                if (!$verifiedUser || (int) $verifiedUser['id'] !== (int) $currentUser['id']) {
                    echo json_encode(['success' => false, 'message' => 'A valid email verification code is required.']);
                    exit;
                }
            }

            $newHash = hashPassword($newPw);
            $stmt    = $conn->prepare("UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?");
            $stmt->bind_param('si', $newHash, $currentUser['id']);
            $ok = $stmt->execute();
            $stmt->close();

            if ($isPrivileged) {
                clearPasswordResetToken($conn, (int) $currentUser['id']);
            }

            if (!$ok) {
                echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again.']);
                exit;
            }

            $_SESSION['must_change_password'] = false;

            logActivity(
                $conn,
                'PASSWORD_CHANGE',
                'USER',
                $currentUser['id'],
                'Changed account password'
            );

            echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
            break;
        }

        case 'get_activity': {
            if (!requestOptionalIntIsValid($body, 'offset', 0) || !requestOptionalIntIsValid($body, 'limit', 1, 50)) {
                echo json_encode(['success' => false, 'message' => 'Invalid activity pagination.']);
                exit;
            }
            $offset    = requestOptionalInt($body, 'offset', 0) ?? 0;
            $limit     = requestOptionalInt($body, 'limit', 1, 50) ?? 20;
            $search    = requestString($body, 'search', 100) ?? '';
            $dateFrom  = requestString($body, 'date_from', 10) ?? '';
            $dateTo    = requestString($body, 'date_to', 10) ?? '';

            if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = '';
            if ($dateTo   !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = '';

            $logs = getActivityLogs(
                $conn,
                $currentUser['id'], null, null,
                $dateFrom !== '' ? $dateFrom : null,
                $dateTo !== '' ? $dateTo : null,
                $search !== '' ? $search : null, $limit, $offset
            );
            $totalCount = getActivityLogsCount(
                $conn,
                $currentUser['id'], null, null,
                $dateFrom !== '' ? $dateFrom : null,
                $dateTo !== '' ? $dateTo : null,
                $search !== '' ? $search : null
            );

            $items = array_map(fn($log) => [
                'activity_type'  => $log['activity_type'],
                'activity_label' => getActivityLabel($log['activity_type'], $log['description'] ?? null),
                'description'    => $log['description'],
                'ip_address'     => $log['ip_address'],
                'formatted_time' => date('M d, Y H:i:s', strtotime($log['created_at'])),
                'before_values'  => $log['before_values'] ?? [],
                'after_values'   => $log['after_values']  ?? [],
            ], $logs);

            echo json_encode(['success' => true, 'items' => $items, 'total_count' => $totalCount]);
            break;
        }

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
