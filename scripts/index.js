document.addEventListener(
	"touchmove",
	(e) => {
		if (e.touches.length > 1) e.preventDefault();
	},
	{ passive: false },
);

["gesturestart", "gesturechange", "gestureend"].forEach((evt) =>
	document.addEventListener(evt, (e) => e.preventDefault()),
);

document.addEventListener(
	"wheel",
	(e) => {
		if (e.ctrlKey) e.preventDefault();
	},
	{ passive: false },
);

document.addEventListener("keydown", (e) => {
	if ((e.ctrlKey || e.metaKey) && ["+", "-", "=", "0"].includes(e.key)) {
		e.preventDefault();
	}
});

let state = {
	screen: 0,
	office: null,
	service: null,
	studentLevel: null,
	history: [0],
	isPriority: false,
};

const DEFAULT_AVG_MINUTES = 5;

function getAvgMinutes(office) {
	return office && office.avg_minutes != null && office.avg_minutes > 0
		? office.avg_minutes
		: DEFAULT_AVG_MINUTES;
}

function formatMinutes(mins) {
	mins = Math.max(0, Math.round(mins));
	if (mins < 60) return `${mins}m`;
	const h = Math.floor(mins / 60);
	const m = mins % 60;
	return m === 0 ? `${h}h` : `${h}h ${m}m`;
}

let nameKeyboardUppercase = true;
let nameKeyboardCloseTimeout = null;
let backspaceRepeatTimeout = null;
let backspaceRepeatInterval = null;
let backspacePointerActivation = false;

function buildNameKeyboard() {
	const rows = [
		["keyboardRow1", "qwertyuiop"],
		["keyboardRow2", "asdfghjkl"],
		["keyboardRow3", "zxcvbnm"],
	];

	rows.forEach(([rowId, letters]) => {
		const row = document.getElementById(rowId);
		if (rowId === "keyboardRow3") {
			row.appendChild(
				createNameKeyboardKey(
					"shift",
					"Shift",
					"keyboard-key-shift",
					"fas fa-arrow-up",
				),
			);
		}
		[...letters].forEach((letter) => row.appendChild(createNameKeyboardKey(letter, letter)));
		if (rowId === "keyboardRow3") {
			row.appendChild(
				createNameKeyboardKey(
					"backspace",
					"Backspace",
					"keyboard-key-backspace",
					"fas fa-delete-left",
				),
			);
		}
	});

	const keyboard = document.getElementById("nameKeyboard");
	keyboard.addEventListener("click", (event) => {
		if (suppressKeyClick) {
			suppressKeyClick = false;
			return;
		}
		const key = event.target.closest("[data-key]");
		if (!key) return;
		if (key.dataset.key === "backspace") {
			if (backspacePointerActivation) backspacePointerActivation = false;
			else deleteNameCharacter();
			return;
		}
		handleNameKeyboardKey(key.dataset.key);
	});

	const backspaceKey = keyboard.querySelector('[data-key="backspace"]');
	backspaceKey.addEventListener("pointerdown", () => {
		backspacePointerActivation = true;
		deleteNameCharacter();
		backspaceRepeatTimeout = setTimeout(() => {
			backspaceRepeatInterval = setInterval(deleteNameCharacter, 80);
		}, 400);
	});
	backspaceKey.addEventListener("pointerup", () => {
		stopBackspaceRepeat();
		setTimeout(() => { backspacePointerActivation = false; }, 250);
	});
	backspaceKey.addEventListener("pointerleave", () => {
		stopBackspaceRepeat();
		backspacePointerActivation = false;
	});
	backspaceKey.addEventListener("pointercancel", () => {
		stopBackspaceRepeat();
		backspacePointerActivation = false;
	});

	setupKeyPreviewAndLongPress(keyboard);
	document.getElementById("visitorName").addEventListener("click", showNameKeyboard);
	updateClearKey();
	updateNameCaret();
}

// ---- Phone-style key preview bubble + long-press n for ñ ----
let suppressKeyClick = false;
let longPressTimer = null;
let longPressFired = false;
let pressedLetterKey = null;
const LONG_PRESS_MS = 450;

function setupKeyPreviewAndLongPress(keyboard) {
	const preview = document.createElement("div");
	preview.className = "key-preview";
	preview.id = "keyPreview";
	preview.setAttribute("aria-hidden", "true");
	keyboard.appendChild(preview);

	keyboard.addEventListener("contextmenu", (e) => e.preventDefault());

	keyboard.addEventListener("pointerdown", (e) => {
		const key = e.target.closest("[data-key]");
		if (!key || key.disabled || !/^[a-z]$/.test(key.dataset.key)) return;
		pressedLetterKey = key;
		longPressFired = false;
		showKeyPreview(key, key.textContent, false);
		clearTimeout(longPressTimer);
		if (key.dataset.key === "n") {
			longPressTimer = setTimeout(() => {
				longPressFired = true;
				showKeyPreview(key, nameKeyboardUppercase ? "Ñ" : "ñ", true);
			}, LONG_PRESS_MS);
		}
	});

	const finishPress = (cancelled) => {
		clearTimeout(longPressTimer);
		longPressTimer = null;
		if (pressedLetterKey && longPressFired && !cancelled) {
			suppressKeyClick = true;
			setTimeout(() => { suppressKeyClick = false; }, 300);
			handleNameKeyboardKey("enye");
		}
		longPressFired = false;
		pressedLetterKey = null;
		hideKeyPreview();
	};
	document.addEventListener("pointerup", () => finishPress(false));
	document.addEventListener("pointercancel", () => finishPress(true));
}

