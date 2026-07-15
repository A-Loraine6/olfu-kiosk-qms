<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/settings_functions.php';

$conn = $db->getConnection();

$settings = getKioskSettings($conn);

echo json_encode([
    'success'      => true,
    'enabled'      => $settings['kiosk_enabled'],
    'idle_timeout' => $settings['idle_timeout'],
]);
