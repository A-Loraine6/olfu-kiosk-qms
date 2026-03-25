<?php

/**
 * OLFU Queue Management System - Login & Register Page
 */

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

// ── Login ─────────────────────────────────────────────────────────────────────
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

// ── Register ──────────────────────────────────────────────────────────────────
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
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family:
                "DM Sans",
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
            background: var(--primary-dark);
            background-image:
                radial-gradient(ellipse at 12% 0%,
                    rgba(0, 196, 90, 0.2) 0%,
                    transparent 55%),
                radial-gradient(ellipse at 88% 100%,
                    rgba(0, 0, 0, 0.28) 0%,
                    transparent 55%);

            display: flex;
            justify-content: center;
            align-items: flex-start;
            overflow-y: auto;
            padding: 40px 0;
        }

        .form-group {
            text-align: left;
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .auth-card {
            background: var(--bg-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: var(--spacing-xl);
            text-align: center;
        }

        .auth-logo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            margin: 0 auto 12px;
            border: 3px solid rgba(255, 255, 255, 0.4);
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-logo img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
        }

        .auth-card-header {
            margin-bottom: var(--spacing-xl);
        }

        .auth-card-header h1 {
            font-size: 22px;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .auth-card-header p {
            font-size: 13px;
            color: var(--text-light);
            margin: 0;
        }

        /* Password with toggle */
        .input-wrap {
            position: relative;
        }

        .input-wrap input {
            padding-right: 44px;
        }

        .pwd-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-light);
            font-size: 15px;
            padding: 4px;
            transition: color .2s;
        }

        .pwd-toggle:hover {
            color: var(--primary-color);
        }

        /* Strength bar */
        .strength-wrap {
            margin-top: 6px;
            display: none;
        }

        .strength-wrap.visible {
            display: block;
        }

        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: var(--border-color);
            overflow: hidden;
            margin-bottom: 4px;
        }

        .strength-fill {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: width .3s, background .3s;
        }

        .strength-text {
            font-size: 12px;
            color: var(--text-light);
        }

        /* Footer toggle */
        .auth-toggle {
            text-align: center;
            margin-top: var(--spacing-lg);
            font-size: 14px;
            color: var(--text-light);
        }

        .auth-toggle a {
            color: var(--primary-color);
            font-weight: 600;
        }

        .auth-toggle a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .auth-wrap {
                grid-template-columns: 1fr;
            }

            .auth-panel {
                display: none;
            }

            .auth-form-side {
                min-height: 100vh;
                padding: var(--spacing-md);
            }
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: var(--spacing-lg);
            }
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">

            <!-- LOGO ABOVE HEADER -->
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

            <div class="auth-toggle">
                <?php if ($is_register): ?>
                    Already have an account? <a href="login.php">Sign in</a>
                <?php else: ?>
                    Don't have an account? <a href="login.php?register=1">Create one</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function togglePwd(id, btn) {
            const f = document.getElementById(id),
                i = btn.querySelector('i');
            const show = f.type === 'password';
            f.type = show ? 'text' : 'password';
            i.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
        }

        // Strength meter
        (function() {
            const f = document.getElementById('password');
            const sw = document.getElementById('sw');
            if (!f || !sw) return;
            const fill = document.getElementById('sf'),
                lbl = document.getElementById('sl');
            const levels = [{
                    pct: '20%',
                    c: '#d32f2f',
                    t: 'Very weak'
                },
                {
                    pct: '40%',
                    c: '#f57c00',
                    t: 'Weak'
                },
                {
                    pct: '60%',
                    c: '#fbc02d',
                    t: 'Fair'
                },
                {
                    pct: '80%',
                    c: '#388e3c',
                    t: 'Strong'
                },
                {
                    pct: '100%',
                    c: '#1b5e20',
                    t: 'Very strong'
                },
            ];
            f.addEventListener('input', function() {
                const v = this.value;
                if (!v) {
                    sw.classList.remove('visible');
                    return;
                }
                sw.classList.add('visible');
                let s = 0;
                if (v.length >= 8) s++;
                if (v.length >= 12) s++;
                if (/[A-Z]/.test(v)) s++;
                if (/[0-9]/.test(v)) s++;
                if (/[\W_]/.test(v)) s++;
                const l = levels[Math.min(s - 1, 4)] || levels[0];
                fill.style.width = l.pct;
                fill.style.background = l.c;
                lbl.textContent = l.t;
                lbl.style.color = l.c;
            });
        })();
    </script>
</body>

</html>