function showKeyPreview(keyEl, text, isAlt) {
	const keyboard = document.getElementById("nameKeyboard");
	const preview = document.getElementById("keyPreview");
	if (!preview) return;
	const kb = keyboard.getBoundingClientRect();
	const k = keyEl.getBoundingClientRect();
	const width = k.width + 18;
	preview.textContent = text;
	preview.classList.toggle("is-alt", !!isAlt);
	preview.style.width = `${width}px`;
	preview.style.left = `${k.left - kb.left - 9}px`;
	preview.style.top = `${k.top - kb.top - 76}px`;
	preview.classList.add("show");
}

function hideKeyPreview() {
	const preview = document.getElementById("keyPreview");
	if (preview) preview.classList.remove("show");
}

function createNameKeyboardKey(value, label, extraClass = "", iconClass = "") {
	const key = document.createElement("button");
	key.type = "button";
	key.className = `keyboard-key ${extraClass}`.trim();
	key.dataset.key = value;
	if (iconClass) {
		key.setAttribute("aria-label", label);
		const icon = document.createElement("i");
		icon.className = iconClass;
		icon.setAttribute("aria-hidden", "true");
		key.appendChild(icon);
	} else {
		key.textContent = label;
	}
	return key;
}

function showNameKeyboard() {
	const keyboard = document.getElementById("nameKeyboard");
	if (!keyboard.hidden && keyboard.classList.contains("is-opening")) return;
	clearTimeout(nameKeyboardCloseTimeout);
	nameKeyboardCloseTimeout = null;
	keyboard.hidden = false;
	keyboard.classList.remove("is-closing");
	const screen = document.getElementById("screen3");
	screen.classList.remove("keyboard-closing");
	screen.classList.add("keyboard-open");
	screen.querySelector(".screen-body").scrollTop = 0;
	const input = document.getElementById("visitorName");
	input.classList.add("is-active");
	input.classList.remove("name-saved");
	updateNameKeyboardCase();
	updateNameCaret();
	void keyboard.offsetWidth;
	keyboard.classList.add("is-opening");
	resetIdle();
}

function hideNameKeyboard({ immediate = false, clearName = false } = {}) {
	const keyboard = document.getElementById("nameKeyboard");
	const input = document.getElementById("visitorName");
	if (clearName) {
		input.value = "";
		updateNameCaret();
		updateClearKey();
		nameKeyboardUppercase = true;
		updateNameKeyboardCase();
	}
	if (keyboard.hidden) {
		input.classList.remove("is-active");
		document.getElementById("screen3").classList.remove("keyboard-open", "keyboard-closing");
		return;
	}
	if (keyboard.classList.contains("is-closing")) return;

	clearTimeout(nameKeyboardCloseTimeout);
	stopBackspaceRepeat();
	if (immediate || window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
		keyboard.hidden = true;
		keyboard.classList.remove("is-opening", "is-closing");
		document.getElementById("screen3").classList.remove("keyboard-open", "keyboard-closing");
		input.classList.remove("is-active");
		return;
	}

	keyboard.classList.remove("is-opening");
	keyboard.classList.add("is-closing");
	const screen = document.getElementById("screen3");
	screen.classList.remove("keyboard-open");
	screen.classList.add("keyboard-closing");
	// Keep this timeout in sync with the 220ms .is-closing transition in index.css.
	nameKeyboardCloseTimeout = setTimeout(() => {
		keyboard.hidden = true;
		keyboard.classList.remove("is-closing");
		input.classList.remove("is-active");
		screen.classList.remove("keyboard-closing");
		if (input.value.trim()) {
			input.classList.remove("name-saved");
			void input.offsetWidth;
			input.classList.add("name-saved");
		}
		nameKeyboardCloseTimeout = null;
	}, 220);
}

function updateNameKeyboardCase() {
	document.querySelectorAll("#nameKeyboard [data-key]").forEach((key) => {
		if (/^[a-z]$/.test(key.dataset.key)) {
			key.textContent = nameKeyboardUppercase
				? key.dataset.key.toUpperCase()
				: key.dataset.key;
		}
		if (key.dataset.key === "shift") {
			key.classList.toggle("is-active", nameKeyboardUppercase);
			key.setAttribute("aria-pressed", String(nameKeyboardUppercase));
		}
	});
}

