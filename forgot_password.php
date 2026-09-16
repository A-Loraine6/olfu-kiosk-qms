<?php
require_once __DIR__ . '/includes/password_reset.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if (($_GET['restart'] ?? '') === '1') {
    unset($_SESSION['password_reset_email'], $_SESSION['password_reset_ticket'], $_SESSION['password_reset_expires']);
    header('Location: forgot_password.php');
    exit;
}

$message = '';
$messageType = 'success';
$csrfToken = getCsrfToken();

if (!empty($_SESSION['password_reset_expires']) && time() > $_SESSION['password_reset_expires']) {
    unset($_SESSION['password_reset_email'], $_SESSION['password_reset_ticket'], $_SESSION['password_reset_expires']);
    $message = 'Your code expired. Please request a new one.';
    $messageType = 'error';
}

$step = 'email';
if (!empty($_SESSION['password_reset_email']) && !empty($_SESSION['password_reset_ticket'])) {
    $step = 'password';
} elseif (!empty($_SESSION['password_reset_email'])) {
    $step = 'otp';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = requestString($_POST, 'csrf_token', 255, true) ?? '';
    $action = requestEnum($_POST, 'action', ['request_otp', 'resend_otp', 'verify_otp', 'reset_password'], false) ?: '';

    if (!verifyCsrfToken($csrf)) {
        $message = 'Invalid request. Please refresh and try again.';
        $messageType = 'error';
    } elseif ($action === 'request_otp' || $action === 'resend_otp') {
        $email = validEmail((string) ($_POST['email'] ?? ($_SESSION['password_reset_email'] ?? '')), 100);
        if ($email === null) {
            $message = 'Please enter a valid email address.';
            $messageType = 'error';
        } else {
            $status = requestPasswordReset($db, $email);
            if ($status === 'delivery_failed') {
                $message = 'Password recovery email is not available right now. Please contact an administrator.';
                $messageType = 'error';
            } elseif ($status === 'rate_limited') {
                $message = 'Too many attempts. Please wait a moment and try again.';
                $messageType = 'error';
            } else {
                unset($_SESSION['password_reset_ticket']);
                $_SESSION['password_reset_email'] = $email;
                $_SESSION['password_reset_expires'] = time() + PASSWORD_RESET_OTP_TTL;
                $step = 'otp';
                $message = 'Recovery code sent successfully.';
            }
        }
    } elseif ($action === 'verify_otp' && $step === 'otp') {
        $email = $_SESSION['password_reset_email'] ?? '';
        $otp = requestString($_POST, 'otp', 6, true) ?? '';

        if (!preg_match('/^\d{6}$/', $otp)) {
            $message = 'Enter the 6-digit code from your email.';
            $messageType = 'error';
        } else {
            $ticket = verifyResetOtp($db, $email, $otp);
            if ($ticket === null) {
                $message = 'Invalid or expired recovery code.';
                $messageType = 'error';
            } else {
                $_SESSION['password_reset_ticket'] = $ticket;
                $_SESSION['password_reset_expires'] = time() + PASSWORD_RESET_TICKET_TTL;
                $step = 'password';
            }
        }
    } elseif ($action === 'reset_password' && $step === 'password') {
        $email = $_SESSION['password_reset_email'] ?? '';
        $ticket = $_SESSION['password_reset_ticket'] ?? '';
        $newPassword = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
        $confirmPassword = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
        $user = findUserByResetTicket($db, $email, $ticket);

        if (!$user) {
            $message = 'Your session expired. Please start over.';
            $messageType = 'error';
            unset($_SESSION['password_reset_email'], $_SESSION['password_reset_ticket'], $_SESSION['password_reset_expires']);
            $step = 'email';
        } elseif (validPassword($newPassword) === null) {
            $message = 'Password must be between 8 and 255 characters.';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Passwords do not match.';
            $messageType = 'error';
        } elseif (completePasswordReset($db, $user, $newPassword)) {
            unset($_SESSION['password_reset_email'], $_SESSION['password_reset_ticket'], $_SESSION['password_reset_expires']);
            $step = 'done';
            $message = 'Your password has been reset successfully.';
        } else {
            $message = 'Unable to reset your password. Please request a new code.';
            $messageType = 'error';
        }
    } else {
        $message = 'That step has expired. Please start over.';
        $messageType = 'error';
        unset($_SESSION['password_reset_email'], $_SESSION['password_reset_ticket'], $_SESSION['password_reset_expires']);
        $step = 'email';
    }
}

