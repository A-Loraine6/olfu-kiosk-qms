<?php
require_once __DIR__ . '/config_loader.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/request_validation.php';
require_once __DIR__ . '/../api/activity_logger.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

const PASSWORD_RESET_OTP_TTL = 600;
const PASSWORD_RESET_TICKET_TTL = 300;

function passwordResetIdentifiers(): array
{
    return ['password-reset:ip:' . trim((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'))];
}

function passwordOtpIdentifiers(string $email): array
{
    return ['password-reset-otp:ip:' . trim((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')) . ':email:' . $email];
}

function requestPasswordReset($db, string $email): string
{
    $identifiers = passwordResetIdentifiers();
    if (getLoginLockoutSeconds($db, $identifiers) > 0) {
        return 'rate_limited';
    }
    recordLoginFailure($db, $identifiers);

    $user = findActiveUserByEmail($db, $email);
    if (!$user) {
        return 'not_found';
    }

    return createAndSendResetOtp($db, $user) ? 'sent' : 'delivery_failed';
}

function findActiveUserByEmail($db, string $email): ?array
{
    $stmt = $db->prepare(
        'SELECT id, full_name, email FROM users
         WHERE email = ? AND is_active = 1 AND is_deleted = 0 LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}

function createAndSendResetOtp($db, array $user): bool
{
    if (MAIL_HOST === '' || MAIL_USERNAME === '' || MAIL_PASSWORD === '' || MAIL_FROM_ADDRESS === '') {
        error_log('Password reset email is not configured. Set the MAIL_* constants in config/config.php.');
        return false;
    }

    $otp = (string) random_int(100000, 999999);
    $tokenHash = hash('sha256', $otp);
    $expiresAt = date('Y-m-d H:i:s', time() + PASSWORD_RESET_OTP_TTL);
    $update = $db->prepare(
        'UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?'
    );
    if (!$update) {
        return false;
    }
    $update->bind_param('ssi', $tokenHash, $expiresAt, $user['id']);
    $saved = $update->execute();
    $update->close();
    $sent = $saved && sendPasswordResetOtpEmail($user, $otp);
    if (!$sent && $saved) {
        clearPasswordResetToken($db, (int) $user['id']);
    }
    return $sent;
}

function sendPasswordResetOtpEmail(array $user, string $otp): bool
{
    if (MAIL_HOST === '' || MAIL_USERNAME === '' || MAIL_PASSWORD === '' || MAIL_FROM_ADDRESS === '') {
        error_log('Password reset email is not configured.');
        return false;
    }

    $mailer = new PHPMailer(true);

    try {
        $mailer->isSMTP();
        $mailer->Host = MAIL_HOST;
        $mailer->SMTPAuth = true;
        $mailer->Username = MAIL_USERNAME;
        $mailer->Password = MAIL_PASSWORD;
        $mailer->Port = MAIL_PORT;
        $mailer->SMTPSecure = MAIL_ENCRYPTION === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mailer->addAddress($user['email'], $user['full_name']);
        $mailer->isHTML(true);
        $mailer->Subject = APP_NAME . ' — password recovery code';

        $safeName = htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8');
        $safeApp = htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8');
        $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
        $minutes = (int) (PASSWORD_RESET_OTP_TTL / 60);

        $mailer->Body = <<<HTML
        <div style="background:#f0f4f1;padding:32px 16px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8e4;">
            <tr>
              <td style="background:#12331f;padding:24px 32px;">
                <span style="color:#ffffff;font-size:16px;font-weight:600;letter-spacing:.2px;">{$safeApp}</span>
              </td>
            </tr>
            <tr>
              <td style="padding:32px;">
                <p style="margin:0 0 4px;color:#0f2417;font-size:16px;">Hi {$safeName},</p>
                <p style="margin:0 0 24px;color:#4b5f54;font-size:14px;line-height:1.6;">
                  Use this code to finish recovering your account. It expires in {$minutes} minutes.
                </p>
                <div style="background:#f0f6f2;border:1px solid #d8e6dc;border-radius:12px;padding:20px;text-align:center;margin-bottom:24px;">
                  <span style="font-size:32px;font-weight:700;letter-spacing:10px;color:#12331f;">{$safeOtp}</span>
                </div>
                <p style="margin:0;color:#7c8d82;font-size:13px;line-height:1.6;">
                  Didn't request this? You can safely ignore this email — your password will stay the same.
                </p>
              </td>
            </tr>
          </table>
        </div>
        HTML;

        $mailer->AltBody = "Your {$safeApp} password recovery code is: {$otp}\nThis code expires in {$minutes} minutes.\nIf you didn't request this, you can ignore this email.";
        $mailer->send();
        return true;
    } catch (Exception $exception) {
        error_log('Password reset email failed: ' . $exception->getMessage());
        return false;
    }
}

function findPasswordResetUserByOtp($db, string $email, string $otp): ?array
{
    $tokenHash = hash('sha256', $otp);
    $stmt = $db->prepare(
        'SELECT id, full_name, email FROM users
         WHERE email = ? AND password_reset_token = ? AND password_reset_expires > NOW()
           AND is_active = 1 AND is_deleted = 0 LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ss', $email, $tokenHash);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}

function verifyResetOtp($db, string $email, string $otp): ?string
{
    $identifiers = passwordOtpIdentifiers($email);
    if (getLoginLockoutSeconds($db, $identifiers) > 0) {
        return null;
    }
    recordLoginFailure($db, $identifiers);

    $user = findPasswordResetUserByOtp($db, $email, $otp);
    if (!$user) {
        return null;
    }

    $ticket = bin2hex(random_bytes(32));
    $ticketHash = hash('sha256', $ticket);
    $expiresAt = date('Y-m-d H:i:s', time() + PASSWORD_RESET_TICKET_TTL);
    $update = $db->prepare(
        'UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?'
    );
    if (!$update) {
        return null;
    }
    $update->bind_param('ssi', $ticketHash, $expiresAt, $user['id']);
    $saved = $update->execute();
    $update->close();

    return $saved ? $ticket : null;
}

function findUserByResetTicket($db, string $email, string $ticket): ?array
{
    $ticketHash = hash('sha256', $ticket);
    $stmt = $db->prepare(
        'SELECT id, full_name, email FROM users
         WHERE email = ? AND password_reset_token = ? AND password_reset_expires > NOW()
           AND is_active = 1 AND is_deleted = 0 LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ss', $email, $ticketHash);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}

function completePasswordReset($db, array $user, string $newPassword): bool
{
    $passwordHash = hashPassword($newPassword);
    $stmt = $db->prepare(
        'UPDATE users SET password = ?, must_change_password = 0,
         password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?'
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('si', $passwordHash, $user['id']);
    $saved = $stmt->execute();
    $stmt->close();
    if (!$saved) {
        return false;
    }

    logActivity(
        $db,
        'PASSWORD_RESET',
        'USER',
        (int) $user['id'],
        'Password reset through email recovery',
        null,
        null,
        (int) $user['id']
    );
    return true;
}

function clearPasswordResetToken($db, int $userId): void
{
    $stmt = $db->prepare(
        'UPDATE users SET password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?'
    );
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}