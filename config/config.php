<?php

/**
 * OLFU Queue Management System - Configuration File
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'qms_db');
define('DB_PORT', 3306);

// Application Configuration
define('APP_NAME', 'OLFU Queue Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/olfu-kiosk-qms/');

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('SESSION_NAME', 'olfu_queue_session');

define('LOCKOUT_WINDOW', 900);  
define('MAX_LOGIN_ATTEMPTS', 5); 

// Security Configuration
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_OPTIONS', array('cost' => 12));

// Offices Array
$OFFICES = array(
    array('id' => 1, 'name' => 'Registrar', 'code' => 'REG'),
    array('id' => 2, 'name' => 'Accounting', 'code' => 'ACC'),
    array('id' => 3, 'name' => 'Admissions', 'code' => 'ADM')
);