function handleNameKeyboardKey(key) {
	const input = document.getElementById("visitorName");
	let insertedCharacter = "";
	if (/^[a-z]$/.test(key)) {
		insertedCharacter = nameKeyboardUppercase ? key.toUpperCase() : key;
	} else if (key === "enye") {
		insertedCharacter = nameKeyboardUppercase ? "Ñ" : "ñ";
	} else if (key === "period") {
		insertedCharacter = ".";
	} else if (key === "comma") {
		insertedCharacter = ",";
	} else if (key === "apostrophe") {
		insertedCharacter = "'";
	} else if (key === "hyphen") {
		insertedCharacter = "-";
	}

	if (insertedCharacter) {
		appendNameCharacter(insertedCharacter);
		nameKeyboardUppercase = false;
		updateNameKeyboardCase();
	} else {
		switch (key) {
			case "space":
				if (input.value.length < input.maxLength && input.value && !/\s$/.test(input.value)) {
					appendNameCharacter(" ");
					nameKeyboardUppercase = true;
					updateNameKeyboardCase();
				}
				break;
			case "backspace":
				deleteNameCharacter();
				break;
			case "clear":
				input.value = "";
				nameKeyboardUppercase = true;
				updateNameKeyboardCase();
				updateNameCaret();
				updateClearKey();
				break;
			case "shift":
				nameKeyboardUppercase = !nameKeyboardUppercase;
				updateNameKeyboardCase();
				break;
			case "done":
				hideNameKeyboard();
				break;
		}
	}

	resetIdle();
}

function appendNameCharacter(character) {
	const input = document.getElementById("visitorName");
	if (input.value.length >= input.maxLength) return;
	input.value += character;
	updateNameCaret();
	updateClearKey();
}

function deleteNameCharacter() {
	const input = document.getElementById("visitorName");
	if (input.value) input.value = Array.from(input.value).slice(0, -1).join("");
	nameKeyboardUppercase = input.value === "" || /[.]\s*$/.test(input.value) || /\s$/.test(input.value);
	updateNameKeyboardCase();
	updateNameCaret();
	updateClearKey();
	resetIdle();
}

function stopBackspaceRepeat() {
	clearTimeout(backspaceRepeatTimeout);
	clearInterval(backspaceRepeatInterval);
	backspaceRepeatTimeout = null;
	backspaceRepeatInterval = null;
}

function updateClearKey() {
	const clearKey = document.querySelector('#nameKeyboard [data-key="clear"]');
	if (clearKey) clearKey.disabled = document.getElementById("visitorName").value.length === 0;
}

function updateNameCaret() {
	const input = document.getElementById("visitorName");
	const caret = document.getElementById("visitorNameCaret");
	if (!input || !caret) return;
	const canvas = updateNameCaret.canvas || (updateNameCaret.canvas = document.createElement("canvas"));
	const context = canvas.getContext("2d");
	if (!context) return;
	context.font = getComputedStyle(input).font;
	const textWidth = context.measureText(input.value).width;
	const leftPadding = parseFloat(getComputedStyle(input).paddingLeft) || 0;
	caret.style.left = `${Math.min(leftPadding + textWidth, input.clientWidth - 12)}px`;
}

function nameKeyboardIsOpen() {
	return !document.getElementById("nameKeyboard").hidden;
}

function selectPriority(isPriority) {
	fetch("api/kiosk_status.php", { cache: "no-store" })
		.then((r) => r.json())
		.then((data) => {
			if (!data.enabled) {
				location.href = location.pathname + "?_=" + Date.now();
				return;
			}
			state.isPriority = isPriority;
			updatePriorityPills();
			state.history = [0, 1];
			showScreen(1);
		})
		.catch(() => {
			state.isPriority = isPriority;
			updatePriorityPills();
			state.history = [0, 1];
			showScreen(1);
		});
}

