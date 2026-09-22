<?php
require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/request_validation.php';
require_once __DIR__ . '/activity_logger.php';

if (!isLoggedIn()) {
    logAuthDenied($db->getConnection(), 'Unauthenticated report export attempt', null, 'report');
    header('Location: ../login.php');
    exit;
}
if (!verifyCsrfToken(requestString($_POST, 'csrf_token', 255, true) ?? '')) {
    logSuspiciousApiActivity($db->getConnection(), 'Invalid CSRF token during PDF export', (int) ($_SESSION['user_id'] ?? 0), 'report');
    http_response_code(403);
    die('Invalid CSRF token.');
}

$currentUser = getCurrentUser();
$isFullView   = ($_SESSION['role_id'] ?? null) === 1
    || ($_SESSION['role'] ?? '') === 'admin'
    || hasPermission('view_reports');
$isScopedView = !$isFullView && hasPermission('view_reports_office');
$isStaff      = !$isFullView && !$isScopedView && hasPermission('view_own_metrics');

$userId     = (int) $currentUser['id'];
$userOffice = (int) ($currentUser['office_id'] ?? 0);

$from = requestDate($_POST, 'date_from', date('Y-m-d'));
$to   = requestDate($_POST, 'date_to', date('Y-m-d'));
if ($from === null || $to === null || $from > $to) { http_response_code(400); die('Invalid date range.'); }

if ($isScopedView) {
    $officeId = $userOffice;
} else {
    $officeId = requestOptionalInt($_POST, 'office_id') ?? 0;
}

$conn = $db->getConnection();


function fmtDate(string $d): string
{
    return date('F j, Y', strtotime($d));
}
function fmtTime(?float $secs): string
{
    if ($secs === null || $secs <= 0) return '-';
    $m = (int) floor($secs / 60);
    $s = (int) round($secs % 60);
    return $s > 0 ? "{$m}m {$s}s" : "{$m}m";
}
function fmtTs(?string $ts): string
{
    if (!$ts) return '-';
    return date('g:i A', strtotime($ts));
}

foreach (
    [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../vendor/setasign/fpdf/fpdf.php',
        __DIR__ . '/lib/fpdf/fpdf.php',
    ] as $p
) {
    if (file_exists($p)) {
        require_once $p;
        break;
    }
}
if (!class_exists('FPDF')) {
    http_response_code(500);
    die('FPDF not found. Run: composer require setasign/fpdf');
}

class BaseReport extends FPDF
{
    public string $rTitle = '';
    public string $rSub   = '';
    public string $rRange = '';
    public string $rGenAt = '';

    const GRN = [0, 139, 67];
    const DRK = [0, 70, 35];
    const GRY = [246, 249, 247];
    const TXT = [28, 42, 33];
    const MUT = [118, 148, 128];
    const ORG = [210, 85, 15];
    const BLU = [26, 100, 170];
    const PRP = [80, 55, 175];

    function Header(): void
    {
        $this->SetFillColor(...self::GRN);
        $this->Rect(0, 0, 210, $this->rSub ? 23 : 19, 'F');
        $this->SetFont('Helvetica', 'B', 11.5);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(10, $this->rSub ? 3.5 : 4.5);
        $this->Cell(0, 6, $this->rTitle, 0, 1, 'C');
        if ($this->rSub) {
            $this->SetFont('Helvetica', '', 8);
            $this->Cell(0, 4, $this->rSub, 0, 1, 'C');
        }
        $this->SetFont('Helvetica', '', 7);
        $this->Cell(0, 4, $this->rRange, 0, 1, 'C');
        $this->SetTextColor(...self::TXT);
        $this->SetY($this->rSub ? 26 : 22);
    }

    function Footer(): void
    {
        $this->SetY(-9);
        $this->SetDrawColor(...self::GRN);
        $this->SetLineWidth(0.3);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->SetLineWidth(0.2);
        $this->SetFont('Helvetica', 'I', 6);
        $this->SetTextColor(...self::MUT);
        $this->SetY($this->GetY() + 1.5);
        $this->Cell(95, 3.5, 'Generated ' . $this->rGenAt . ' (PHT)', 0, 0, 'L');
        $this->Cell(95, 3.5, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'R');
    }

    function SecTitle(string $t): void
    {
        $y = $this->GetY();
        $this->SetFont('Helvetica', 'B', 7.5);
        $this->SetTextColor(...self::DRK);
        $this->SetXY(10, $y);
        $this->Cell(0, 5.5, strtoupper($t), 0, 1);
        $this->SetX(10);
        $this->SetTextColor(...self::TXT);
        $this->Ln(1);
    }

