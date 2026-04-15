<?php
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'api/activity_logger.php';

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