if (!KIOSK_ENABLED) {
	document.body.innerHTML = `
		<div style="
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		height: 100vh;
		background: linear-gradient(135deg, #006633 0%, #008b43 60%, #00b856 100%);
		font-family: 'Plus Jakarta Sans', sans-serif;
		text-align: center;
		padding: 40px;
		">
		<div style="
			background: rgba(255,255,255,0.08);
			border: 1px solid rgba(255,255,255,0.15);
			border-radius: 32px;
			padding: 60px 80px;
			backdrop-filter: blur(12px);
			max-width: 560px;
			width: 100%;
		">
			<div style="
			width: 90px;
			height: 90px;
			background: rgba(255,255,255,0.15);
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 0 auto 28px;
			font-size: 40px;
			color: #fff;
			">
			<i class="fas fa-power-off"></i>
			</div>
			<div style="
			font-size: 13px;
			font-weight: 700;
			letter-spacing: 0.15em;
			text-transform: uppercase;
			color: rgba(255,255,255,0.6);
			margin-bottom: 12px;
			">Queue Management System</div>
			<h1 style="
			font-size: 36px;
			font-weight: 800;
			color: #fff;
			margin: 0 0 16px;
			line-height: 1.2;
			">Kiosk is Currently<br>Unavailable</h1>
			<p style="
			font-size: 16px;
			color: rgba(255,255,255,0.75);
			margin: 0 0 36px;
			line-height: 1.6;
			">This kiosk has been temporarily disabled.<br>Please approach a staff member for assistance.</p>
			<div style="
			display: inline-flex;
			align-items: center;
			gap: 10px;
			background: rgba(255,255,255,0.12);
			border: 1px solid rgba(255,255,255,0.2);
			border-radius: 50px;
			padding: 12px 24px;
			color: rgba(255,255,255,0.85);
			font-size: 14px;
			font-weight: 600;
			">
			<i class="fas fa-headset"></i>
			See a staff member for help
			</div>
		</div>
		<div style="
			margin-top: 32px;
			font-size: 13px;
			color: rgba(255,255,255,0.4);
		">© Our Lady of Fatima University</div>
		</div>
	`;

	setInterval(() => {
		fetch("api/kiosk_status.php", { cache: "no-store" })
			.then((r) => r.json())
			.then((data) => {
				if (data.enabled) {
					location.href = location.pathname + "?_=" + Date.now();
				}
			})
			.catch(() => {});
	}, 10000);
	throw new Error("Kiosk disabled");
}

function updateClocks() {
	const now = new Date();
	const time = now.toLocaleTimeString("en-PH", {
		hour: "numeric",
		minute: "2-digit",
		second: "2-digit",
		hour12: true,
		timeZone: "Asia/Manila",
	});
	const date = now.toLocaleDateString("en-PH", {
		weekday: "long",
		month: "long",
		day: "numeric",
		year: "numeric",
		timeZone: "Asia/Manila",
	});
	document.getElementById("topTime").textContent = time;
	document.getElementById("topDate").textContent = date;
	document.getElementById("idleTime").textContent = time;
	document.getElementById("idleDate").textContent = date;
}
updateClocks();
setInterval(updateClocks, 1000);

(function () {
	const container = document.getElementById("idleBubbles");
	if (!container) return;
	const count = 24;
	for (let i = 0; i < count; i++) {
		const size = 10 + Math.random() * 90;
		const left = Math.random() * 100;
		const duration = 8 + Math.random() * 20;
		const delay = -(Math.random() * 25);
		const driftX = (Math.random() - 0.5) * 160;
		const opacity = 0.04 + Math.random() * 0.1;
		const b = document.createElement("div");
		b.className = "idle-bubble";
		b.style.cssText = `
			width:${size}px;height:${size}px;left:${left}%;bottom:-${size}px;
			animation-duration:${duration}s;animation-delay:${delay}s;
			--drift-x:${driftX}px;opacity:${opacity};`;
		container.appendChild(b);
	}
})();

function getTotalSteps() {
	const o = state.office;
	return o && (
		o.mode === "level-based" ||
		(o.mode === "service-based" && o.services && o.services.length)
	)
		? 4
		: 3;
}

function updateStepLabels() {
	const total = getTotalSteps();
	const stepMap = { 0: 1, 1: 2, 2: 3, 3: total };

	[0, 1, 2, 3].forEach((screenNum) => {
		const screenEl = document.getElementById("screen" + screenNum);
		const eyebrow = screenEl && screenEl.querySelector(".eyebrow");
		if (eyebrow) {
			eyebrow.textContent = `Step ${stepMap[screenNum]}`;
		}
	});
}

function showScreen(n) {
	const cur = document.getElementById("screen" + state.screen);
	const nxt = document.getElementById("screen" + n);
	cur.classList.remove("active");
	cur.classList.add("exit");
	setTimeout(() => cur.classList.remove("exit"), 250);
	nxt.classList.add("active");
	const body = nxt.querySelector(".screen-body");
	if (body) body.scrollTop = 0;
	state.screen = n;
	updateStepLabels();
	resetIdle();
}

function goBack() {
	state.history.pop();
	const prev = state.history.length
		? state.history[state.history.length - 1]
		: 0;
	showScreen(prev);
}

const ICONS = {
	REG: "fa-scroll",
	ACC: "fa-coins",
	ADM: "fa-user-graduate",
};

function isOfficeClosed(office, now = getManilaTime()) {
	const openTime = office.open_time.slice(0, 5);
	const closeTime = office.close_time.slice(0, 5);
	const cutoffPassed = office.queue_cutoff && now >= office.queue_cutoff.slice(0, 5);
	return now < openTime || now >= closeTime || cutoffPassed;
}

function getManilaTime() {
	return new Date().toLocaleTimeString("en-PH", {
		hour: "2-digit",
		minute: "2-digit",
		hour12: false,
		timeZone: "Asia/Manila",
	});
}

