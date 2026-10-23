const AJAX_URL = "api/profile_actions.php";

function restrictFutureDates() {
	const today = new Date().toISOString().split("T")[0]; // "YYYY-MM-DD"
	document.querySelectorAll('input[type="date"]').forEach((input) => {
		input.max = today;
	});
}

function linkDateRangeInputs(fromId, toId) {
	const fromEl = document.getElementById(fromId);
	const toEl = document.getElementById(toId);
	if (!fromEl || !toEl) return;

	const today = new Date().toISOString().split("T")[0];

	fromEl.addEventListener("change", () => {
		toEl.min = fromEl.value || "";
	});
	toEl.addEventListener("change", () => {
		fromEl.max = toEl.value || today;
	});
}

document.addEventListener("DOMContentLoaded", () => {
	restrictFutureDates();
	linkDateRangeInputs("activityDateFrom", "activityDateTo");
});

async function ajax(payload) {
	const res = await fetch(AJAX_URL, {
		method: "POST",
		headers: { "Content-Type": "application/json" },
		body: JSON.stringify({ ...payload, csrf_token: CSRF_TOKEN }),
		cache: "no-store",
	});
	return res.json();
}

function showToast(message, type = "success") {
	const container = document.getElementById("toast-container");
	const toast = document.createElement("div");
	const icon = type === "success" ? "fa-check-circle" : "fa-exclamation-circle";
	toast.className = `toast toast-${type}`;
	toast.innerHTML = `
				<i class="fas ${icon}"></i>
				<span>${message}</span>
				<button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
		`;
	container.appendChild(toast);
	requestAnimationFrame(() => toast.classList.add("show"));
	setTimeout(() => {
		toast.style.transition = "opacity 0.4s, transform 0.4s";
		toast.style.opacity = "0";
		toast.style.transform = "translateX(100%)";
		setTimeout(() => toast.remove(), 400);
	}, 4000);
}

function setBtnLoading(btnId, loading, defaultHtml) {
	const btn = document.getElementById(btnId);
	if (!btn) return;
	btn.disabled = loading;
	btn.innerHTML = loading
		? '<i class="fas fa-spinner fa-spin"></i> Please wait…'
		: defaultHtml;
}

const SECTIONS = ["profile", "security", "activity"];

function switchSection(section) {
	SECTIONS.forEach((s) => {
		document
			.getElementById("section-" + s)
			?.classList.toggle("active", s === section);
	});
	document.querySelectorAll(".profile-nav-item").forEach((btn, i) => {
		btn.classList.toggle("active", SECTIONS[i] === section);
	});
	history.replaceState(null, "", updateParam("section", section));
}

function updateParam(key, value) {
	const u = new URL(window.location.href);
	u.searchParams.set(key, value);
	return u.toString();
}

let isEditMode = false;

function toggleEditMode() {
	isEditMode = !isEditMode;
	const viewMode = document.getElementById("profileViewMode");
	const editMode = document.getElementById("profileEditMode");
	const btn = document.getElementById("editToggleBtn");

	if (isEditMode) {
		viewMode.style.display = "none";
		editMode.style.display = "block";
		btn.classList.add("active-edit");
	} else {
		cancelEdit();
	}
}

function cancelEdit() {
	isEditMode = false;
	document.getElementById("profileViewMode").style.display = "block";
	document.getElementById("profileEditMode").style.display = "none";
	const btn = document.getElementById("editToggleBtn");
	btn.classList.remove("active-edit");
	btn.innerHTML = '<i class="fas fa-pen"></i> Edit Profile';
}

async function submitEditProfile() {
	const fullNameInput = document.getElementById("edit_full_name");
	const fullName = fullNameInput.value.trim();
	fullNameInput.value = fullName;
	const email = document.getElementById("edit_email").value.trim();

	if (!fullNameInput.checkValidity()) {
		showToast("Full name must be 3 to 100 characters and use only letters, spaces, periods, hyphens, or apostrophes.", "error");
		return;
	}
	if (!email) {
		showToast("Email is required.", "error");
		return;
	}

	document.getElementById("confirm_identity_password").value = "";
	openConfirmModal();
}

function openConfirmModal() {
	const modal = document.getElementById("confirmProfileModal");
	modal.classList.add("show");
	setTimeout(
		() => document.getElementById("confirm_identity_password").focus(),
		100,
	);
}

function closeConfirmModal() {
	document.getElementById("confirmProfileModal").classList.remove("show");
	document.getElementById("confirm_identity_password").value = "";
}

