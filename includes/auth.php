<?php
session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $submitted): bool
{
    $stored = $_SESSION['csrf_token'] ?? '';
    return $stored !== '' && hash_equals($stored, $submitted);
}


function bindSessionFingerprint(): bool
{
    $fp = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['REMOTE_ADDR'] ?? ''));

    if (!isset($_SESSION['fp'])) {
        $_SESSION['fp'] = $fp;
        return true;
    }

    $valid = hash_equals($_SESSION['fp'], $fp);
    if (!$valid) {
        logout();
    }
    return $valid;
}

function validateLoginInputs(string $csrf, string $email, string $password, $db): string
{
    if (!verifyCsrfToken($csrf)) {
        return 'Invalid request. Please refresh and try again.';
    }

    if (empty($email) || empty($password)) {
        return 'Please enter both email and password.';
    }

    return '';
}

function login($db, string $email, string $password, string $csrf): array
{
    $inputError = validateLoginInputs($csrf, $email, $password, $db);
    if ($inputError !== '') {
        return ['success' => false, 'message' => $inputError];
    }

    $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user = fetchUserByEmail($db, $email);

    rehashPasswordIfNeeded($db, $user, $password);
    establishSession($user);

    return ['success' => true, 'message' => ''];
}

function fetchUserByEmail($db, string $email): ?array
{
    $stmt = $db->prepare(
        "SELECT id, full_name, email, password, role FROM users WHERE email = ? LIMIT 1"
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

function rehashPasswordIfNeeded($db, array $user, string $plainPassword): void
{
    $algo    = (int) PASSWORD_HASH_ALGO;
    $options = defined('PASSWORD_HASH_OPTIONS') ? (array) PASSWORD_HASH_OPTIONS : ['cost' => 12];

    if (!password_needs_rehash($user['password'], $algo, $options)) {
        return;
    }

    $newHash = hashPassword($plainPassword);
    $stmt    = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('si', $newHash, $user['id']);
        $stmt->execute();
        $stmt->close();
    }
}

function establishSession(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['full_name']  = $user['full_name'];
    $_SESSION['email']      = $user['email'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['login_time'] = time();

    bindSessionFingerprint();

    unset($_SESSION['csrf_token']);
    getCsrfToken();
}

function validateRegistrationInputs(
    string $csrf,
    string $full_name,
    string $email,
    string $password,
    string $confirm_password
): string {
    if (!verifyCsrfToken($csrf)) {
        return 'Invalid request. Please refresh and try again.';
    }

    if (mb_strlen($full_name) < 3 || mb_strlen($full_name) > 100) {
        return 'Full name must be between 3 and 100 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
        return 'Please enter a valid email address.';
    }

    if (mb_strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }

    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[\d\W]/', $password)) {
        return 'Password must contain at least one letter and one number or symbol.';
    }

    if ($password !== $confirm_password) {
        return 'Passwords do not match.';
    }

    return '';
}

function register($db, string $full_name, string $email, string $password, string $confirm_password, string $csrf): array
{
    $full_name = trim($full_name);
    $email     = strtolower(trim($email));

    $inputError = validateRegistrationInputs($csrf, $full_name, $email, $password, $confirm_password);
    if ($inputError !== '') {
        return ['success' => false, 'message' => $inputError];
    }

    $dbError = insertNewUser($db, $full_name, $email, $password);
    if ($dbError !== '') {
        return ['success' => false, 'message' => $dbError];
    }

    return ['success' => true, 'message' => 'Account created! You can now sign in.'];
}

function insertNewUser($db, string $full_name, string $email, string $password): string
{
    $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    if (!$checkStmt) {
        return 'A database error occurred.';
    }

    $checkStmt->bind_param('s', $email);
    $checkStmt->execute();
    $checkStmt->store_result();
    $exists = $checkStmt->num_rows > 0;
    $checkStmt->close();

    if ($exists) {
        return 'That email is already registered. Please sign in instead.';
    }

    $hashed   = hashPassword($password);
    $insStmt  = $db->prepare(
        "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'admin')"
    );

    if (!$insStmt) {
        return 'A database error occurred.';
    }

    $insStmt->bind_param('sss', $full_name, $email, $hashed);
    $ok = $insStmt->execute();
    $insStmt->close();

    return $ok ? '' : 'Registration failed. Please try again.';
}

function logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'],
            $p['domain'],
            $p['secure'],
            $p['httponly']
        );
    }

    session_destroy();
}

function isLoggedIn(): bool
{
    if (empty($_SESSION['user_id'])) {
        return false;
    }

    if (!bindSessionFingerprint()) {
        return false;
    }

    $timeout  = (int) SESSION_TIMEOUT;
    $timedOut = isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout;

    if ($timedOut) {
        logout();
        return false;
    }

    $_SESSION['login_time'] = time();
    return true;
}

function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id'        => (int) $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'],
        'email'     => $_SESSION['email'],
        'role'      => $_SESSION['role'],
    ];
}

function checkSessionTimeout()
{
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return true;
    }

    if ((time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

function hashPassword(string $password): string
{
    $algo    = defined('PASSWORD_HASH_ALGO')    ? (int) PASSWORD_HASH_ALGO    : PASSWORD_BCRYPT;
    $options = defined('PASSWORD_HASH_OPTIONS') ? (array) PASSWORD_HASH_OPTIONS : ['cost' => 12];

    return password_hash($password, $algo, $options);
}