function formatTime12(hhmm) {
	if (!hhmm) return "";
	const [h, m] = hhmm.split(":").map(Number);
	const period = h >= 12 ? "PM" : "AM";
	const hour = h % 12 || 12;
	return `${hour}:${String(m).padStart(2, "0")} ${period}`;
}

function isOfficeAcceptingQueue(office) {
	const hhmm = getManilaTime();

	if (office.queue_cutoff && hhmm >= office.queue_cutoff.slice(0, 5)) {
		return {
			ok: false,
			code: "cutoff",
			reason: `Queue closed — cut-off time (${formatTime12(office.queue_cutoff)}) has passed.`,
		};
	}

	return { ok: true };
}

function getBreakInfo(office) {
	if (!office.break_start || !office.break_end) return { onBreak: false };
	const hhmm = getManilaTime();
	if (hhmm >= office.break_start.slice(0, 5) && hhmm < office.break_end.slice(0, 5)) {
		return { onBreak: true, breakEnd: office.break_end };
	}
	return { onBreak: false };
}

function minutesUntil(hhmm) {
	const now = new Date();
	const [h, m] = hhmm.split(":").map(Number);
	const target = new Date(now);
	target.setHours(h, m, 0, 0);
	const diffMs = Math.max(0, target - now);
	return Math.ceil(diffMs / 60000);
}

function renderOffices() {
	updateStepLabels();
	const grid = document.getElementById("officeGrid");
	grid.innerHTML = "";
	OFFICES.forEach((o) => {
		const w = o.waiting;
		const icon = ICONS[o.code] || "fa-building";
		const now = getManilaTime();
		const closed = isOfficeClosed(o, now);
		const breakInfo = getBreakInfo(o);
		const cutoffPassed = o.queue_cutoff && now >= o.queue_cutoff.slice(0, 5);
		const btn = document.createElement("button");
		btn.className = "office-card" + (closed ? " office-closed" : "");
		btn.disabled = closed;

		let badgeHtml;
		if (cutoffPassed) {
			badgeHtml = '<i class="fas fa-clock" style="font-size:10px"></i> Cut-off passed';
		} else if (closed) {
			badgeHtml = '<i class="fas fa-lock" style="font-size:10px"></i> Closed';
		} else if (breakInfo.onBreak) {
			badgeHtml = `<i class="fas fa-coffee" style="font-size:10px"></i> On break until ${formatTime12(breakInfo.breakEnd)}`;
		} else if (w > 0) {
			badgeHtml = '<span class="pulse"></span>' + w + " waiting";
		} else {
			badgeHtml =
				'<i class="fas fa-check" style="font-size:10px"></i> No queue';
		}

		btn.innerHTML = `
			<div class="office-icon"><i class="fas ${icon}"></i></div>
			<div class="office-name">${esc(o.name)}</div>
			<div class="office-badge ${closed ? "badge-closed" : breakInfo.onBreak ? "badge-break" : w > 0 ? "badge-active" : "badge-empty"}">
				${badgeHtml}
			</div>`;
		if (!closed) btn.addEventListener("click", () => selectOffice(o));
		grid.appendChild(btn);
	});
}

function selectOffice(o) {
	state.office = o;
	state.service = null;
	state.studentLevel = null;
	state.history = [0, 1];

	const check = isOfficeAcceptingQueue(o);
	if (!check.ok) {
		showBlockedMessage(check.code, check.reason);
		return;
	}

	updatePriorityPills();

	if (o.mode === "level-based") {
		renderLevels(o);
		state.history.push(2);
		showScreen(2);
	} else if (o.mode === "service-based" && o.services && o.services.length) {
		renderServices(o);
		state.history.push(2);
		showScreen(2);
	} else {
		checkAndPrepareConfirm();
	}
}

function updatePriorityPills() {
	const show = !!state.isPriority;
	[
		"s1PriorityPill",
		"s2PriorityPill",
		"s3PriorityPill",
		"s4PriorityPill",
	].forEach((id) => {
		const el = document.getElementById(id);
		if (el) el.style.display = show ? "inline-flex" : "none";
	});
}

function renderServices(o) {
	document.getElementById("s2Title").textContent = "Select a Service";
	document.getElementById("s2OfficeName").textContent = o.name;
	const grid = document.getElementById("serviceGrid");
	grid.innerHTML = "";
	grid.classList.remove("level-grid");
	o.services.forEach((s) => {
		const btn = document.createElement("button");
		btn.className = "service-card";
		btn.innerHTML = `
			<div class="svc-name">${esc(s.name)}</div>
			${s.description ? `<div class="svc-desc">${esc(s.description)}</div>` : ""}`;
		btn.addEventListener("click", () => selectService(s));
		grid.appendChild(btn);
	});
}

