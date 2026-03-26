<?php
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf     = $_POST['csrf_token'] ?? '';
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

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

$csrf_token = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="page-wrap">

        <div class="brand-panel">
            <div class="wordmark">
                <img src="img/olfu-logo.png" alt="OLFU">
                <span>Our Lady of Fatima University</span>
            </div>
            <div class="brand-body">
                <h2 class="brand-headline">
                    Queue Management<br><em>System</em>
                </h2>
                <p class="brand-sub">
                    Manage and monitor student queues efficiently.
                </p>
            </div>
        </div>

        <div class="form-panel">

            <div class="form-logo">
                <img src="img/olfu-logo.png" alt="OLFU Logo">
            </div>

            <div class="form-heading">
                <h1>Sign In</h1>
                <p>Sign in to your account to continue.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token"
                    value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i>Email Address</label>
                    <input type="email" id="email" name="email"
                        required maxlength="254"
                        placeholder="Enter your email address">
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i>Password</label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password"
                            required minlength="8"
                            placeholder="Enter your password">
                        <button type="button" class="pwd-toggle"
                            onclick="togglePwd('password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    Sign In
                </button>
            </form>

        </div>
    </div>

    <script src="scripts/login.js"></script>
</body>

</html>