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
            <img src="img/olfu-logo.png" alt="OLFU" onerror="this.style.display='none'">
        </div>
        <div class="bar-brand">
            <div class="bar-name">Our Lady of Fatima University</div>
            <div class="bar-sub">Queue Management System</div>
        </div>
        <div class="bar-dots" id="dots"></div>
        <div class="bar-clock">
            <div class="bar-time" id="clock">—</div>
            <div class="bar-date" id="dateStr">—</div>
        </div>
    </div>

    <div class="stage" id="stage"></div>
    <div class="footnote">Live &mdash; updates every 10s</div>

    <script>
        const DATA = <?php echo $officesJson; ?>;
        const ROTATE_MS = 8000;
        const REFRESH_MS = 10000;

        let offices = DATA;
        let currentIdx = 0;
        let rotateTimer = null;
        let transitioning = false;

        /* ── Clock ── */
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

        /* ── Dots ── */
        function renderDots() {
            const el = document.getElementById('dots');
            el.innerHTML = '';
            offices.forEach((_, i) => {
                const d = document.createElement('div');
                d.className = 'dot' + (i === currentIdx ? ' on' : '');
                d.id = 'd' + i;
                el.appendChild(d);
            });
        }

        function updateDots() {
            offices.forEach((_, i) => {
                const d = document.getElementById('d' + i);
                if (d) d.className = 'dot' + (i === currentIdx ? ' on' : '');
            });
        }

        /* ── Build panel inner HTML ── */
        function buildInner(o) {

            /* Serving area */
            let main = '';
            const ns = o.now_serving || [];

            if (ns.length === 0) {
                main = `<div class="no-ticket a1">
                    <div class="no-ticket-dashes">— —</div>
                    <div class="no-ticket-txt">No ticket currently being called</div>
                </div>`;

            } else if (ns.length === 1) {
                const t = ns[0];
                if (t.window_id) {
                    main = `<div class="hero a1">
                        <div class="hero-num">${esc(t.ticket_number)}</div>
                        <div class="hero-sep"></div>
                        <div class="hero-info">
                            <div class="hero-win-lbl">Proceed to</div>
                            <div class="hero-win-num">Window&nbsp;${esc(t.window_id)}</div>
                            ${t.transaction_type ? `<div class="hero-svc">${esc(t.transaction_type)}</div>` : ''}
                        </div>
                    </div>`;
                } else {
                    main = `<div class="hero-nowin a1">
                        <div class="hero-num">${esc(t.ticket_number)}</div>
                        <div class="hero-nowin-sub">Now Serving</div>
                    </div>`;
                }

            } else {
                const cards = ns.map((t, i) => `
                    <div class="t-card a${i + 1}">
                        <div class="t-num">${esc(t.ticket_number)}</div>
                        ${t.window_id
                            ? `<div class="t-win-lbl">Window</div>
                               <div class="t-win-num">${esc(t.window_id)}</div>`
                            : ''}
                    </div>`).join('');
                main = `<div class="ticket-grid">${cards}</div>`;
            }

            /* Waiting list */
            const nu = o.next_up || [];
            const wc = o.waiting_count || 0;
            let waitHTML = '';
            if (nu.length === 0) {
                waitHTML = `<div class="q-empty">Queue is clear</div>`;
            } else {
                waitHTML = `<div class="q-list">` +
                    nu.map((t, i) => `
                        <div class="q-row${i === 0 ? ' first' : ''}">
                            <div class="q-num">${esc(t.ticket_number)}</div>
                            <div class="q-pos">#${i + 1}</div>
                        </div>`).join('') +
                    `</div>`;
            }

            /* Recently done */
            const rd = o.recently_done || [];
            let doneHTML = '';
            if (rd.length > 0) {
                doneHTML = `<div class="side-block">
                    <div class="side-lbl">Recently Served</div>
                    <div class="done-list">` +
                    rd.map(t => `
                        <div class="done-row">
                            <div class="done-check">
                                <svg viewBox="0 0 10 10" fill="none" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="1.5,5 4,7.5 8.5,2.5"/>
                                </svg>
                            </div>
                            <div class="done-num">${esc(t.ticket_number)}</div>
                        </div>`).join('') +
                    `</div></div>`;
            }

            return `
            <div class="p-header">
                <div class="p-office">${esc(o.name)}</div>
                <div class="p-meta">
                    <div class="p-stat">
                        <div class="p-stat-n${wc > 0 ? ' amber' : ''}">${wc}</div>
                        <div class="p-stat-l">Waiting</div>
                    </div>
                    <div class="p-divider"></div>
                    <div class="p-stat">
                        <div class="p-stat-n">${o.served_today || 0}</div>
                        <div class="p-stat-l">Served Today</div>
                    </div>
                </div>
            </div>

            <div class="p-body">
                <div class="p-main">
                    <div class="p-watermark">${esc(o.name)}</div>
                    <div class="ns-label"><div class="ns-dot"></div>Now Serving</div>
                    ${main}
                </div>
                <div class="p-side">
                    <div class="side-block" style="flex:1;min-height:0">
                        <div class="side-lbl">Next Up &mdash; ${wc} in queue</div>
                        ${waitHTML}
                    </div>
                    ${doneHTML}
                </div>
            </div>

            <div class="p-bar"><div class="p-bar-fill" id="bar-fill"></div></div>`;
        }

        /* ── Show panel ── */
        function showPanel(idx) {
            if (transitioning) return;
            transitioning = true;
            const stage = document.getElementById('stage');
            const prev = stage.querySelector('.panel');

            function mount() {
                const p = document.createElement('div');
                p.className = 'panel';
                p.innerHTML = buildInner(offices[idx]);
                stage.appendChild(p);
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    p.classList.add('visible');
                    startBar();
                    transitioning = false;
                    scheduleNext();
                }));
                updateDots();
            }

            if (prev) {
                prev.style.cssText =
                    'opacity:0;transform:translateY(-14px);' +
                    'transition:opacity .25s ease,transform .25s ease;pointer-events:none';
                setTimeout(() => {
                    prev.remove();
                    mount();
                }, 270);
            } else {
                mount();
            }
        }

        function startBar() {
            const f = document.getElementById('bar-fill');
            if (!f) return;
            f.style.transition = 'none';
            f.style.width = '100%';
            requestAnimationFrame(() => requestAnimationFrame(() => {
                f.style.transition = `width ${ROTATE_MS}ms linear`;
                f.style.width = '0%';
            }));
        }

        function scheduleNext() {
            clearTimeout(rotateTimer);
            if (offices.length > 1) {
                rotateTimer = setTimeout(() => {
                    currentIdx = (currentIdx + 1) % offices.length;
                    showPanel(currentIdx);
                }, ROTATE_MS);
            }
        }

        /* ── Live refresh ── */
        async function refresh() {
            try {
                const r = await fetch('api/display_data.php', {
                    cache: 'no-store'
                });
                if (!r.ok) return;
                const d = await r.json();
                if (d && Array.isArray(d.offices)) {
                    offices = d.offices;
                    if (currentIdx >= offices.length) currentIdx = 0;
                    renderDots();
                }
            } catch (e) {}
            setTimeout(refresh, REFRESH_MS);
        }

        /* ── Init ── */
        renderDots();
        if (offices.length > 0) {
            showPanel(0);
        } else {
            document.getElementById('stage').innerHTML =
                '<div class="g-empty">No offices configured</div>';
        }
        setTimeout(refresh, REFRESH_MS);
    </script>

</body>

</html>