function renderLevels(o) {
	document.getElementById("s2Title").textContent = "Select your student level";
	document.getElementById("s2OfficeName").textContent = o.name;
	const grid = document.getElementById("serviceGrid");
	grid.innerHTML = "";
	grid.classList.add("level-grid");
	[
		{ value: "shs", label: "SHS", desc: "Senior High School", icon: "fa-graduation-cap" },
		{ value: "college", label: "College", desc: "Undergraduate / Graduate", icon: "fa-graduation-cap" },
	].forEach((level) => {
		const btn = document.createElement("button");
		btn.className = "level-card";
		btn.innerHTML = `
			<div class="level-card-icon"><i class="fas ${level.icon}"></i></div>
			<div class="level-card-name">${level.label}</div>
			<div class="level-card-desc">${level.desc}</div>`;
		btn.addEventListener("click", () => selectStudentLevel(level.value));
		grid.appendChild(btn);
	});
}

function selectStudentLevel(level) {
	if (level !== "shs" && level !== "college") return;
	const check = isOfficeAcceptingQueue(state.office);
	if (!check.ok) {
		showBlockedMessage(check.code, check.reason);
		return;
	}
	state.studentLevel = level;
	checkAndPrepareConfirm();
}

function selectService(s) {
	const check = isOfficeAcceptingQueue(state.office);
	if (!check.ok) {
		showBlockedMessage(check.code, check.reason);
		return;
	}
	state.service = s;
	checkAndPrepareConfirm();
}

function prepareConfirm() {
	const o = state.office,
		s = state.service;
	const studentLevel = state.studentLevel;
	document.getElementById("s3OfficeName").textContent = o.name;
	document.getElementById("s3ServiceName").textContent = o.mode === "level-based"
		? (studentLevel === "shs" ? "SHS" : "College")
		: (s ? s.name : "General Queue");
	document.getElementById("s3QueuePreview").textContent = "—";
	updatePriorityPills();

	fetch("api/preview_ticket.php", {
		method: "POST",
		headers: { "Content-Type": "application/x-www-form-urlencoded" },
		body: `office_id=${o.id}&service_name=${encodeURIComponent(s ? s.name : "")}&student_level=${encodeURIComponent(studentLevel || "")}&is_priority=${state.isPriority ? 1 : 0}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`,
	})
		.then((r) => r.json())
		.then((data) => {
			document.getElementById("s3QueuePreview").textContent = data.success
				? data.preview_ticket
				: "—";
		})
		.catch(() => {
			document.getElementById("s3QueuePreview").textContent = "—";
		});

	renderWaitEstimate(o, "s3WaitEstimate");
}

function renderWaitEstimate(o, targetElId, apiData) {
  const target = document.getElementById(targetElId);
  const onBreak = apiData ? apiData.on_break : getBreakInfo(o).onBreak;
  const peopleAhead = apiData ? apiData.people_ahead : o.waiting;
  const estWaitMins = apiData ? apiData.est_wait_mins : peopleAhead * getAvgMinutes(o);

  if (onBreak) {
    const breakEnd = o.break_end;
    const resumeIn = minutesUntil(breakEnd);
    target.innerHTML = `
      <div class="break-banner">
        <div class="break-banner-icon"><i class="fas fa-mug-hot"></i></div>
        <div class="break-banner-text">
          <div class="break-banner-title">Office is on break</div>
          <div class="break-banner-sub">Resumes at <strong>${formatTime12(breakEnd)}</strong></div>
        </div>
      </div>
      <div class="wait-stat-row">
        <div class="wait-stat">
          <div class="wait-stat-value">${formatMinutes(resumeIn)}</div>
          <div class="wait-stat-label">Until break ends</div>
        </div>
        <div class="wait-stat-divider"></div>
        <div class="wait-stat">
          <div class="wait-stat-value">${peopleAhead}</div>
          <div class="wait-stat-label">People ahead</div>
        </div>
        <div class="wait-stat-divider"></div>
        <div class="wait-stat">
          <div class="wait-stat-value">~${formatMinutes(estWaitMins)}</div>
          <div class="wait-stat-label">Total est. wait</div>
        </div>
      </div>`;
    return;
  }

  target.innerHTML =
    peopleAhead === 0
      ? `<div class="wait-next">You're next! No wait time.</div>`
      : `Estimated wait: <strong>~${formatMinutes(estWaitMins)}</strong>`;
}

function confirmQueue() {
	hideNameKeyboard({ immediate: true });
	const btn = document.getElementById("confirmBtn");
	btn.disabled = true;
	btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

	const o = state.office,
		s = state.service;
	const studentLevel = state.studentLevel;

	fetch("api/issue_ticket.php", {
		method: "POST",
		headers: { "Content-Type": "application/x-www-form-urlencoded" },
		body: `office_id=${o.id}&service_name=${encodeURIComponent(s ? s.name : "")}&student_level=${encodeURIComponent(studentLevel || "")}&is_priority=${state.isPriority ? 1 : 0}&visitor_name=${encodeURIComponent(document.getElementById("visitorName").value.trim())}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`,
	})
		.then((r) => r.json())
		.then((data) => {
			btn.disabled = false;
			btn.innerHTML =
				'<i class="fas fa-check-circle"></i> Confirm &amp; Get Ticket';

			if (data.success) {
				const oo = OFFICES.find((x) => x.id == o.id);
				if (oo) oo.waiting = data.waiting_count ?? oo.waiting + 1;
				showTicket(data);
			} else {
				const msg = data.message || "Unable to issue a ticket at this time.";
				const code = data.code || inferErrorCode(msg);
				showBlockedMessage(code, msg);
			}
		})
		.catch(() => {
			btn.disabled = false;
			btn.innerHTML =
				'<i class="fas fa-check-circle"></i> Confirm &amp; Get Ticket';
			showServerErrorMessage();
		});
}