$stepMeta = [
    'email' => ['n' => 1, 'label' => 'Email'],
    'otp' => ['n' => 2, 'label' => 'Verify'],
    'password' => ['n' => 3, 'label' => 'New password'],
    'done' => ['n' => 3, 'label' => 'Done'],
];
$currentStepNumber = $stepMeta[$step]['n'];
$maskedEmail = '';
if (!empty($_SESSION['password_reset_email'])) {
    [$local, $domain] = array_pad(explode('@', $_SESSION['password_reset_email'], 2), 2, '');
    $maskedEmail = (strlen($local) > 2 ? $local[0] . str_repeat('•', strlen($local) - 2) . substr($local, -1) : $local) . '@' . $domain;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Sign In</title>
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/forgot_password.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="fp-body">
    <div class="fp-backdrop">
        <div class="fp-card" role="dialog" aria-labelledby="fp-heading">

            <div class="fp-card-head">
                <img class="fp-logo" src="img/olfu-logo.png" alt="OLFU">
                <div>
                    <h1 id="fp-heading">Reset your password</h1>
                    <p class="fp-sub">Queue Management System</p>
                </div>
            </div>

            <?php if ($step !== 'done'): ?>
            <ol class="fp-steps" aria-hidden="true">
                <?php foreach (['email' => 'Email', 'otp' => 'Verify', 'password' => 'New password'] as $key => $label):
                    $n = $stepMeta[$key]['n'];
                    $state = $n < $currentStepNumber ? 'done' : ($n === $currentStepNumber ? 'current' : 'upcoming');
                ?>
                <li class="fp-step fp-step--<?php echo $state; ?>">
                    <span class="fp-step-dot"><?php echo $state === 'done' ? '<i class="fas fa-check"></i>' : $n; ?></span>
                    <span class="fp-step-label"><?php echo $label; ?></span>
                </li>
                <?php endforeach; ?>
            </ol>
            <?php endif; ?>

            <?php if ($step === 'email'): ?>
                <p class="fp-copy">Enter the email on your account and we'll send you a 6-digit code.</p>
                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="request_otp">
                    <div class="fp-field">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" required maxlength="254" autofocus placeholder="you@olfu.edu.ph">
                    </div>
                    <button type="submit" class="fp-btn-primary">Send code</button>
                </form>

            <?php elseif ($step === 'otp'): ?>
                <p class="fp-copy">Enter the 6-digit code we sent to <strong><?php echo htmlspecialchars($maskedEmail, ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="verify_otp">
                    <div class="fp-field">
                        <label for="otp">Recovery code</label>
                        <input type="text" id="otp" name="otp" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" autofocus placeholder="000000" class="fp-otp-input">
                    </div>
                    <button type="submit" class="fp-btn-primary">Verify code</button>
                </form>
                <div class="fp-aux-row">
                    <button type="submit" form="fp-resend-form" class="fp-link-btn">Resend code</button>
                    <a href="forgot_password.php?restart=1" class="fp-link-btn">Use a different email</a>
                </div>
                <form id="fp-resend-form" method="POST" class="fp-hidden-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="resend_otp">
                </form>

            <?php elseif ($step === 'password'): ?>
                <p class="fp-copy">Choose a new password for your account.</p>
                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="reset_password">
                    <div class="fp-field">
                        <label for="new_password">New password</label>
                        <div class="fp-input-wrap">
                            <input type="password" id="new_password" name="new_password" required minlength="8" maxlength="255" autofocus placeholder="Enter your new password">
                            <button type="button" class="fp-pwd-toggle" onclick="togglePwd('new_password', this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="fp-strength" id="sw">
                            <div class="fp-strength-bar"><div class="fp-strength-fill" id="sf"></div></div>
                            <span class="fp-strength-label" id="sl"></span>
                        </div>
                    </div>
                    <div class="fp-field">
                        <label for="confirm_password">Confirm password</label>
                        <div class="fp-input-wrap">
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" maxlength="255" placeholder="Confirm your new password">
                            <button type="button" class="fp-pwd-toggle" onclick="togglePwd('confirm_password', this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <span class="fp-match-msg" id="pwd-match-msg"></span>
                    </div>
                    <button type="submit" class="fp-btn-primary">Reset password</button>
                </form>

            <?php else: ?>
                <div class="fp-done">
                    <div class="fp-done-icon"><i class="fas fa-check"></i></div>
                    <p class="fp-copy">Your password has been reset. You can sign in now.</p>
                </div>
                <a href="login.php" class="fp-btn-primary fp-btn-link">Back to sign in</a>
            <?php endif; ?>

            <?php if ($step !== 'done'): ?>
            <a class="fp-back-link" href="login.php"><i class="fas fa-arrow-left"></i> Back to sign in</a>
            <?php endif; ?>
        </div>
    </div>
    <div id="toast-container"></div>
    <?php if ($message): ?>
        <script>
            window.loginToast = <?php echo json_encode(['message' => $message, 'type' => $messageType], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        </script>
    <?php endif; ?>
    <script src="scripts/forgot_password.js"></script>
</body>
</html>