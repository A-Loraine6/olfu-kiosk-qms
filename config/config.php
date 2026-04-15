<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'qms_db');
define('DB_PORT', 3306);

define('APP_NAME', 'Queue Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/olfu-kiosk-qms/');

define('SESSION_TIMEOUT', 3600);
define('SESSION_NAME', 'olfu_queue_session');

define('LOCKOUT_WINDOW', 900);  
define('MAX_LOGIN_ATTEMPTS', 5); 

define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_OPTIONS', array('cost' => 12));

date_default_timezone_set('Asia/Manila');