function inferErrorCode(msg) {
	const m = msg.toLowerCase();
	if (m.includes("break")) return "break";
	if (m.includes("cut-off") || m.includes("cutoff") || m.includes("closed"))
		return "cutoff";
	if (m.includes("full")) return "queue_full";
	if (m.includes("server") || m.includes("database")) return "server_error";
	return "generic";
}

const BLOCKED_CONFIG = {
	break: { icon: "fa-mug-hot", title: "Office is on Break", tone: "tone-info" },
	cutoff: { icon: "fa-clock", title: "Queue is Now Closed", tone: "tone-warn" },
	cutoff_risk: {
		icon: "fa-hourglass-end",
		title: "Cannot Issue Ticket",
		tone: "tone-warn",
	},
	queue_full: {
		icon: "fa-users-slash",
		title: "Queue is Full",
		tone: "tone-warn",
	},
	office_closed: {
		icon: "fa-door-closed",
		title: "Office is Closed",
		tone: "tone-neutral",
	},
	server_error: {
		icon: "fa-triangle-exclamation",
		title: "Something Went Wrong",
		tone: "tone-danger",
	},
	generic: {
		icon: "fa-circle-exclamation",
		title: "Cannot Issue Ticket",
		tone: "tone-neutral",
	},
};

function showBlockedMessage(code, reason) {
	const existing = document.getElementById("blockedOverlay");
	if (existing) existing.remove();

	const cfg = BLOCKED_CONFIG[code] || BLOCKED_CONFIG.generic;

	const overlay = document.createElement("div");
	overlay.id = "blockedOverlay";
	overlay.className = "blocked-overlay";

	overlay.innerHTML = `
		<div class="blocked-card ${cfg.tone}">
			<div class="blocked-icon">
				<i class="fas ${cfg.icon}"></i>
			</div>
			<h1 class="blocked-title">${esc(cfg.title)}</h1>
			<p class="blocked-reason">${esc(reason)}</p>
			<button class="blocked-back-btn">
				<i class="fas fa-arrow-left"></i> Go Back
			</button>
			<p class="blocked-help">
				<i class="fas fa-headset"></i> Need help? Ask any staff member
			</p>
		</div>
		<div class="blocked-footer">© Our Lady of Fatima University</div>`;

	overlay.querySelector(".blocked-back-btn").addEventListener("click", () => {
		overlay.remove();
		resetKiosk();
	});

	document.body.appendChild(overlay);
}

function showServerErrorMessage() {
	showBlockedMessage(
		"server_error",
		"A network or server error occurred. Please check your connection and try again.",
	);
}

const TOTAL_CD = 15;
let cdTimer = null,
	cdSecs = TOTAL_CD;

function renderWindowBadge(elId, windowNumber) {
	const el = document.getElementById(elId);
	if (!el) return;
	const value = Number(windowNumber);
	if (Number.isFinite(value) && value > 0) {
		el.textContent = `Window ${value}`;
		el.style.display = "inline-flex";
	} else {
		el.textContent = "";
		el.style.display = "none";
	}
}

