<?php
$configCandidates = [
    dirname(__DIR__, 2) . '/config/config.php',
    dirname(__DIR__) . '/config/config.php',
];

foreach ($configCandidates as $configPath) {
    if (is_file($configPath)) {
        require_once $configPath;
        break;
    }
}

if (!defined('DB_HOST')) {
    http_response_code(500);
    exit('Application configuration is unavailable.');
}
