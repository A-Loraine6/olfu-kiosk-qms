<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$host    = getenv('QMS_DB_HOST') ?: '127.0.0.1';
$port    = getenv('QMS_DB_PORT') ?: '3306';
$db      = getenv('QMS_DB_NAME') ?: '';
$user    = getenv('QMS_DB_USER') ?: '';
$pass    = getenv('QMS_DB_PASS') ?: '';
$dump    = getenv('QMS_MYSQLDUMP') ?: 'D:\\xampp\\mysql\\bin\\mysqldump.exe';
$dir     = rtrim(getenv('QMS_BACKUP_DIR') ?: 'D:\\qms-backups', '\\/');
$key     = getenv('QMS_BACKUP_KEY') ?: '';
$keepDay = (int)(getenv('QMS_KEEP_DAILY_DAYS') ?: 14);
$keepMon = (int)(getenv('QMS_KEEP_MONTHLY_MONTHS') ?: 6);

$logFile = $dir . DIRECTORY_SEPARATOR . 'backup.log';

function logmsg(string $file, string $msg): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND);
    echo $line;
}

function fail(string $file, string $msg): never
{
    logmsg($file, 'FAILED: ' . $msg);
    exit(1);
}

if ($db === '' || $user === '') {
    fwrite(STDERR, "QMS_DB_NAME and QMS_DB_USER must be set explicitly.\n");
    exit(1);
}
if (!preg_match('/^[A-Za-z0-9_]+$/', $db)) {
    fwrite(STDERR, "Invalid database name.\n");
    exit(1);
}
if (!is_dir($dir) && !mkdir($dir, 0700, true)) {
    fwrite(STDERR, "Cannot create backup dir: $dir\n");
    exit(1);
}
@mkdir($dir . DIRECTORY_SEPARATOR . 'monthly', 0700, true);

$stamp   = date('Ymd_His');
$sqlFile = "$dir/qms_{$db}_{$stamp}.sql";
$gzFile  = $sqlFile . '.gz';
$cnf     = "$dir/.my_{$stamp}.cnf";

try {
    // Credentials go in a temp option file so they never appear on the command line.
    $esc = static fn(string $v): string => '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $v) . '"';
    file_put_contents($cnf, "[client]\nuser={$esc($user)}\npassword={$esc($pass)}\nhost={$esc($host)}\nport=$port\n");
    @chmod($cnf, 0600);

    $cmd = [
        $dump, "--defaults-extra-file=$cnf",
        '--single-transaction', '--routines', '--triggers', '--events',
        '--default-character-set=utf8mb4',
        "--result-file=$sqlFile", $db,
    ];
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($proc)) {
        throw new RuntimeException('Could not start mysqldump at ' . $dump);
    }
    stream_get_contents($pipes[1]);
    $err  = stream_get_contents($pipes[2]);
    $code = proc_close($proc);

    if ($code !== 0) {
        throw new RuntimeException("mysqldump exit code $code: " . trim($err));
    }
    if (!is_file($sqlFile) || filesize($sqlFile) < 100) {
        throw new RuntimeException('Dump file missing or empty.');
    }
    // mysqldump writes this marker only when the dump finished completely.
    $tail = file_get_contents($sqlFile, false, null, max(0, filesize($sqlFile) - 200));
    if (strpos((string)$tail, 'Dump completed') === false) {
        throw new RuntimeException('Dump appears incomplete (no completion marker).');
    }

    // Compress
    $in  = fopen($sqlFile, 'rb');
    $out = gzopen($gzFile, 'wb9');
    while (!feof($in)) {
        gzwrite($out, fread($in, 1 << 20));
    }
    fclose($in);
    gzclose($out);
    unlink($sqlFile);
    $final = $gzFile;

    // Optional encryption
    if ($key !== '') {
        $raw = hash('sha256', $key, true);
        $iv  = random_bytes(12);
        $tag = '';
        $ct  = openssl_encrypt(file_get_contents($gzFile), 'aes-256-gcm', $raw, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ct === false) {
            throw new RuntimeException('Encryption failed.');
        }
        $final = $gzFile . '.enc';
        file_put_contents($final, 'QMS1' . $iv . $tag . $ct);
        unlink($gzFile);
    }

    // Keep a monthly copy (first successful backup of each month)
    $month = date('Ym');
    if (!glob("$dir/monthly/qms_{$db}_{$month}*")) {
        copy($final, "$dir/monthly/" . basename($final));
    }

    logmsg($logFile, 'OK: ' . basename($final) . ' (' . number_format(filesize($final)) . ' bytes)');
} catch (Throwable $e) {
    @unlink($sqlFile);
    @unlink($gzFile);
    fail($logFile, $e->getMessage());
} finally {
    @unlink($cnf);
}

// Retention
$prune = static function (string $pattern, int $maxAgeSec) use ($logFile): void {
    foreach (glob($pattern) ?: [] as $f) {
        if (is_file($f) && filemtime($f) < time() - $maxAgeSec) {
            unlink($f);
            logmsg($logFile, 'Pruned ' . basename($f));
        }
    }
};
$prune("$dir/qms_{$db}_*", $keepDay * 86400);
$prune("$dir/monthly/qms_{$db}_*", $keepMon * 31 * 86400);

exit(0);