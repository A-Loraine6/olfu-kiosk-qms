<?php
declare(strict_types=1);

/**
 * QMS database restore (CLI only, pure PHP: no mysql client or proc_open needed).
 *
 * Usage:
 *   php scripts/restore_database.php <backup-file> --target=<db_name> --confirm
 *        [--settings=/path/to/qms-backup.sh] [--allow-live]
 *
 * The target database must already exist (create an empty one in your hosting
 * panel). Same settings as backup_database.php (QMS_DB_USER, QMS_DB_PASS,
 * QMS_DB_HOST, QMS_BACKUP_KEY). QMS_DB_NAME is treated as the LIVE database and
 * is protected unless you pass --allow-live.
 *
 * Handles backups made by backup_database.php (PHP mode or mysqldump mode).
 * Triggers/routines in mysqldump-mode files are skipped.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

function bail(string $m): never
{
    fwrite(STDERR, $m . PHP_EOL);
    exit(1);
}

$file = $argv[1] ?? '';
$opts = array_slice($argv, 2);
$target = '';
$settings = '';
foreach ($opts as $o) {
    if (strpos($o, '--target=') === 0) {
        $target = substr($o, 9);
    } elseif (strpos($o, '--settings=') === 0) {
        $settings = substr($o, 11);
    }
}
$confirm   = in_array('--confirm', $opts, true);
$allowLive = in_array('--allow-live', $opts, true);

if ($settings !== '') {
    if (!is_file($settings)) bail("Settings file not found: $settings");
    foreach (file($settings, FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^\s*(?:export\s+)?(QMS_[A-Z_]+)=(.*)$/', $line, $m)) {
            $v = trim($m[2]);
            if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
                $v = substr($v, 1, -1);
            }
            putenv($m[1] . '=' . $v);
        }
    }
}

$host = getenv('QMS_DB_HOST') ?: 'localhost';
$port = getenv('QMS_DB_PORT') ?: '3306';
$live = getenv('QMS_DB_NAME') ?: '';
$user = getenv('QMS_DB_USER') ?: '';
$pass = getenv('QMS_DB_PASS') ?: '';
$key  = getenv('QMS_BACKUP_KEY') ?: '';

if ($file === '' || !is_file($file)) bail('Backup file not found.');
if ($user === '') bail('QMS_DB_USER must be set (use --settings=/path/to/qms-backup.sh).');
if (!preg_match('/^[A-Za-z0-9_]+$/', $target)) bail('--target=<db_name> is required (letters, digits, underscore).');
if ($target === $live && !$allowLive) bail("Refusing to restore over live database '$live' without --allow-live.");
if (!$confirm) bail("Dry run: would restore $file into '$target'. Add --confirm to proceed.");

$sql = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'qms_restore_' . bin2hex(random_bytes(4)) . '.sql';

try {
    $data = (string)file_get_contents($file);

    if (substr($file, -4) === '.enc') {
        if ($key === '') throw new RuntimeException('QMS_BACKUP_KEY is required for encrypted backups.');
        if (substr($data, 0, 4) !== 'QMS1') throw new RuntimeException('Unrecognized encrypted file.');
        $iv  = substr($data, 4, 12);
        $tag = substr($data, 16, 16);
        $ct  = substr($data, 32);
        $data = openssl_decrypt($ct, 'aes-256-gcm', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv, $tag);
        if ($data === false) throw new RuntimeException('Decryption failed (wrong key or corrupted file).');
    }
    if (substr($data, 0, 2) === "\x1f\x8b") {
        $data = gzdecode($data);
        if ($data === false) throw new RuntimeException('Decompression failed.');
    }
    file_put_contents($sql, $data);
    unset($data);

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$target;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    $fh = fopen($sql, 'rb');
    $buf = '';
    $count = 0;
    $skipping = false;
    while (($line = fgets($fh)) !== false) {
        $t = rtrim($line);
        if (preg_match('/^DELIMITER\s+(\S+)/i', $t, $m)) {
            $skipping = ($m[1] !== ';');
            $buf = '';
            continue;
        }
        if ($skipping) continue;
        if ($buf === '' && ($t === '' || strncmp($t, '--', 2) === 0)) continue;
        $buf .= $line;
        if (substr($t, -1) === ';') {
            try {
                $pdo->exec($buf);
            } catch (Throwable $e) {
                throw new RuntimeException('Statement failed near: ' . substr(trim($buf), 0, 80) . ' -- ' . $e->getMessage());
            }
            $count++;
            $buf = '';
        }
    }
    fclose($fh);
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    echo "Restored $count statements into '$target'. Verify tables, logins, offices/services and ticket history before use.\n";
} catch (Throwable $ex) {
    bail('FAILED: ' . $ex->getMessage());
} finally {
    @unlink($sql);
}