async function confirmAndSaveProfile() {
	const fullNameInput = document.getElementById("edit_full_name");
	const fullName = fullNameInput.value.trim();
	fullNameInput.value = fullName;
	const email = document.getElementById("edit_email").value.trim();
	const confirmPassword = document.getElementById(
		"confirm_identity_password",
	).value;

	if (!fullNameInput.checkValidity()) {
		showToast("Full name must be 3 to 100 characters and use only letters, spaces, periods, hyphens, or apostrophes.", "error");
		return;
	}

	if (!confirmPassword) {
		showToast("Please enter your password.", "error");
		return;
	}

	console.log("Sending update:", { fullName, email, confirmPassword });

	const btn = document.getElementById("confirmProfileSaveBtn");
	btn.disabled = true;
	btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

	const data = await ajax({
		action: "update_profile",
		full_name: fullName,
		email,
		confirm_password: confirmPassword,
	});

	console.log("Response:", data);

	btn.disabled = false;
	btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';

	if (data.success) {
		const oldFullName = document.getElementById("viewName").textContent;
		const oldEmail = document.getElementById("viewEmail").textContent;

		closeConfirmModal();
		document.getElementById("viewName").textContent = fullName;
		document.getElementById("viewEmail").textContent = email;
		document.getElementById("cardName").textContent = fullName;
		updateAvatar(fullName);
		cancelEdit();
		showToast(data.message, "success");

		prependActivityItem(
			"PROFILE_UPDATE",
			"Profile Updated",
			"Updated own profile",
			{
				full_name: oldFullName,
				email: oldEmail,
			},
			{ full_name: fullName, email },
		);

		setTimeout(() => window.location.reload(), 1000);
	} else {
		showToast(data.message, "error");
		document.getElementById("confirm_identity_password").value = "";
		document.getElementById("confirm_identity_password").focus();
	}
}

document
	.getElementById("confirmProfileModal")
	.addEventListener("click", function (e) {
		if (e.target === this) closeConfirmModal();
	});

function updateAvatar(name) {
	const parts = name.trim().split(" ").filter(Boolean);
	const initials =
		parts.length >= 2
			? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
			: name.substring(0, 2).toUpperCase();
	document.getElementById("avatarDisplay").textContent = initials;
}

function togglePw(fieldId, btn) {
	const field = document.getElementById(fieldId);
	const icon = btn.querySelector("i");
	if (field.type === "password") {
		field.type = "text";
		icon.classList.replace("fa-eye", "fa-eye-slash");
	} else {
		field.type = "password";
		icon.classList.replace("fa-eye-slash", "fa-eye");
	}
}

function checkPasswordStrength(value) {
	const wrap = document.getElementById("pwStrengthWrap");
	const fill = document.getElementById("pwStrengthFill");
	const label = document.getElementById("pwStrengthLabel");
	const rules = {
		length: value.length >= 8,
		lowercase: /[a-z]/.test(value),
		uppercase: /[A-Z]/.test(value),
		number: /[0-9]/.test(value),
		special: /[^A-Za-z0-9]/.test(value),
	};

	if (!value) {
		wrap.style.display = "none";
		updatePasswordRules(rules);
		return;
	}
	wrap.style.display = "flex";

	const score = Object.values(rules).filter(Boolean).length;

	const levels = [
		{ pct: "20%", bg: "#c62828", text: "Very Weak", color: "#c62828" },
		{ pct: "40%", bg: "#e07b1a", text: "Weak", color: "#e07b1a" },
		{ pct: "60%", bg: "#f9a825", text: "Fair", color: "#f9a825" },
		{ pct: "80%", bg: "#2196f3", text: "Good", color: "#2196f3" },
		{ pct: "100%", bg: "#2a9d5c", text: "Strong", color: "#2a9d5c" },
	];

	const level = levels[Math.min(score - 1, 4)] ?? levels[0];
	fill.style.width = level.pct;
	fill.style.background = level.bg;
	label.textContent = level.text;
	label.style.color = level.color;
	updatePasswordRules(rules);
	checkPasswordMatch();
}

function updatePasswordRules(rules) {
	document.querySelectorAll("#pwChecklist li").forEach((item) => {
		const passed = Boolean(rules[item.dataset.rule]);
		item.classList.toggle("passed", passed);
		item.querySelector("i").className = passed
			? "fas fa-check-circle"
			: "far fa-circle";
	});
}

function checkPasswordMatch() {
	const newPw = document.getElementById("new_password").value;
	const confirm = document.getElementById("confirm_password").value;
	const feedback = document.getElementById("pwMatchFeedback");

	if (!confirm) {
		feedback.textContent = "";
		feedback.className = "pw-match-feedback";
		return;
	}

	const matches = newPw === confirm;
	feedback.textContent = matches ? "Passwords match" : "Passwords do not match";
	feedback.className = `pw-match-feedback ${matches ? "is-valid" : "is-invalid"}`;
}

