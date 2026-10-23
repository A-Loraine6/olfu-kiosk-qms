<?php
require_once __DIR__ . '/includes/config_loader.php';
require_once 'includes/db.php';

date_default_timezone_set('Asia/Manila');

$officesResult = $db->query("
    SELECT id, name, code, window_count, queue_prefix, queue_mode
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
        SELECT t.office_id, t.ticket_number, t.visitor_name, t.transaction_type, t.student_level,
               t.window_id, t.status, t.served_at, t.created_at
        FROM tickets t
        WHERE t.office_id IN ($placeholders)
          AND DATE(t.created_at) = ?
          AND t.status IN ('serving','waiting','completed','skipped')
        ORDER BY t.is_priority DESC, COALESCE(t.queue_order_at, t.created_at) ASC, t.id ASC
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
            'window_count'  => max(1, (int) $office['window_count']),
            'now_serving'   => $serving,
            'next_up'       => $waiting,
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
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
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
        const REFRESH_MS = 8000;
        const HARD_RELOAD_MS = 60 * 60 * 1000;
        const MAX_NEXT_UP = 6; // rows shown in "Next Up" before collapsing into "+N more"

        let offices = DATA;

        // Track announcements per TICKET (not per window) so that several
        // "serving" tickets sharing a window can't overwrite each other and
        // cause endless re-announcements.
        const announced = {};  // "officeId:ticketNumber" -> served_at already announced
        const flashUntil = {}; // "officeId:ticketNumber" -> timestamp
        const FLASH_MS = 10000;
        const ticketKey = (o, t) => `${o.id}:${t.ticket_number}`;

        // Seed so a page load / hourly reload doesn't re-announce everything
        DATA.forEach(o => (o.now_serving || []).forEach(t => {
            announced[ticketKey(o, t)] = t.served_at || '';
        }));

        let audioUnlocked = false;
        let audioCtx = null;
        let selectedVoice = null;
        const speechQueue = [];
        let speaking = false;

        function unlockAudio() {
            if (audioUnlocked) return;
            const warm = new SpeechSynthesisUtterance('');
            speechSynthesis.speak(warm);
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            audioUnlocked = true;
            document.getElementById('audio-unlock')?.remove();
        }

        function attemptAutoUnlock() {
            try {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const warm = new SpeechSynthesisUtterance('');
                speechSynthesis.speak(warm);
                audioUnlocked = true;
            } catch (e) {
                audioUnlocked = false;
            }
        }

        // ---- Voice selection: pick a female-sounding voice if one is available ----
        function pickFemaleVoice() {
            const voices = speechSynthesis.getVoices();
            if (!voices.length) return null;

            const femaleNamePatterns = /female|zira|samantha|susan|karen|victoria|moira|tessa|fiona|serena|allison|ava|joanna|kimberly|salli|amy|emma|google uk english female|google us english/i;

            let match = voices.find(v => /en/i.test(v.lang) && femaleNamePatterns.test(v.name));
            if (match) return match;

            match = voices.find(v => femaleNamePatterns.test(v.name));
            if (match) return match;

            return voices.find(v => /en/i.test(v.lang)) || voices[0] || null;
        }

        function initVoice() {
            selectedVoice = pickFemaleVoice();
        }

        if (speechSynthesis.onvoiceschanged !== undefined) {
            speechSynthesis.onvoiceschanged = initVoice;
        }
        initVoice();

        // --- Uncomment to inspect available voices on this TV/browser (shows in tab title) ---
        // function debugListVoices() {
        //     const voices = speechSynthesis.getVoices();
        //     document.title = voices.map(v => `${v.name} (${v.lang})`).join(' | ') || 'No voices yet';
        // }
        // speechSynthesis.onvoiceschanged = debugListVoices;
        // debugListVoices();

        function playChime() {
            return new Promise(resolve => {
                if (!audioCtx) return resolve();

                const now = audioCtx.currentTime;
                const notes = [
                    { freq: 880, start: 0,    dur: 0.18 },
                    { freq: 1046.5, start: 0.18, dur: 0.22 }
                ];

                notes.forEach(n => {
                    const osc  = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(n.freq, now + n.start);
                    gain.gain.setValueAtTime(0, now + n.start);
                    gain.gain.linearRampToValueAtTime(0.3, now + n.start + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + n.start + n.dur);
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.start(now + n.start);
                    osc.stop(now + n.start + n.dur);
                });

                const totalDur = notes[notes.length - 1].start + notes[notes.length - 1].dur;
                setTimeout(resolve, totalDur * 1000 + 150);
            });
        }

        function speakText(text) {
            speechQueue.push(text);
            processSpeechQueue();
        }

        async function processSpeechQueue() {
            if (speaking || speechQueue.length === 0 || !audioUnlocked) return;
            speaking = true;

            const text = speechQueue.shift();
            await playChime();

            const utter = new SpeechSynthesisUtterance(text);
            utter.rate = 0.95;
            if (selectedVoice) {
                utter.voice = selectedVoice;
                utter.lang = selectedVoice.lang;
            } else {
                utter.lang = 'en-PH';
            }
            utter.onend = () => { speaking = false; processSpeechQueue(); };
            utter.onerror = () => { speaking = false; processSpeechQueue(); };
            speechSynthesis.speak(utter);
        }

        function spellTicketNumber(num) {
            const match = String(num).match(/^([A-Za-z]*)(\d+)$/);
            if (!match) return num;
            const [, letters, digits] = match;
            const digitStr = digits.split('').join(' ');
            return letters ? `${letters}, ${digitStr}` : digitStr;
        }

        function announceNewCalls(newOffices) {
            newOffices.forEach(o => {
                // oldest call first, so several new calls are spoken in order
                const serving = [...(o.now_serving || [])].sort((a, b) =>
                    String(a.served_at || '').localeCompare(String(b.served_at || '')));

                serving.forEach(current => {
                    const key = ticketKey(o, current);
                    const stamp = current.served_at || '';

                    // already announced this exact call -> skip
                    if (key in announced && announced[key] === stamp) return;

                    announced[key] = stamp;
                    flashUntil[key] = Date.now() + FLASH_MS;

                    const spoken = spellTicketNumber(current.ticket_number);
                    const text = current.window_id
                        ? `Now serving ${spoken}, window ${current.window_id}, ${o.name}`
                        : `Now serving ${spoken}, ${o.name}`;

                    speakText(text);
                });
            });
        }

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

        function displayName(name) {
            const words = String(name || '').trim().split(/\s+/u).filter(Boolean);
            if (words.length < 2) return words[0] || '';
            return `${words[0]} ${words.slice(1).map(word => `${Array.from(word)[0]}.`).join(' ')}`;
        }

        function buildNextUp(o) {
            const nu = Array.isArray(o.next_up) ? o.next_up : [];
            if (nu.length === 0) {
                return `<div class="c-q-empty">
                    <span class="c-q-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="5 12.5 10 17.5 19 7"/>
                        </svg>
                    </span>
                    <span>Queue is clear</span>
                </div>`;
            }

            // Render everything; fitQueues() hides whatever doesn't fit
            return `
                <div class="c-q-list" data-total="${nu.length}">
                    ${nu.map((t, i) => `
                        <div class="c-q-row${i === 0 ? ' first' : ''}">
                            <span class="c-q-pos">${i + 1}</span>
                            <span class="c-q-ticket">${esc(t.ticket_number)}</span>
                        </div>
                    `).join('')}
                    <div class="c-q-more"></div>
                </div>
            `;
        }

        function fitQueues() {
            document.querySelectorAll('.c-q-list').forEach(list => {
                const rows = [...list.querySelectorAll('.c-q-row')];
                const more = list.querySelector('.c-q-more');
                const total = rows.length;
                const gap = parseFloat(getComputedStyle(list).rowGap) || 0;
                const limit = list.clientHeight;

                // reset
                rows.forEach(r => r.style.display = '');
                more.style.display = 'none';

                const bottomOf = r => r.offsetTop + r.offsetHeight;

                // everything fits, no "+N more" needed
                if (bottomOf(rows[total - 1]) <= limit) return;

                // otherwise reserve room for the "+N more" line
                more.textContent = '+0 more';
                more.style.display = 'block';
                const reserve = more.offsetHeight + gap;

                let shown = total;
                while (shown > 1 && bottomOf(rows[shown - 1]) + reserve > limit) {
                    rows[shown - 1].style.display = 'none';
                    shown--;
                }
                more.textContent = `+${total - shown} more`;
            });
        }

        function buildCard(o) {
            const serving = o.now_serving || [];
            // Only windows that are currently serving a ticket, always ordered by window number
            const active = [...serving].sort((a, b) =>
                (Number(a.window_id) || 999) - (Number(b.window_id) || 999));

            // Layout density: 1 = big hero, 2-4 = 2-col grid, 5+ = compact rows
            const n = active.length;
            const density = n <= 1 ? 'one' : n <= 4 ? 'few' : 'many';

            const windows = n === 0
                ? `<div class="c-noticket windows-empty">
                        <div class="c-noticket-dash">—</div>
                        <div class="c-noticket-txt">No ticket called</div>
                   </div>`
                : active.map(current => {
                    const key = ticketKey(o, current);
                    const flashing = (flashUntil[key] || 0) > Date.now();
                    const label = current.window_id ? `Window ${esc(String(current.window_id))}` : 'Now Serving';

                    return `<div class="window-tile active${flashing ? ' flash' : ''}">
                        <div class="window-label">${label}</div>
                        <div class="c-hero-num">${esc(current.ticket_number)}</div>
                        ${current.visitor_name ? `<div class="c-hero-name">${esc(displayName(current.visitor_name))}</div>` : ''}
                    </div>`;
                }).join('');
            const wc = o.waiting_count || 0;
            return `
            <div class="office-card">
                <div class="c-header">
                    <div class="c-name">${esc(o.name)}</div>
                    <div class="c-badge${wc > 0 ? ' amber' : ''}">${wc} waiting</div>
                </div>
                <div class="c-ns-label"><span class="c-ns-dot"></span>Windows</div>
                <div class="office-windows ${density}">${windows}</div>
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
            fitQueues();
        }

        async function refresh() {
            try {
                const r = await fetch('api/display_data.php', { cache: 'no-store' });
                if (!r.ok) return;
                const d = await r.json();
                if (d && Array.isArray(d.offices)) {
                    announceNewCalls(d.offices);
                    offices = d.offices;
                    render();
                }
            } catch (e) {}
            setTimeout(refresh, REFRESH_MS);
        }

        render();
        setTimeout(refresh, REFRESH_MS);
        attemptAutoUnlock();
        window.addEventListener('resize', fitQueues);
    </script>

</body>

</html>