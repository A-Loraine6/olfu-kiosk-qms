<?php
require_once __DIR__ . '/includes/config_loader.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/request_validation.php';
require_once 'api/activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken(requestString($_POST, 'csrf_token', 255, true) ?? '')) {
    http_response_code(405);
    exit('Invalid logout request.');
}

logActivity(
    $db,
    'LOGOUT',
    'user',
    getCurrentUser()['id'] ?? null,
    'User logged out'
);

logout();

header('Location: login.php');
exit;