let pendingPasswordChange = null;

async function submitChangePassword() {
	const current = document.getElementById("current_password").value;
	const newPw = document.getElementById("new_password").value;
	const confirm = document.getElementById("confirm_password").value;

	if (!current) return showToast("Current password is required.", "error");
	if (!newPw) return showToast("New password is required.", "error");

	const strongPassword =
		newPw.length >= 8 &&
		/[a-z]/.test(newPw) &&
		/[A-Z]/.test(newPw) &&
		/[0-9]/.test(newPw) &&
		/[^A-Za-z0-9]/.test(newPw);
	if (!strongPassword)
		return showToast("Password must meet all strength requirements.", "error");
	if (!confirm) return showToast("Please confirm your new password.", "error");
	if (newPw !== confirm) return showToast("Passwords do not match.", "error");
	if (newPw === current) {
		return showToast("New password must be different from your current password.", "error");
	}

	if (REQUIRE_PASSWORD_OTP) {
		setBtnLoading(
			"changePasswordBtn",
			true,
			'<i class="fas fa-lock"></i> Update Password',
		);
		const otpRequest = await ajax({
			action: "request_password_change_otp",
			current_password: current,
			new_password: newPw,
		});
		setBtnLoading(
			"changePasswordBtn",
			false,
			'<i class="fas fa-lock"></i> Update Password',
		);

		if (!otpRequest.success) return showToast(otpRequest.message, "error");

		pendingPasswordChange = { current, newPw, confirm };
		openOtpModal();
		showToast(otpRequest.message, "success");
		return;
	}

	await submitPasswordChangeRequest({
		current,
		newPw,
		confirm,
		verificationOtp: "",
	});
}

function openOtpModal() {
	const modal = document.getElementById("otpVerificationModal");
	modal.classList.add("show");
	const input = document.getElementById("modal_password_otp");
	input.value = "";
	setTimeout(() => input.focus(), 100);
}

function closeOtpModal() {
	document.getElementById("otpVerificationModal").classList.remove("show");
	document.getElementById("modal_password_otp").value = "";
}

document
	.getElementById("otpVerificationModal")
	?.addEventListener("click", function (e) {
		if (e.target === this) closeOtpModal();
	});

async function confirmOtpAndChangePassword() {
	const verificationOtp = document
		.getElementById("modal_password_otp")
		.value.trim();
	if (!/^\d{6}$/.test(verificationOtp)) {
		return showToast(
			"Enter the 6-digit verification code sent to your email.",
			"error",
		);
	}
	if (!pendingPasswordChange) return closeOtpModal();

	const btn = document.getElementById("otpModalSubmitBtn");
	const originalHtml = btn.innerHTML;
	btn.disabled = true;
	btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying…';

	const ok = await submitPasswordChangeRequest({
		...pendingPasswordChange,
		verificationOtp,
	});

	btn.disabled = false;
	btn.innerHTML = originalHtml;
	if (ok) closeOtpModal();
}

async function resendPasswordOtp(e) {
	e.preventDefault();
	if (!pendingPasswordChange) return;
	const link = document.getElementById("resendOtpLink");
	const original = link.textContent;
	link.textContent = "Sending…";

	const otpRequest = await ajax({
		action: "request_password_change_otp",
		current_password: pendingPasswordChange.current,
		new_password: pendingPasswordChange.newPw,
	});

	link.textContent = original;
	showToast(otpRequest.message, otpRequest.success ? "success" : "error");
}

async function submitPasswordChangeRequest({
	current,
	newPw,
	confirm,
	verificationOtp,
}) {
	setBtnLoading(
		"changePasswordBtn",
		true,
		'<i class="fas fa-lock"></i> Update Password',
	);

	const data = await ajax({
		action: "change_password",
		current_password: current,
		new_password: newPw,
		confirm_password: confirm,
		verification_otp: verificationOtp,
	});

	setBtnLoading(
		"changePasswordBtn",
		false,
		'<i class="fas fa-lock"></i> Update Password',
	);

	if (data.success) {
		document.getElementById("current_password").value = "";
		document.getElementById("new_password").value = "";
		document.getElementById("confirm_password").value = "";
		pendingPasswordChange = null;
		document.getElementById("pwStrengthWrap").style.display = "none";
		document.getElementById("pwMatchFeedback").textContent = "";
		updatePasswordRules({});
		showToast(data.message, "success");
		prependActivityItem(
			"PASSWORD_CHANGE",
			"Password Changed",
			"Changed account password",
		);
		return true;
	}

	showToast(data.message, "error");
	return false;
}