    function StatCards(array $cards, float $x0 = 10, float $totalW = 190): void
    {
        $n   = count($cards);
        $gap = 2.5;
        $w   = ($totalW - $gap * ($n - 1)) / $n;
        $y0  = $this->GetY();

        foreach ($cards as $i => $c) {
            $x = $x0 + $i * ($w + $gap);
            $this->SetFillColor(...self::GRY);
            $this->RRect($x, $y0, $w, 19, 1.8);
            $this->SetFillColor(...($c['clr'] ?? self::GRN));
            $this->Rect($x, $y0, $w, 1.2, 'F');
            $this->SetFont('Helvetica', '', 6);
            $this->SetTextColor(...self::MUT);
            $this->SetXY($x + 2, $y0 + 2.5);
            $this->Cell($w - 4, 3, $c['lbl'], 0, 1);
            $this->SetFont('Helvetica', 'B', 12);
            $this->SetTextColor(...self::TXT);
            $this->SetXY($x + 2, $y0 + 5.5);
            $this->Cell($w - 4, 6, $c['val'], 0, 1);
            $this->SetFont('Helvetica', '', 5.5);
            $this->SetTextColor(...self::MUT);
            $this->SetXY($x + 2, $y0 + 12);
            $this->Cell($w - 4, 3.5, $c['sub'] ?? '', 0, 1);
        }
        $this->SetXY($x0, $y0 + 21);
        $this->SetTextColor(...self::TXT);
    }

    function THead(array $cols): void
    {
        $this->SetFont('Helvetica', 'B', 7);
        $this->SetFillColor(...self::GRN);
        $this->SetTextColor(255, 255, 255);
        foreach ($cols as [$l, $w, $a]) $this->Cell($w, 5.5, $l, 0, 0, $a, true);
        $this->Ln();
        $this->SetTextColor(...self::TXT);
    }

    function TRow(array $cells, bool $shade): void
    {
        $this->SetFont('Helvetica', '', 7);
        $this->SetFillColor(...($shade ? self::GRY : [255, 255, 255]));
        foreach ($cells as [$v, $w, $a]) $this->Cell($w, 5.2, (string) $v, 0, 0, $a, true);
        $this->Ln();
    }

    function RRect(float $x, float $y, float $w, float $h, float $r): void
    {
        $a  = 4 / 3 * (sqrt(2) - 1);
        $hp = $this->h;
        $k  = $this->k;
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        $this->_Ar($xc + $r * $a, $yc - $r, $xc + $r, $yc - $r * $a, $xc + $r, $yc);
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->_Ar($xc + $r, $yc + $r * $a, $xc + $r * $a, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->_Ar($xc - $r * $a, $yc + $r, $xc - $r, $yc + $r * $a, $xc - $r, $yc);
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
        $this->_Ar($xc - $r, $yc - $r * $a, $xc - $r * $a, $yc - $r, $xc, $yc - $r);
        $this->_out('f');
    }

    function _Ar(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
    {
        $h = $this->h;
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            $x1 * $this->k,
            ($h - $y1) * $this->k,
            $x2 * $this->k,
            ($h - $y2) * $this->k,
            $x3 * $this->k,
            ($h - $y3) * $this->k
        ));
    }
}

$genAt     = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('F j, Y \a\t g:i A');
$dateLabel = $from === $to ? fmtDate($from) : fmtDate($from) . ' - ' . fmtDate($to);

$reportType = $isStaff ? 'staff_report' : ($isScopedView ? 'office_report' : 'admin_report');

