<?php
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error       = '';
$success     = '';
$is_register = isset($_GET['register']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_register) {
    $email    = isset($_POST['email'])       ? trim($_POST['email'])      : '';
    $password = isset($_POST['password'])    ? $_POST['password']          : '';
    $csrf     = isset($_POST['csrf_token'])  ? $_POST['csrf_token']        : '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $result = login($db, $email, $password, $csrf);
        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        }
        $error = $result['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_register) {
    $full_name        = isset($_POST['full_name'])        ? trim($_POST['full_name'])        : '';
    $email            = isset($_POST['email'])            ? trim($_POST['email'])            : '';
    $password         = isset($_POST['password'])         ? $_POST['password']               : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password']       : '';
    $csrf             = isset($_POST['csrf_token'])       ? $_POST['csrf_token']             : '';

    $result = register($db, $full_name, $email, $password, $confirm_password, $csrf);
    if ($result['success']) {
        $success     = $result['message'];
        $is_register = false;
    } else {
        $error = $result['message'];
    }
}

$csrf_token = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_register ? 'Create Account' : 'Sign In'; ?></title>
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">

            <div class="auth-logo">
                <img src="img/olfu-logo.png" alt="OLFU Logo">
            </div>

            <div class="auth-card-header">
                <?php if ($is_register): ?>
                    <h1>Create an account</h1>
                    <p>Fill in the details below to get started.</p>
                <?php else: ?>
                    <h1>Welcome back</h1>
                    <p>Sign in to your admin account to continue.</p>
                <?php endif; ?>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token"
                    value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                <?php if ($is_register): ?>
                    <div class="form-group">
                        <label for="full_name"><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" id="full_name" name="full_name"
                            required minlength="3" maxlength="100"
                            autocomplete="name" placeholder="Full name">
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="email" name="email"
                        required maxlength="254"
                        autocomplete="<?php echo $is_register ? 'email' : 'username'; ?>"
                        placeholder="Email address">
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password"
                            required minlength="8"
                            autocomplete="<?php echo $is_register ? 'new-password' : 'current-password'; ?>"
                            placeholder="Password">
                        <button type="button" class="pwd-toggle" aria-label="Show/hide password"
                            onclick="togglePwd('password',this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if ($is_register): ?>
                        <div class="strength-wrap" id="sw">
                            <div class="strength-bar">
                                <div class="strength-fill" id="sf"></div>
                            </div>
                            <span class="strength-text" id="sl"></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($is_register): ?>
                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                        <div class="input-wrap">
                            <input type="password" id="confirm_password" name="confirm_password"
                                required minlength="8"
                                autocomplete="new-password"
                                placeholder="Confirm password">
                            <button type="button" class="pwd-toggle" aria-label="Show/hide password"
                                onclick="togglePwd('confirm_password',this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: var(--spacing-md);">
                    <i class="fas fa-<?php echo $is_register ? 'user-plus' : 'sign-in-alt'; ?>"></i>
                    <?php echo $is_register ? 'Create Account' : 'Sign In'; ?>
                </button>
            </form>

            <!-- <div class="auth-toggle">
                <?php if ($is_register): ?>
                    Already have an account? <a href="login.php">Sign in</a>
                <?php else: ?>
                    Don't have an account? <a href="login.php?register=1">Create one</a>
                <?php endif; ?>
            </div> -->
        </div>
    </div>

    <script src="scripts/login.js"></script>
</body>

</html>