const FIELD_LABELS = {
	full_name: "Full Name",
	email: "Email",
	role: "Role",
	status: "Status",
	office_id: "Office",
	office_name: "Office",
	is_active: "Active",
	name: "Name",
	description: "Description",
	price: "Price",
	duration: "Duration",
	queue_mode: "Queue Mode",
	is_open: "Open",
	open_time: "Open Time",
	close_time: "Close Time",
};

function fieldLabel(key) {
	return (
		FIELD_LABELS[key] ??
		key.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase())
	);
}

function formatVal(val, key = "") {
	if (val === null || val === undefined || val === "") return "—";
	if (Array.isArray(val)) return val.map((item) => key === "permission_keys" ? String(item).replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase()) : String(item)).join(", ");
	if (val === true || val === 1 || val === "1") return "Yes";
	if (val === false || val === 0 || val === "0") return "No";
	return String(val);
}

function buildDiffHtml(oldObj, newObj) {
	if (!oldObj && !newObj) return "";

	const parse = (v) => {
		if (!v) return {};
		if (typeof v === "string") {
			try {
				return JSON.parse(v);
			} catch {
				return {};
			}
		}
		return typeof v === "object" ? v : {};
	};

	const before = parse(oldObj);
	const after = parse(newObj);
	const allKeys = new Set([...Object.keys(before), ...Object.keys(after)]);
	const rows = [];

	for (const key of allKeys) {
		const bVal = formatVal(before[key], key);
		const aVal = formatVal(after[key], key);
		if (bVal === aVal) continue;

		if (!(key in before)) {
			rows.push(`
				<div class="diff-row diff-added">
					<span class="diff-field">${escHtml(fieldLabel(key))}</span>
					<span class="diff-arrow"><i class="fas fa-plus"></i></span>
					<span class="diff-new">${escHtml(aVal)}</span>
				</div>`);
		} else if (!(key in after)) {
			rows.push(`
				<div class="diff-row diff-removed">
					<span class="diff-field">${escHtml(fieldLabel(key))}</span>
					<span class="diff-arrow"><i class="fas fa-minus"></i></span>
					<span class="diff-old">${escHtml(bVal)}</span>
				</div>`);
		} else {
			rows.push(`
				<div class="diff-row diff-changed">
					<span class="diff-field">${escHtml(fieldLabel(key))}</span>
					<span class="diff-old">${escHtml(bVal)}</span>
					<span class="diff-arrow"><i class="fas fa-arrow-right"></i></span>
					<span class="diff-new">${escHtml(aVal)}</span>
				</div>`);
		}
	}

	if (!rows.length) return "";
	return `<div class="activity-diff">${rows.join("")}</div>`;
}

let activityOffset = 20;
let loadingMore = false;
let activitySearchDebounce;
let activityFilters = { search: "", date_from: "", date_to: "" };

document.getElementById("activitySearchInput")?.addEventListener("input", function () {
	document.getElementById("activitySearchClear").style.opacity = this.value ? "1" : "0";
	clearTimeout(activitySearchDebounce);
	activitySearchDebounce = setTimeout(applyActivityFilters, 280);
});

function applyActivityFilters() {
	activityFilters.search = document.getElementById("activitySearchInput").value.trim();
	activityFilters.date_from = document.getElementById("activityDateFrom").value;
	activityFilters.date_to = document.getElementById("activityDateTo").value;
	fetchActivity(true);
}

function clearActivitySearch() {
	document.getElementById("activitySearchInput").value = "";
	document.getElementById("activitySearchClear").style.opacity = "0";
	applyActivityFilters();
}

function clearActivityFilters() {
	document.getElementById("activitySearchInput").value = "";
	document.getElementById("activityDateFrom").value = "";
	document.getElementById("activityDateTo").value = "";
	document.getElementById("activitySearchClear").style.opacity = "0";
	activityFilters = { search: "", date_from: "", date_to: "" };
	fetchActivity(true);
}