if ($isStaff) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS served,
               AVG(CASE WHEN served_at IS NOT NULL AND completed_at IS NOT NULL
                   THEN COALESCE(time_spent,0) + TIMESTAMPDIFF(SECOND,served_at,completed_at)
                   END) AS avg_s
        FROM tickets
        WHERE served_by = ? AND status = 'completed'
          AND DATE(created_at) BETWEEN ? AND ?");
    $stmt->bind_param('iss', $userId, $from, $to);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    $stmt2 = $conn->prepare("
        SELECT COUNT(*) AS skipped FROM tickets
        WHERE served_by = ? AND status = 'skipped'
          AND DATE(created_at) BETWEEN ? AND ?");
    $stmt2->bind_param('iss', $userId, $from, $to);
    $stmt2->execute();
    $skippedCount = (int) $stmt2->get_result()->fetch_assoc()['skipped'];

    $stmt3 = $conn->prepare("
        SELECT ticket_number, transaction_type, status,
               created_at, served_at, completed_at,
               COALESCE(time_spent,0) AS time_spent
        FROM tickets
        WHERE served_by = ?
          AND DATE(created_at) BETWEEN ? AND ?
          AND status IN ('completed','skipped','on-hold')
        ORDER BY COALESCE(served_at, created_at) ASC");
    $stmt3->bind_param('iss', $userId, $from, $to);
    $stmt3->execute();
    $tickets = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt4 = $conn->prepare("SELECT full_name, window_number FROM users WHERE id = ?");
    $stmt4->bind_param('i', $userId);
    $stmt4->execute();
    $staffInfo = $stmt4->get_result()->fetch_assoc();

    $stmtB = $conn->prepare("
        SELECT transaction_type, COUNT(*) AS cnt
        FROM tickets
        WHERE served_by = ? AND status = 'completed'
        AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY transaction_type
        ORDER BY cnt DESC");
    $stmtB->bind_param('iss', $userId, $from, $to);
    $stmtB->execute();
    $breakdownRows = $stmtB->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt5 = $conn->prepare("SELECT name FROM offices WHERE id = ?");
    $stmt5->bind_param('i', $userOffice);
    $stmt5->execute();
    $officeName = $stmt5->get_result()->fetch_assoc()['name'] ?? 'Unknown Office';

    $served = (int) ($stats['served'] ?? 0);
    $avgS   = $stats['avg_s'] !== null ? (float) $stats['avg_s'] : null;
    $total  = $served + $skippedCount;
    $rate   = $total > 0 ? round($served / $total * 100, 1) . '%' : 'N/A';
    

    $pdf = new BaseReport('P', 'mm', 'A4');
    $pdf->AliasNbPages();
    $pdf->SetAutoPageBreak(true, 14);
    $pdf->SetMargins(10, 28, 10);
    $pdf->rTitle = 'Staff Activity Report';
    $pdf->rSub   = $staffInfo['full_name'] ?? 'Staff';
    $pdf->rRange = $officeName . '   |   ' . $dateLabel
        . ($staffInfo['window_number'] ? '   |   Window ' . $staffInfo['window_number'] : '');
    $pdf->rGenAt = $genAt;

    $pdf->AddPage();

    $pdf->SecTitle('Performance Summary');
    $pdf->StatCards([
        ['lbl' => 'Tickets Served',  'val' => (string) $served,       'sub' => 'Completed transactions', 'clr' => BaseReport::GRN],
        ['lbl' => 'Tickets Skipped', 'val' => (string) $skippedCount,  'sub' => 'No-show / skipped',      'clr' => BaseReport::ORG],
        ['lbl' => 'Avg. Serve Time', 'val' => fmtTime($avgS),          'sub' => 'Per completed ticket',   'clr' => BaseReport::BLU],
        ['lbl' => 'Completion Rate', 'val' => $rate,                   'sub' => 'Served / total called',  'clr' => BaseReport::PRP],
    ]);

    $pdf->Ln(4);

    $pdf->SecTitle('Services Handled');
    if (empty($breakdownRows)) {
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->SetTextColor(...BaseReport::MUT);
        $pdf->Cell(0, 8, 'No completed transactions for this period.', 0, 1, 'C');
        $pdf->SetTextColor(...BaseReport::TXT);
    } else {
        $pdf->THead([
            ['Service Type', 145, 'L'],
            ['Completed',     45, 'C'],
        ]);
        foreach ($breakdownRows as $i => $row) {
            $pdf->TRow([
                [$row['transaction_type'] ?: 'General Queue', 145, 'L'],
                [(int) $row['cnt'],                             45, 'C'],
            ], $i % 2 === 1);
        }
    }
    $pdf->Ln(4);

    $pdf->SecTitle('Ticket Log');
    if (empty($tickets)) {
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->SetTextColor(...BaseReport::MUT);
        $pdf->Cell(0, 8, 'No tickets found for this period.', 0, 1, 'C');
        $pdf->SetTextColor(...BaseReport::TXT);
    } else {
        $pdf->THead([
            ['Ticket #',         25, 'L'],
            ['Transaction Type', 55, 'L'],
            ['Time Called',      30, 'C'],
            ['Completed',        30, 'C'],
            ['Duration',         25, 'C'],
            ['Status',           25, 'C'],
        ]);
        foreach ($tickets as $i => $t) {
            $dur = null;
            if ($t['served_at'] && $t['completed_at']) {
                $dur = (int) $t['time_spent']
                    + (strtotime($t['completed_at']) - strtotime($t['served_at']));
            } elseif ($t['time_spent'] > 0) {
                $dur = (int) $t['time_spent'];
            }
            $pdf->TRow([
                [$t['ticket_number'],           25, 'L'],
                [$t['transaction_type'] ?? '-', 55, 'L'],
                [fmtTs($t['served_at']),        30, 'C'],
                [fmtTs($t['completed_at']),     30, 'C'],
                [fmtTime($dur),                 25, 'C'],
                [ucfirst($t['status']),         25, 'C'],
            ], $i % 2 === 1);
        }
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetFillColor(...BaseReport::DRK);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(80, 5.5, 'Total: ' . count($tickets) . ' tickets', 0, 0, 'L', true);
        $pdf->Cell(60, 5.5, '', 0, 0, 'C', true);
        $pdf->Cell(25, 5.5, '', 0, 0, 'C', true);
        $pdf->Cell(25, 5.5, $served . ' completed', 0, 1, 'C', true);
        $pdf->SetTextColor(...BaseReport::TXT);
    }

    $fn = 'Staff_Report_' . ($staffInfo['full_name'] ?? 'Staff') . '_' . $from
        . ($from !== $to ? '_to_' . $to : '') . '.pdf';
    $fn = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $fn);
    logDataExport($conn, 'Exported PDF office report for ' . ($officeName ?? 'office'), $userId, 'report', $officeId);
    logDataExport($conn, 'Exported PDF staff report for ' . ($staffInfo['full_name'] ?? 'Staff'), $userId, 'report', $userId);
    $pdf->Output('D', $fn);
    exit;
}

if ($isScopedView) {
    $ow = " AND office_id = {$officeId}";

    $stmtON = $conn->prepare("SELECT name, open_time, close_time FROM offices WHERE id = ?");
    $stmtON->bind_param('i', $officeId);
    $stmtON->execute();
    $officeRow  = $stmtON->get_result()->fetch_assoc();
    $officeName = $officeRow['name'] ?? 'My Office';
    $openHour   = (int) explode(':', $officeRow['open_time']  ?? '08:00:00')[0];
    $closeHour  = (int) explode(':', $officeRow['close_time'] ?? '17:00:00')[0];

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS tq,
               COUNT(CASE WHEN status='completed' THEN 1 END) AS ts,
               COUNT(CASE WHEN status='skipped'   THEN 1 END) AS tsk,
               AVG(CASE WHEN served_at IS NOT NULL AND completed_at IS NOT NULL
                   THEN COALESCE(time_spent,0) + TIMESTAMPDIFF(SECOND,served_at,completed_at) END) AS avg_s
        FROM tickets
        WHERE DATE(created_at) BETWEEN ? AND ? $ow");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $daily = $stmt->get_result()->fetch_assoc();

    $stmt2 = $conn->prepare("
        SELECT u.full_name, u.window_number,
               COUNT(CASE WHEN t.status='completed' THEN 1 END) AS served,
               COUNT(CASE WHEN t.status='skipped'   THEN 1 END) AS skipped,
               AVG(CASE WHEN t.served_at IS NOT NULL AND t.completed_at IS NOT NULL
                   THEN COALESCE(t.time_spent,0) + TIMESTAMPDIFF(SECOND,t.served_at,t.completed_at) END) AS avg_s
        FROM users u
        LEFT JOIN tickets t ON t.served_by = u.id AND DATE(t.created_at) BETWEEN ? AND ?
        WHERE u.role = 'staff' AND u.is_deleted = 0 AND u.is_active = 1
          AND u.office_id = ?
        GROUP BY u.id, u.full_name, u.window_number
        ORDER BY served DESC, u.full_name ASC");
    $stmt2->bind_param('ssi', $from, $to, $officeId);
    $stmt2->execute();
    $staffRows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt3 = $conn->prepare("
        SELECT transaction_type,
               COUNT(*) AS total,
               COUNT(CASE WHEN status='completed' THEN 1 END) AS completed,
               COUNT(CASE WHEN status='skipped'   THEN 1 END) AS skipped,
               AVG(CASE WHEN served_at IS NOT NULL AND completed_at IS NOT NULL
                   THEN COALESCE(time_spent,0) + TIMESTAMPDIFF(SECOND,served_at,completed_at) END) AS avg_s
        FROM tickets
        WHERE DATE(created_at) BETWEEN ? AND ? $ow
          AND transaction_type IS NOT NULL
        GROUP BY transaction_type ORDER BY total DESC");
    $stmt3->bind_param('ss', $from, $to);
    $stmt3->execute();
    $typeRows = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt4 = $conn->prepare("
        SELECT HOUR(created_at) AS hr, COUNT(*) AS cnt
        FROM tickets
        WHERE DATE(created_at) BETWEEN ? AND ? $ow
          AND HOUR(created_at) BETWEEN ? AND ?
        GROUP BY HOUR(created_at) ORDER BY hr ASC");
    $stmt4->bind_param('ssii', $from, $to, $openHour, $closeHour);
    $stmt4->execute();
    $hourlyRows = $stmt4->get_result()->fetch_all(MYSQLI_ASSOC);
    $hourlyMap  = array_column($hourlyRows, 'cnt', 'hr');

    $total  = (int) ($daily['tq']  ?? 0);
    $served = (int) ($daily['ts']  ?? 0);
    $skip   = (int) ($daily['tsk'] ?? 0);
    $avgS   = $daily['avg_s'] !== null ? (float) $daily['avg_s'] : null;
    $rate   = $total > 0 ? round($served / $total * 100, 1) . '%' : 'N/A';

    $pdf = new BaseReport('P', 'mm', 'A4');
    $pdf->AliasNbPages();
    $pdf->SetAutoPageBreak(true, 14);
    $pdf->SetMargins(10, 28, 10);
    $pdf->rTitle = 'Office Performance Report';
    $pdf->rSub   = $officeName;
    $pdf->rRange = $dateLabel;
    $pdf->rGenAt = $genAt;

    $pdf->AddPage();

    $pdf->SecTitle('Office Summary');
    $pdf->StatCards([
        ['lbl' => 'Total Queued',    'val' => (string) $total,  'sub' => 'Students in queue',      'clr' => BaseReport::GRN],
        ['lbl' => 'Total Served',    'val' => (string) $served, 'sub' => 'Completed transactions', 'clr' => BaseReport::GRN],
        ['lbl' => 'Total Skipped',   'val' => (string) $skip,   'sub' => 'No-show / skipped',      'clr' => BaseReport::ORG],
        ['lbl' => 'Avg. Serve Time', 'val' => fmtTime($avgS),   'sub' => 'Per completed ticket',   'clr' => BaseReport::BLU],
        ['lbl' => 'Completion Rate', 'val' => $rate,            'sub' => 'Served / total queued',  'clr' => BaseReport::PRP],
    ]);

    $pdf->Ln(4);

    $pdf->SecTitle('Staff Performance — ' . $officeName);
    if (empty($staffRows)) {
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->SetTextColor(...BaseReport::MUT);
        $pdf->Cell(0, 8, 'No staff data for this period.', 0, 1, 'C');
        $pdf->SetTextColor(...BaseReport::TXT);
    } else {
        $pdf->THead([
            ['Name',       75, 'L'],
            ['Window',     20, 'C'],
            ['Served',     22, 'C'],
            ['Skipped',    22, 'C'],
            ['Avg. Time',  30, 'C'],
            ['Comp. Rate', 21, 'C'],
        ]);
        foreach ($staffRows as $i => $row) {
            $s   = (int) $row['served'];
            $sk  = (int) $row['skipped'];
            $tot = $s + $sk;
            $cr  = $tot > 0 ? round($s / $tot * 100, 1) . '%' : 'N/A';
            $win = $row['window_number'] ? 'W' . $row['window_number'] : '-';
            $pdf->TRow([
                [$row['full_name'],      75, 'L'],
                [$win,                   20, 'C'],
                [$s,                     22, 'C'],
                [$sk,                    22, 'C'],
                [fmtTime($row['avg_s']), 30, 'C'],
                [$cr,                    21, 'C'],
            ], $i % 2 === 1);
        }
    }

    $pdf->Ln(5);

    $pdf->SecTitle('Transaction Type Breakdown');
    if (empty($typeRows)) {
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->SetTextColor(...BaseReport::MUT);
        $pdf->Cell(0, 8, 'No transaction type data for this period.', 0, 1, 'C');
        $pdf->SetTextColor(...BaseReport::TXT);
    } else {
        $pdf->THead([
            ['Transaction Type', 65, 'L'],
            ['Total',            20, 'C'],
            ['Completed',        25, 'C'],
            ['Skipped',          25, 'C'],
            ['Avg. Time',        30, 'C'],
            ['Comp. Rate',       25, 'C'],
        ]);
        foreach ($typeRows as $i => $row) {
            $tot = (int) $row['total'];
            $com = (int) $row['completed'];
            $cr  = $tot > 0 ? round($com / $tot * 100, 1) . '%' : 'N/A';
            $pdf->TRow([
                [$row['transaction_type'] ?? 'Unspecified', 65, 'L'],
                [$tot,                   20, 'C'],
                [$com,                   25, 'C'],
                [(int) $row['skipped'],  25, 'C'],
                [fmtTime($row['avg_s']), 30, 'C'],
                [$cr,                    25, 'C'],
            ], $i % 2 === 1);
        }
    }

    $pdf->Ln(5);

    $openLabel  = date('g A', mktime($openHour, 0));
    $closeLabel = date('g A', mktime($closeHour, 0));
    $pdf->SecTitle("Hourly Queue Volume ({$openLabel} - {$closeLabel})");

    $pdf->THead([
        ['Hour', 95, 'C'],
        ['Queue Count', 95, 'C'],
    ]);

    for ($h = $openHour; $h <= $closeHour; $h++) {
        $pdf->TRow([
            [date('g A', mktime($h, 0)), 95, 'C'],
            [(string) ($hourlyMap[$h] ?? 0), 95, 'C'],
        ], $h % 2 === 0);
    }

    $fn = 'Office_Report_' . preg_replace('/\s+/', '_', $officeName)
        . '_' . $from . ($from !== $to ? '_to_' . $to : '') . '.pdf';
    $fn = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $fn);
    $pdf->Output('D', $fn);
    exit;
}

$ow = $officeId > 0 ? " AND office_id = {$officeId}" : '';

$stmt = $conn->prepare("
    SELECT COUNT(*) AS tq,
           COUNT(CASE WHEN status='completed' THEN 1 END) AS ts,
           COUNT(CASE WHEN status='skipped'   THEN 1 END) AS tsk,
           AVG(CASE WHEN served_at IS NOT NULL AND completed_at IS NOT NULL
               THEN COALESCE(time_spent,0) + TIMESTAMPDIFF(SECOND,served_at,completed_at) END) AS avg_s
    FROM tickets
    WHERE DATE(created_at) BETWEEN ? AND ? $ow");
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$daily = $stmt->get_result()->fetch_assoc();

$stmt2 = $conn->prepare("
    SELECT o.id, o.name,
           COUNT(t.id) AS total,
           COUNT(CASE WHEN t.status='completed' THEN 1 END) AS served,
           COUNT(CASE WHEN t.status='skipped'   THEN 1 END) AS skipped,
           AVG(CASE WHEN t.served_at IS NOT NULL AND t.completed_at IS NOT NULL
               THEN COALESCE(t.time_spent,0) + TIMESTAMPDIFF(SECOND,t.served_at,t.completed_at) END) AS avg_s,
           ROUND(COUNT(t.id)*100.0 /
               NULLIF((SELECT COUNT(*) FROM tickets WHERE DATE(created_at) BETWEEN ? AND ? $ow),0),1) AS share
    FROM offices o
    LEFT JOIN tickets t ON o.id = t.office_id AND DATE(t.created_at) BETWEEN ? AND ?
    " . ($officeId > 0 ? "WHERE o.id = {$officeId}" : '') . "
    GROUP BY o.id, o.name ORDER BY total DESC");
$stmt2->bind_param('ssss', $from, $to, $from, $to);
$stmt2->execute();
$offices = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

$staffWhere = $officeId > 0 ? "AND u.office_id = {$officeId}" : '';
$stmt3 = $conn->prepare("
    SELECT u.full_name, u.window_number, o.name AS office_name,
           COUNT(CASE WHEN t.status='completed' THEN 1 END) AS served,
           COUNT(CASE WHEN t.status='skipped'   THEN 1 END) AS skipped,
           AVG(CASE WHEN t.served_at IS NOT NULL AND t.completed_at IS NOT NULL
               THEN COALESCE(t.time_spent,0) + TIMESTAMPDIFF(SECOND,t.served_at,t.completed_at) END) AS avg_s
    FROM users u
    LEFT JOIN offices o ON o.id = u.office_id
    LEFT JOIN tickets t ON t.served_by = u.id AND DATE(t.created_at) BETWEEN ? AND ?
    WHERE u.role = 'staff' AND u.is_deleted = 0 AND u.is_active = 1
    $staffWhere
    GROUP BY u.id, u.full_name, u.window_number, o.name
    ORDER BY served DESC, u.full_name ASC");
$stmt3->bind_param('ss', $from, $to);
$stmt3->execute();
$staffRows = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt4 = $conn->prepare("
    SELECT transaction_type,
           COUNT(*) AS total,
           COUNT(CASE WHEN status='completed' THEN 1 END) AS completed,
           COUNT(CASE WHEN status='skipped'   THEN 1 END) AS skipped,
           AVG(CASE WHEN served_at IS NOT NULL AND completed_at IS NOT NULL
               THEN COALESCE(time_spent,0) + TIMESTAMPDIFF(SECOND,served_at,completed_at) END) AS avg_s
    FROM tickets
    WHERE DATE(created_at) BETWEEN ? AND ? $ow
      AND transaction_type IS NOT NULL
    GROUP BY transaction_type ORDER BY total DESC");
$stmt4->bind_param('ss', $from, $to);
$stmt4->execute();
$typeRows = $stmt4->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt5 = $conn->prepare("
    SELECT HOUR(created_at) AS hr, COUNT(*) AS cnt
    FROM tickets
    WHERE DATE(created_at) BETWEEN ? AND ? $ow
    GROUP BY HOUR(created_at) ORDER BY cnt DESC LIMIT 10");
$stmt5->bind_param('ss', $from, $to);
$stmt5->execute();
$peakRows = $stmt5->get_result()->fetch_all(MYSQLI_ASSOC);

$total  = (int) ($daily['tq']  ?? 0);
$served = (int) ($daily['ts']  ?? 0);
$skip   = (int) ($daily['tsk'] ?? 0);
$avgS   = $daily['avg_s'] !== null ? (float) $daily['avg_s'] : null;
$rate   = $total > 0 ? round($served / $total * 100, 1) . '%' : 'N/A';

$officeLabel = '';
if ($officeId > 0) {
    foreach ($offices as $o) {
        if ((int) ($o['id'] ?? 0) === $officeId) {
            $officeLabel = $o['name'];
            break;
        }
    }
    if (!$officeLabel) {
        $sx = $conn->prepare("SELECT name FROM offices WHERE id = ?");
        $sx->bind_param('i', $officeId);
        $sx->execute();
        $officeLabel = $sx->get_result()->fetch_assoc()['name'] ?? '';
    }
}

$pdf = new BaseReport('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(true, 14);
$pdf->SetMargins(10, 28, 10);
$pdf->rTitle = 'Queue Management Report';
$pdf->rSub   = $officeLabel ?: 'All Offices - Admin View';
$pdf->rRange = $dateLabel;
$pdf->rGenAt = $genAt;

$pdf->AddPage();

$pdf->SecTitle('System-Wide Transaction Summary');
$pdf->StatCards([
    ['lbl' => 'Total Queued',    'val' => (string) $total,  'sub' => 'Students in queue',      'clr' => BaseReport::GRN],
    ['lbl' => 'Total Served',    'val' => (string) $served, 'sub' => 'Completed transactions', 'clr' => BaseReport::GRN],
    ['lbl' => 'Total Skipped',   'val' => (string) $skip,   'sub' => 'No-show / skipped',      'clr' => BaseReport::ORG],
    ['lbl' => 'Avg. Serve Time', 'val' => fmtTime($avgS),   'sub' => 'Per completed ticket',   'clr' => BaseReport::BLU],
    ['lbl' => 'Completion Rate', 'val' => $rate,            'sub' => 'Served / total queued',  'clr' => BaseReport::PRP],
]);

$pdf->Ln(4);

$pdf->SecTitle('Office Performance');
$pdf->THead([
    ['Office',     55, 'L'],
    ['Served',     22, 'C'],
    ['Skipped',    22, 'C'],
    ['Avg. Time',  36, 'C'],
    ['Vol. Share', 28, 'C'],
    ['Status',     27, 'C'],
]);
foreach ($offices as $i => $row) {
    $share  = (float) ($row['share'] ?? 0);
    $status = $row['total'] == 0 ? 'Inactive' : ($share > 30 ? 'High' : ($share > 15 ? 'Normal' : 'Low'));
    $pdf->TRow([
        [$row['name'],                   55, 'L'],
        [(int) $row['served'],           22, 'C'],
        [(int) $row['skipped'],          22, 'C'],
        [fmtTime($row['avg_s']),         36, 'C'],
        [number_format($share, 1) . '%', 28, 'C'],
        [$status,                        27, 'C'],
    ], $i % 2 === 1);
}

$pdf->Ln(5);

$pdf->SecTitle('Staff Performance');
if (empty($staffRows)) {
    $pdf->SetFont('Helvetica', 'I', 8);
    $pdf->SetTextColor(...BaseReport::MUT);
    $pdf->Cell(0, 8, 'No staff data for this period.', 0, 1, 'C');
    $pdf->SetTextColor(...BaseReport::TXT);
} else {
    $showOfficeCol = $officeId === 0;
    if ($showOfficeCol) {
        $pdf->THead([
            ['Name',       48, 'L'],
            ['Office',     38, 'L'],
            ['Win.',       14, 'C'],
            ['Served',     18, 'C'],
            ['Skipped',    20, 'C'],
            ['Avg. Time',  30, 'C'],
            ['Comp. Rate', 22, 'C'],
        ]);
    } else {
        $pdf->THead([
            ['Name',       65, 'L'],
            ['Window',     20, 'C'],
            ['Served',     25, 'C'],
            ['Skipped',    25, 'C'],
            ['Avg. Time',  35, 'C'],
            ['Comp. Rate', 20, 'C'],
        ]);
    }
    foreach ($staffRows as $i => $row) {
        $s   = (int) $row['served'];
        $sk  = (int) $row['skipped'];
        $tot = $s + $sk;
        $cr  = $tot > 0 ? round($s / $tot * 100, 1) . '%' : 'N/A';
        $win = $row['window_number'] ? 'W' . $row['window_number'] : '-';
        if ($showOfficeCol) {
            $pdf->TRow([
                [$row['full_name'],          48, 'L'],
                [$row['office_name'] ?? '-', 38, 'L'],
                [$win,                       14, 'C'],
                [$s,                         18, 'C'],
                [$sk,                        20, 'C'],
                [fmtTime($row['avg_s']),     30, 'C'],
                [$cr,                        22, 'C'],
            ], $i % 2 === 1);
        } else {
            $pdf->TRow([
                [$row['full_name'],      65, 'L'],
                [$win,                   20, 'C'],
                [$s,                     25, 'C'],
                [$sk,                    25, 'C'],
                [fmtTime($row['avg_s']), 35, 'C'],
                [$cr,                    20, 'C'],
            ], $i % 2 === 1);
        }
    }
}

$pdf->Ln(5);

$pdf->SecTitle('Transaction Type Breakdown');
if (empty($typeRows)) {
    $pdf->SetFont('Helvetica', 'I', 8);
    $pdf->SetTextColor(...BaseReport::MUT);
    $pdf->Cell(0, 8, 'No transaction type data for this period.', 0, 1, 'C');
    $pdf->SetTextColor(...BaseReport::TXT);
} else {
    $pdf->THead([
        ['Transaction Type', 65, 'L'],
        ['Total',            20, 'C'],
        ['Completed',        25, 'C'],
        ['Skipped',          25, 'C'],
        ['Avg. Time',        30, 'C'],
        ['Comp. Rate',       25, 'C'],
    ]);
    foreach ($typeRows as $i => $row) {
        $tot = (int) $row['total'];
        $com = (int) $row['completed'];
        $cr  = $tot > 0 ? round($com / $tot * 100, 1) . '%' : 'N/A';
        $pdf->TRow([
            [$row['transaction_type'] ?? 'Unspecified', 65, 'L'],
            [$tot,                   20, 'C'],
            [$com,                   25, 'C'],
            [(int) $row['skipped'],  25, 'C'],
            [fmtTime($row['avg_s']), 30, 'C'],
            [$cr,                    25, 'C'],
        ], $i % 2 === 1);
    }
}

$pdf->Ln(5);

$pdf->SecTitle('Top Peak Hours');
if (empty($peakRows)) {
    $pdf->SetFont('Helvetica', 'I', 8);
    $pdf->SetTextColor(...BaseReport::MUT);
    $pdf->Cell(0, 8, 'No hourly data for this period.', 0, 1, 'C');
    $pdf->SetTextColor(...BaseReport::TXT);
} else {
    $pdf->THead([
        ['Rank',         15, 'C'],
        ['Hour',         50, 'C'],
        ['Queue Volume', 50, 'C'],
        ['% of Total',   75, 'C'],
    ]);
    $totalPeak = array_sum(array_column($peakRows, 'cnt'));
    foreach ($peakRows as $i => $row) {
        $pct = $totalPeak > 0 ? round($row['cnt'] / $totalPeak * 100, 1) . '%' : '-';
        $pdf->TRow([
            ['#' . ($i + 1),                        15, 'C'],
            [date('g:00 A', mktime($row['hr'], 0)), 50, 'C'],
            [(string) $row['cnt'],                  50, 'C'],
            [$pct,                                  75, 'C'],
        ], $i % 2 === 1);
    }
}

$fn = 'Queue_Report_' . $from . ($from !== $to ? '_to_' . $to : '') . '.pdf';
$pdf->Output('D', $fn);
