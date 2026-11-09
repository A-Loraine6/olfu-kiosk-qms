<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$cfgFile = $argv[1] ?? '';
if ($cfgFile !== '') {
    if (!is_file($cfgFile)) {
        fwrite(STDERR, "Settings file not found: $cfgFile\n");
        exit(1);
    }
    foreach (file($cfgFile, FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^\s*(?:export\s+)?(QMS_[A-Z_]+)=(.*)$/', $line, $m)) {
            $v = trim($m[2]);
            if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
                $v = substr($v, 1, -1);
            }
            putenv($m[1] . '=' . $v);
        }
    }
}

$host    = getenv('QMS_DB_HOST') ?: 'localhost';
$port    = getenv('QMS_DB_PORT') ?: '3306';
$db      = getenv('QMS_DB_NAME') ?: '';
$user    = getenv('QMS_DB_USER') ?: '';
$pass    = getenv('QMS_DB_PASS') ?: '';
$dump    = getenv('QMS_MYSQLDUMP') ?: '';
$dir     = rtrim(getenv('QMS_BACKUP_DIR') ?: '', '\\/');
$key     = getenv('QMS_BACKUP_KEY') ?: '';
$keepDay = (int)(getenv('QMS_KEEP_DAILY_DAYS') ?: 14);
$keepMon = (int)(getenv('QMS_KEEP_MONTHLY_MONTHS') ?: 6);

if ($db === '' || $user === '' || $dir === '') {
    fwrite(STDERR, "QMS_DB_NAME, QMS_DB_USER and QMS_BACKUP_DIR must be set.\n");
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

$logFile = $dir . DIRECTORY_SEPARATOR . 'backup.log';

function logmsg(string $file, string $msg): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND);
    echo $line;
}

/** Pure-PHP dump through PDO. Streams straight into a .gz file. */
function phpDump(string $host, string $port, string $user, string $pass, string $db, string $gzFile): array
{
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
    ]);
    $notes = [];
    $out = gzopen($gzFile, 'wb9');
    if (!$out) {
        throw new RuntimeException('Cannot write ' . $gzFile);
    }
    $w = static function (string $s) use ($out): void {
        gzwrite($out, $s);
    };
    $all = static fn(string $sql, int $mode = PDO::FETCH_NUM) => $pdo->query($sql)->fetchAll($mode);
    $id  = static fn(string $n): string => '`' . str_replace('`', '``', $n) . '`';

    $w("-- QMS PHP dump of `$db` on " . date('c') . "\n");
    $w("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET UNIQUE_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

    $tables = [];
    $views  = [];
    foreach ($all('SHOW FULL TABLES') as $r) {
        if (strtoupper((string)$r[1]) === 'VIEW') {
            $views[] = $r[0];
        } else {
            $tables[] = $r[0];
        }
    }

    foreach ($tables as $t) {
        $q = $id($t);
        $create = $all("SHOW CREATE TABLE $q")[0][1];
        $w("DROP TABLE IF EXISTS $q;\n$create;\n\n");

        $names = [];
        $bin   = [];
        foreach ($all("SHOW COLUMNS FROM $q", PDO::FETCH_ASSOC) as $c) {
            if (stripos((string)$c['Extra'], 'GENERATED') !== false) {
                continue; // generated columns cannot be inserted into
            }
            $names[] = $id($c['Field']);
            $bin[]   = (bool)preg_match('/blob|binary|bit/i', (string)$c['Type']);
        }
        if (!$names) {
            continue;
        }
        $colList = implode(',', $names);

        $st = $pdo->query("SELECT $colList FROM $q");
        $batch = [];
        $size  = 0;
        $flush = static function () use (&$batch, &$size, $w, $q, $colList): void {
            if ($batch) {
                $w("INSERT INTO $q ($colList) VALUES\n" . implode(",\n", $batch) . ";\n");
                $batch = [];
                $size  = 0;
            }
        };
        while (($row = $st->fetch(PDO::FETCH_NUM)) !== false) {
            $vals = [];
            foreach ($row as $i => $v) {
                if ($v === null) {
                    $vals[] = 'NULL';
                } elseif ($bin[$i]) {
                    $vals[] = $v === '' ? "''" : '0x' . bin2hex((string)$v);
                } else {
                    $vals[] = $pdo->quote((string)$v);
                }
            }
            $line = '(' . implode(',', $vals) . ')';
            $batch[] = $line;
            $size += strlen($line);
            if (count($batch) >= 200 || $size > 1000000) {
                $flush();
            }
        }
        $st->closeCursor();
        $flush();
        $w("\n");
    }

    foreach ($views as $v) {
        $q   = $id($v);
        $sql = (string)$all("SHOW CREATE VIEW $q")[0][1];
        $sql = preg_replace('/DEFINER=`[^`]*`@`[^`]*`\s*/', '', $sql);
        $w("DROP VIEW IF EXISTS $q;\n$sql;\n\n");
    }

    // Triggers / routines are not written by this simple dump: warn if any exist.
    try {
        $nt = count($all('SHOW TRIGGERS'));
        $nr = count($all("SHOW PROCEDURE STATUS WHERE Db = " . $pdo->quote($db)))
            + count($all("SHOW FUNCTION STATUS WHERE Db = " . $pdo->quote($db)));
        if ($nt + $nr > 0) {
            $notes[] = "PHP dump does not include $nt trigger(s) and $nr routine(s).";
        }
    } catch (Throwable $e) {
        // not enough privileges to check; ignore
    }

    $w("SET FOREIGN_KEY_CHECKS=1;\n-- Dump completed on " . date('c') . "\n");
    gzclose($out);
    return $notes;
}