function showTicket(data) {
	const o = state.office,
		s = state.service;
	const studentLevel = data.student_level || state.studentLevel;
	document.getElementById("s4OfficeName").textContent = o.name;
	document.getElementById("s4ServiceName").textContent = s
		? s.name
		: "General Queue";
	const visitorNameEl = document.getElementById("s4VisitorName");
	const visitorName = (data.visitor_name || "").trim();
	visitorNameEl.textContent = visitorName ? `For: ${visitorName}` : "";
	visitorNameEl.style.display = visitorName ? "" : "none";
	renderWindowBadge(
		"s4WindowLabel",
		data.window_number ?? data.window_id ?? null,
	);
	document.getElementById("s4TicketNumber").textContent = data.ticket_number;
	updatePriorityPills();

	let noteEl = document.getElementById("s4BreakNote");
	if (data.on_break) {
		if (!noteEl) {
			noteEl = document.createElement("div");
			noteEl.id = "s4BreakNote";
			noteEl.className = "break-banner break-banner-compact";
			document
				.querySelector("#screen4 .ticket-body")
				?.insertBefore(
					noteEl,
					document.querySelector("#screen4 .ticket-stats"),
				);
		}
		noteEl.innerHTML = `
			<div class="break-banner-icon"><i class="fas fa-mug-hot"></i></div>
			<div class="break-banner-text">
				<div class="break-banner-title">On break</div>
				<div class="break-banner-sub">Service resumes at <strong>${formatTime12(state.office.break_end)}</strong></div>
			</div>`;
		noteEl.style.display = "flex";
	} else if (noteEl) {
		noteEl.style.display = "none";
	}

	const position = (data.people_ahead ?? 0) + 1;
	document.getElementById("s4PeopleAhead").textContent = position;

	const avg = getAvgMinutes(state.office);
	const mins = data.est_wait_mins ?? (data.people_ahead ?? 0) * avg;
	document.getElementById("s4WaitTime").textContent =
		mins === 0 ? "Now" : `~${formatMinutes(mins)}`;

	document.getElementById("qrCode").innerHTML = "";
	const trackUrl =
		window.location.origin +
		window.location.pathname.replace(/\/[^/]*$/, "/") +
		"track.php?token=" +
		encodeURIComponent(data.tracking_token);

	new QRCode(document.getElementById("qrCode"), {
		text: trackUrl,
		width: 160,
		height: 160,
		colorDark: "#006633",
		colorLight: "#ffffff",
		correctLevel: QRCode.CorrectLevel.M,
	});

	state.history.push(4);
	showScreen(4);
	startCountdown();
}

function startCountdown() {
	clearInterval(cdTimer);
	cdSecs = TOTAL_CD;
	renderCountdown();
	cdTimer = setInterval(() => {
		cdSecs--;
		renderCountdown();
		if (cdSecs <= 0) {
			clearInterval(cdTimer);
			resetKiosk();
		}
	}, 1000);
}

function renderCountdown() {
	document.getElementById("countdownNum").textContent = cdSecs;
	document.getElementById("countdownBar").style.width =
		(cdSecs / TOTAL_CD) * 100 + "%";
}

function extendTime() {
	cdSecs = TOTAL_CD;
	renderCountdown();
}

function resetKiosk() {
	clearInterval(cdTimer);
	clearTimeout(idleTimer);
	clearTimeout(s4Timer);
	location.reload();
}

function backToStep1() {
	clearInterval(cdTimer);
	hideNameKeyboard({ immediate: true });
	state.office = null;
	state.service = null;
	state.isPriority = false;
	state.history = [0];
	document.getElementById("visitorName").value = "";
	updatePriorityPills();
	showScreen(0);
}

let idleTimer = null,
	s4Timer = null;
const IDLE_MS =
	typeof IDLE_TIMEOUT_MS !== "undefined" ? IDLE_TIMEOUT_MS : 60000;
const S4_IDLE_MS = IDLE_MS * 2;

function resetIdle() {
	clearTimeout(idleTimer);
	clearTimeout(s4Timer);
	document.getElementById("idleOverlay").classList.remove("show");

	if (state.screen === 4) {
		s4Timer = setTimeout(() => resetKiosk(), S4_IDLE_MS);
	} else {
		idleTimer = setTimeout(() => {
			if (state.screen === 3) {
				hideNameKeyboard({ clearName: true });
			}
			document.getElementById("idleOverlay").classList.add("show");
		}, nameKeyboardIsOpen() ? IDLE_MS * 2 : IDLE_MS);
	}
}

function dismissIdle() {
	document.getElementById("idleOverlay").classList.remove("show");
	resetKiosk();
	resetIdle();
}

["click", "touchstart", "keydown"].forEach((e) =>
	document.addEventListener(e, resetIdle, { passive: true }),
);

function esc(str) {
	const d = document.createElement("div");
	d.textContent = str;
	return d.innerHTML;
}

function checkAndPrepareConfirm() {
	const o = state.office;
	const s = state.service;
	const studentLevel = state.studentLevel;

	fetch("api/preview_ticket.php", {
		method: "POST",
		headers: { "Content-Type": "application/x-www-form-urlencoded" },
		body: `office_id=${o.id}&service_name=${encodeURIComponent(s ? s.name : "")}&student_level=${encodeURIComponent(studentLevel || "")}&is_priority=${state.isPriority ? 1 : 0}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`,
	})
		.then((r) => r.json())
		.then((data) => {
			if (!data.success) {
				const code = data.code || inferErrorCode(data.message || "");
				showBlockedMessage(code, data.message || "Unable to proceed.");
				return;
			}
			document.getElementById("s3OfficeName").textContent = o.name;
			document.getElementById("s3ServiceName").textContent = o.mode === "level-based"
				? (studentLevel === "shs" ? "SHS" : "College")
				: (s ? s.name : "General Queue");
			document.getElementById("s3QueuePreview").textContent = data.preview_ticket;
			updatePriorityPills();

			renderWaitEstimate(o, "s3WaitEstimate", data); 

			state.history.push(3);
			showScreen(3);
		})
		.catch(() => showServerErrorMessage());
}



renderOffices();
buildNameKeyboard();
resetIdle();
