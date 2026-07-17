<?php
require_once 'config/config.php';
require_once 'includes/db.php';

date_default_timezone_set('Asia/Manila');

$officesResult = $db->query("
    SELECT id, name, code, queue_prefix, queue_mode
    FROM offices
    ORDER BY name ASC
");
$offices = $officesResult->fetch_all(MYSQLI_ASSOC);
$officeIds = array_column($offices, 'id');
$officesJson = '[]';

if (!empty($officeIds)) {
    $today = date('Y-m-d');
    $placeholders = implode(',', array_fill(0, count($officeIds), '?'));
    $types = str_repeat('i', count($officeIds));

    $stmt = $db->prepare("
        SELECT t.office_id, t.ticket_number, t.transaction_type,
               t.window_id, t.status, t.served_at, t.created_at
        FROM tickets t
        WHERE t.office_id IN ($placeholders)
          AND DATE(t.created_at) = ?
          AND t.status IN ('serving','waiting','completed','skipped')
        ORDER BY t.created_at ASC
    ");
    $params = array_merge($officeIds, [$today]);
    $stmt->bind_param($types . 's', ...$params);
    $stmt->execute();
    $allTickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $byOffice = [];
    foreach ($allTickets as $t) {
        $byOffice[$t['office_id']][] = $t;
    }

    $displayData = [];
    foreach ($offices as $office) {
        $oid     = $office['id'];
        $tickets = $byOffice[$oid] ?? [];

        $serving   = array_values(array_filter($tickets, fn($t) => $t['status'] === 'serving'));
        $waiting   = array_values(array_filter($tickets, fn($t) => $t['status'] === 'waiting'));
        $completed = array_values(array_filter($tickets, fn($t) => $t['status'] === 'completed'));

        usort($serving,   fn($a, $b) => strtotime($b['served_at'] ?? '0') - strtotime($a['served_at'] ?? '0'));
        usort($completed, fn($a, $b) => strtotime($b['served_at'] ?? $b['created_at']) - strtotime($a['served_at'] ?? $a['created_at']));

        $displayData[] = [
            'id'            => $oid,
            'name'          => $office['name'],
            'code'          => $office['code'],
            'now_serving'   => array_slice($serving,   0, 4),
            'next_up'       => array_slice($waiting,   0, 5),
            'recently_done' => array_slice($completed, 0, 3),
            'waiting_count' => count($waiting),
            'served_today'  => count($completed),
        ];
    }
    $officesJson = json_encode($displayData);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Display</title>
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/display.css">
</head>

<body>

    <div class="bar">
        <div class="bar-logo">
            <img src="img/olfu-logo.png" alt="OLFU">
        </div>
        <div class="bar-brand">
            <div class="bar-name">Our Lady of Fatima University</div>
            <div class="bar-sub">Queue Management System</div>
        </div>
        <div class="bar-clock">
            <div class="bar-time" id="clock">—</div>
            <div class="bar-date" id="dateStr">—</div>
        </div>
    </div>

    <div class="stage" id="stage"></div>

    <script>
        const DATA = <?php echo $officesJson; ?>;
        const REFRESH_MS = 10000;
        const HARD_RELOAD_MS = 60 * 60 * 1000;

        let offices = DATA;
        
        setTimeout(() => location.reload(), HARD_RELOAD_MS);

        (function tick() {
            const n = new Date();
            document.getElementById('clock').textContent = n.toLocaleTimeString('en-PH', {
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
                hour12: true,
                timeZone: 'Asia/Manila'
            });
            document.getElementById('dateStr').textContent = n.toLocaleDateString('en-PH', {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric',
                timeZone: 'Asia/Manila'
            });
            setTimeout(tick, 1000);
        })();

        function esc(s) {
            if (!s) return '';
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        function buildNowServing(o) {
            const ns = o.now_serving || [];

            if (ns.length === 0) {
                return `<div class="c-noticket">
                    <div class="c-noticket-dash">—</div>
                    <div class="c-noticket-txt">No ticket called</div>
                </div>`;
            }

            if (ns.length === 1) {
                const t = ns[0];
                return `<div class="c-hero">
                    <div class="c-hero-num">${esc(t.ticket_number)}</div>
                    ${t.window_id
                        ? `<div class="c-hero-win">Window ${esc(t.window_id)}</div>`
                        : `<div class="c-hero-win muted">Now Serving</div>`}
                </div>`;
            }

            const cards = ns.slice(0, 2).map(t => `
                <div class="c-mini">
                    <div class="c-mini-num">${esc(t.ticket_number)}</div>
                    ${t.window_id ? `<div class="c-mini-win">W${esc(t.window_id)}</div>` : ''}
                </div>`).join('');
            return `<div class="c-mini-grid">${cards}</div>`;
        }

        function buildNextUp(o) {
            const nu = o.next_up || [];
            if (nu.length === 0) {
                return `<div class="c-q-empty">Queue is clear</div>`;
            }
            return `<div class="c-q-list">` +
                nu.slice(0, 3).map((t, i) => `
                    <div class="c-q-row${i === 0 ? ' first' : ''}">
                        <span class="c-q-num">${esc(t.ticket_number)}</span>
                        <span class="c-q-pos">#${i + 1}</span>
                    </div>`).join('') +
                `</div>`;
        }

        function buildCard(o) {
            const wc = o.waiting_count || 0;
            return `
            <div class="office-card">
                <div class="c-header">
                    <div class="c-name">${esc(o.name)}</div>
                    <div class="c-badge${wc > 0 ? ' amber' : ''}">${wc} waiting</div>
                </div>
                <div class="c-ns-label"><span class="c-ns-dot"></span>Now Serving</div>
                <div class="c-ns">${buildNowServing(o)}</div>
                <div class="c-next-label">Next Up</div>
                ${buildNextUp(o)}
            </div>`;
        }

        function render() {
            const stage = document.getElementById('stage');
            if (offices.length === 0) {
                stage.innerHTML = '<div class="g-empty">No offices configured</div>';
                return;
            }
            stage.innerHTML = `<div class="office-grid">${offices.map(buildCard).join('')}</div>`;
        }

        async function refresh() {
            try {
                const r = await fetch('api/display_data.php', {
                    cache: 'no-store'
                });
                if (!r.ok) return;
                const d = await r.json();
                if (d && Array.isArray(d.offices)) {
                    offices = d.offices;
                    render();
                }
            } catch (e) {}
            setTimeout(refresh, REFRESH_MS);
        }

        render();
        setTimeout(refresh, REFRESH_MS);
    </script>

</body>

</html>