async function fetchActivity(reset = false) {
	if (loadingMore) return;
	if (reset) activityOffset = 0;
	loadingMore = true;

	const container = document.getElementById("activityResultsContainer");
	if (reset) {
		container.innerHTML =
			'<div class="table-loading" style="display:flex;position:static;padding:24px 0;"><div class="table-spinner"></div></div>';
	}

	const data = await ajax({
		action: "get_activity",
		offset: activityOffset,
		limit: 20,
		search: activityFilters.search,
		date_from: activityFilters.date_from,
		date_to: activityFilters.date_to,
	});

	loadingMore = false;

	if (!data.success) {
		if (reset) container.innerHTML = "";
		return;
	}

	const badge = document.getElementById("activityCountBadge");
	if (badge) badge.textContent = data.total_count.toLocaleString() + " total";

	if (reset) {
		if (!data.items.length) {
			container.innerHTML = `
				<div class="activity-empty" id="activityEmptyState">
					<div class="activity-empty-icon"><i class="fas fa-history"></i></div>
					<div class="activity-empty-title">No matching activity</div>
					<div class="activity-empty-desc">Try adjusting your search or date filters.</div>
				</div>`;
			return;
		}
		container.innerHTML = `<div class="activity-list" id="activityList"></div>`;
	}

	const list = document.getElementById("activityList");
	if (!list) return;

	data.items.forEach((item) => {
		const iconMeta = ACTIVITY_ICONS[item.activity_type] ?? { icon: "fa-circle", color: "#90a4ae" };
		const diffHtml = buildDiffHtml(item.before_values, item.after_values);
		const el = document.createElement("div");
		el.className = "activity-item";
		el.innerHTML = `
			<div class="activity-icon-wrap" style="background:${iconMeta.color}1a; color:${iconMeta.color}">
				<i class="fas ${iconMeta.icon}"></i>
			</div>
			<div class="activity-info">
				<div class="activity-label">${escHtml(item.activity_label)}</div>
				${item.description ? `<div class="activity-desc">${escHtml(item.description)}</div>` : ""}
				${diffHtml}
				<div class="activity-meta">
					<span><i class="fas fa-clock"></i> ${escHtml(item.formatted_time)}</span>
				</div>
			</div>
		`;
		list.appendChild(el);
	});

	activityOffset += data.items.length;

	let loadMoreWrap = document.getElementById("activityLoadMoreWrap");
	if (activityOffset < data.total_count) {
		if (!loadMoreWrap) {
			loadMoreWrap = document.createElement("div");
			loadMoreWrap.className = "activity-load-more";
			loadMoreWrap.id = "activityLoadMoreWrap";
			loadMoreWrap.innerHTML = `<button class="btn-outline" id="loadMoreBtn" onclick="loadMoreActivity()"><i class="fas fa-chevron-down"></i> Load More</button>`;
			container.appendChild(loadMoreWrap);
		}
	} else if (loadMoreWrap) {
		loadMoreWrap.remove();
	}
}

async function loadMoreActivity() {
	if (loadingMore) return;
	const btn = document.getElementById("loadMoreBtn");
	if (btn) {
		btn.disabled = true;
		btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading…';
	}
	await fetchActivity(false);
}

function escHtml(str) {
	return String(str)
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;");
}

function prependActivityItem(
	type,
	label,
	description = "",
	beforeValues = {},
	afterValues = {},
) {
	const list = document.getElementById("activityList");
	if (!list) return;

	const emptyEl = document.querySelector(".activity-empty");
	if (emptyEl) emptyEl.remove();

	const iconMeta = ACTIVITY_ICONS[type] ?? {
		icon: "fa-circle",
		color: "#90a4ae",
	};
	const diffHtml = buildDiffHtml(beforeValues, afterValues);
	const now = new Date();
	const timeStr =
		now.toLocaleDateString("en-US", {
			month: "short",
			day: "2-digit",
			year: "numeric",
		}) +
		" " +
		now.toLocaleTimeString("en-US", {
			hour: "2-digit",
			minute: "2-digit",
			second: "2-digit",
		});

	const el = document.createElement("div");
	el.className = "activity-item activity-item-new";
	el.innerHTML = `
				<div class="activity-icon-wrap" style="background:${iconMeta.color}1a; color:${iconMeta.color}">
						<i class="fas ${iconMeta.icon}"></i>
				</div>
				<div class="activity-info">
						<div class="activity-label">${escHtml(label)}</div>
						${description ? `<div class="activity-desc">${escHtml(description)}</div>` : ""}
						${diffHtml}
						<div class="activity-meta">
								<span><i class="fas fa-clock"></i> ${escHtml(timeStr)}</span>
						</div>
				</div>
		`;

	list.prepend(el);

	requestAnimationFrame(() => el.classList.add("activity-item-highlight"));
	setTimeout(() => el.classList.remove("activity-item-highlight"), 2000);

	const badge = document.querySelector(".activity-count-badge");
	if (badge) {
		const current = parseInt(badge.textContent.replace(/\D/g, "")) || 0;
		badge.textContent = (current + 1).toLocaleString() + " total";
	}
}