$stamp   = date('Ymd_His');
$sqlFile = "$dir/qms_{$db}_{$stamp}.sql";
$gzFile  = $sqlFile . '.gz';
$cnf     = "$dir/.my_{$stamp}.cnf";

try {
    $native = $dump !== '' && function_exists('proc_open') && is_file($dump);

    if ($native) {
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
            throw new RuntimeException('Could not start mysqldump.');
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
        $in  = fopen($sqlFile, 'rb');
        $out = gzopen($gzFile, 'wb9');
        while (!feof($in)) {
            gzwrite($out, fread($in, 1 << 20));
        }
        fclose($in);
        gzclose($out);
        unlink($sqlFile);
        $mode = 'mysqldump';
    } else {
        foreach (phpDump($host, $port, $user, $pass, $db, $gzFile) as $note) {
            logmsg($logFile, 'NOTE: ' . $note);
        }
        $mode = 'php';
    }

    // Verify: the dump must be readable and end with its completion marker.
    $h = gzopen($gzFile, 'rb');
    if (!$h) {
        throw new RuntimeException('Cannot re-open the backup to verify it.');
    }
    $last = '';
    $total = 0;
    while (!gzeof($h)) {
        $chunk = (string)gzread($h, 1 << 20);
        $total += strlen($chunk);
        $last = substr($last . $chunk, -300);
    }
    gzclose($h);
    if ($total < 100 || strpos($last, 'Dump completed') === false) {
        throw new RuntimeException('Backup failed verification (incomplete).');
    }

    $final = $gzFile;
    if ($key !== '') {
        $iv  = random_bytes(12);
        $tag = '';
        $ct  = openssl_encrypt((string)file_get_contents($gzFile), 'aes-256-gcm', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv, $tag);
        if ($ct === false) {
            throw new RuntimeException('Encryption failed.');
        }
        $final = $gzFile . '.enc';
        file_put_contents($final, 'QMS1' . $iv . $tag . $ct);
        unlink($gzFile);
    }

    // Keep a monthly copy (first successful backup of each month)
    if (!glob("$dir/monthly/qms_{$db}_" . date('Ym') . '*')) {
        copy($final, "$dir/monthly/" . basename($final));
    }

    logmsg($logFile, "OK ($mode): " . basename($final) . ' (' . number_format((int)filesize($final)) . ' bytes)');
} catch (Throwable $e) {
    @unlink($sqlFile);
    @unlink($gzFile);
    logmsg($logFile, 'FAILED: ' . $e->getMessage());
    exit(1);
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
