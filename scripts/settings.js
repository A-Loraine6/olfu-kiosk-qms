const AJAX_URL = 'api/settings_actions.php';

async function ajax(payload) {
    const res = await fetch(AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...payload, csrf_token: CSRF_TOKEN })
    });
    return res.json();
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast     = document.createElement('div');
    const icon      = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas ${icon}"></i>
        <span>${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => {
        toast.style.transition = 'opacity 0.4s, transform 0.4s';
        toast.style.opacity    = '0';
        toast.style.transform  = 'translateX(100%)';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

function setBtnLoading(btn, loading, originalHtml) {
    btn.disabled  = loading;
    btn.innerHTML = loading
        ? '<i class="fas fa-spinner fa-spin"></i> Saving…'
        : originalHtml;
}

// Returns the header save button for a given tab name
function getHeaderSaveBtn(tab) {
    const map = {
        schedule: 'headerSaveSchedule',
        queue:    'headerSaveQueue',
        kiosk:    'headerSaveKiosk',
    };
    return document.getElementById(map[tab]);
}

document.getElementById('scheduleForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn          = getHeaderSaveBtn('schedule');
    const originalHtml = btn.innerHTML;
    setBtnLoading(btn, true, originalHtml);

    const data = await ajax({
        action:      'save_schedule',
        office_id:   this.querySelector('[name=office_id]').value,
        open:        this.querySelector('[name=open]').value,
        close:       this.querySelector('[name=close]').value,
        break_start: this.querySelector('[name=break_start]').value,
        break_end:   this.querySelector('[name=break_end]').value,
        cutoff:      this.querySelector('[name=cutoff]').value,
    });

    setBtnLoading(btn, false, originalHtml);
    showToast(data.message, data.success ? 'success' : 'error');
});

document.getElementById('queueForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn          = getHeaderSaveBtn('queue');
    const originalHtml = btn.innerHTML;
    setBtnLoading(btn, true, originalHtml);

    const data = await ajax({
        action:       'save_queue',
        office_id:    this.querySelector('[name=office_id]').value,
        queue_prefix: this.querySelector('[name=queue_prefix]').value,
        queue_start:  this.querySelector('[name=queue_start]').value,
        daily_reset:  this.querySelector('[name=daily_reset]').checked,
        max_limit:    this.querySelector('[name=max_limit]').value,
        auto_close:   this.querySelector('[name=auto_close]').checked,
    });

    setBtnLoading(btn, false, originalHtml);
    showToast(data.message, data.success ? 'success' : 'error');
});

document.getElementById('kioskForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn          = getHeaderSaveBtn('kiosk');
    const originalHtml = btn.innerHTML;
    setBtnLoading(btn, true, originalHtml);

    const data = await ajax({
        action:        'save_kiosk',
        kiosk_enabled: this.querySelector('[name=kiosk_enabled]').checked,
        idle_timeout:  this.querySelector('[name=idle_timeout]').value,
    });

    setBtnLoading(btn, false, originalHtml);
    showToast(data.message, data.success ? 'success' : 'error');
});

const TABS = ['schedule', 'queue', 'kiosk'];

function updateHeaderSaveButton(tab) {
    TABS.forEach(t => {
        const btn = getHeaderSaveBtn(t);
        if (btn) btn.style.display = t === tab ? '' : 'none';
    });
}

function switchTab(tab) {
    TABS.forEach(t => {
        document.getElementById('tab-' + t).classList.toggle('active', t === tab);
    });
    document.querySelectorAll('.settings-tab').forEach((btn, i) => {
        btn.classList.toggle('active', TABS[i] === tab);
    });
    updateHeaderSaveButton(tab);
    history.replaceState(null, '', setParam('tab', tab));
}

function switchOffice(officeId, tab) {
    let url = setParam('office', officeId);
    url = setParamIn(url, 'tab', tab);
    window.location.href = url;
}

function setParam(key, value) {
    return setParamIn(window.location.href, key, value);
}

function setParamIn(href, key, value) {
    const u = new URL(href, window.location.origin);
    u.searchParams.set(key, value);
    return u.toString();
}

function updatePreview() {
    const prefix = (document.getElementById('prefixInput')?.value || '').toUpperCase().trim();
    const start  = parseInt(document.getElementById('startInput')?.value || '1') || 1;
    const num    = String(start).padStart(3, '0');
    const label  = prefix ? `${prefix}-${num}` : num;
    const el     = document.getElementById('queuePreview');
    if (el) el.innerHTML = `<i class="fas fa-ticket-alt"></i> ${label}`;
}

function updateKioskStatus(isOn) {
    const card  = document.getElementById('kioskStatusCard');
    const label = document.getElementById('kioskStatusLabel');
    const sub   = document.getElementById('kioskStatusSub');
    const icon  = card?.querySelector('.kiosk-status-icon i');
    if (!card) return;
    card.className = 'kiosk-status-card ' + (isOn ? 'status-on' : 'status-off');
    if (icon)  icon.className    = 'fas fa-' + (isOn ? 'tablet-alt' : 'ban');
    if (label) label.textContent = 'Kiosk is ' + (isOn ? 'Enabled' : 'Disabled');
    if (sub)   sub.textContent   = isOn
        ? 'Users can interact with the kiosk to get queue numbers.'
        : 'The kiosk is turned off. Users cannot generate queue numbers.';
}

function clearCutoff() {
    const el = document.getElementById('cutoffInput');
    if (el) el.value = '';
}

document.addEventListener('DOMContentLoaded', updatePreview);