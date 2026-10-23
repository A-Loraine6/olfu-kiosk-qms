<?php
require_once __DIR__ . '/includes/config_loader.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/request_validation.php';
require_once __DIR__ . '/api/activity_logger.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$currentUser = getCurrentUser();
$csrf_token  = getCsrfToken();
$requiresPasswordOtp = ($currentUser['role_id'] ?? null) === 1
    || strtolower(trim((string) ($currentUser['role'] ?? ''))) === 'admin'
    || in_array(strtolower(trim((string) ($currentUser['role'] ?? ''))), ['department head', 'department_head', 'department-head'], true);

$activeSection = requestEnum($_GET, 'section', ['profile', 'security', 'activity'], false) ?: 'profile';

$activityLogs = getActivityLogs(
    $db->getConnection(),
    $currentUser['id'], null, null, null, null, null, 20, 0
);
$totalLogs = getActivityLogsCount($db->getConnection(), $currentUser['id']);

$activityIcons = [
    'LOGIN'                    => ['icon' => 'fa-sign-in-alt',  'color' => '#2196f3'],
    'LOGIN_FAILED'             => ['icon' => 'fa-sign-in-alt',  'color' => '#d32f2f'],
    'LOGOUT'                   => ['icon' => 'fa-sign-out-alt', 'color' => '#78909c'],
    'AUTH_DENIED'              => ['icon' => 'fa-user-slash',   'color' => '#d32f2f'],
    'PERMISSION_DENIED'        => ['icon' => 'fa-ban',          'color' => '#ef6c00'],
    'API_SUSPICIOUS'           => ['icon' => 'fa-shield-alt',   'color' => '#7b1fa2'],
    'DATA_EXPORT'              => ['icon' => 'fa-file-export',  'color' => '#00838f'],
    'ADMIN_ACTION'             => ['icon' => 'fa-user-shield',  'color' => '#455a64'],
    'CONFIG_CHANGE'            => ['icon' => 'fa-cogs',         'color' => '#2e7d32'],
    'STAFF_ADD'                => ['icon' => 'fa-user-plus',    'color' => '#2a9d5c'],
    'STAFF_EDIT'               => ['icon' => 'fa-user-edit',    'color' => '#e07b1a'],
    'STAFF_DELETE'             => ['icon' => 'fa-user-minus',   'color' => '#c62828'],
    'STAFF_ACTIVATE'           => ['icon' => 'fa-toggle-on',    'color' => '#2a9d5c'],
    'STAFF_DEACTIVATE'         => ['icon' => 'fa-toggle-off',   'color' => '#e07b1a'],
    'PASSWORD_RESET'           => ['icon' => 'fa-key',          'color' => '#7b1fa2'],
    'ROLE_CHANGE'              => ['icon' => 'fa-user-tag',     'color' => '#5e35b1'],
    'ROLE_PERMISSION_CHANGE'   => ['icon' => 'fa-key',           'color' => '#5e35b1'],
    'OFFICE_ASSIGN'            => ['icon' => 'fa-building-user','color' => '#00695c'],
    'SERVICE_ADD'              => ['icon' => 'fa-plus-circle',  'color' => '#2a9d5c'],
    'SERVICE_EDIT'             => ['icon' => 'fa-pen',          'color' => '#e07b1a'],
    'SERVICE_DELETE'           => ['icon' => 'fa-trash',        'color' => '#c62828'],
    'SERVICE_TOGGLE_STATUS'    => ['icon' => 'fa-exchange-alt', 'color' => '#2196f3'],
    'OFFICE_QUEUE_MODE_TOGGLE' => ['icon' => 'fa-random',       'color' => '#2196f3'],
    'SETTINGS_SCHEDULE_UPDATE' => ['icon' => 'fa-clock',        'color' => '#00838f'],
    'SETTINGS_QUEUE_UPDATE'    => ['icon' => 'fa-ticket-alt',   'color' => '#00838f'],
    'SETTINGS_KIOSK_UPDATE'    => ['icon' => 'fa-tablet-alt',   'color' => '#00838f'],
    'PROFILE_UPDATE'           => ['icon' => 'fa-user-edit',    'color' => '#2196f3'],
    'PASSWORD_CHANGE'          => ['icon' => 'fa-key',          'color' => '#7b1fa2'],
];

function getInitials(string $name): string
{
    $parts = explode(' ', trim($name));
    if (count($parts) >= 2) return strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
    return strtoupper(substr($name, 0, 2));
}

function buildDiffHtml(array $before, array $after): string
{
    $fieldLabels = [
        'full_name'   => 'Full Name',
        'email'       => 'Email',
        'role'        => 'Role',
        'status'      => 'Status',
        'office_id'   => 'Office',
        'office_name' => 'Office',
        'is_active'   => 'Active',
        'name'        => 'Name',
        'description' => 'Description',
        'price'       => 'Price',
        'duration'    => 'Duration',
        'queue_mode'  => 'Queue Mode',
        'is_open'     => 'Open',
        'open_time'   => 'Open Time',
        'close_time'  => 'Close Time',
    ];

    $label = fn(string $k): string =>
    $fieldLabels[$k] ?? ucwords(str_replace('_', ' ', $k));

    $fmt = function ($v): string {
        if ($v === null || $v === '')               return '—';
        if (is_array($v))                           return implode(', ', array_map(fn($item) => ucwords(str_replace('_', ' ', (string) $item)), $v));
        if ($v === true  || $v === 1 || $v === '1') return 'Yes';
        if ($v === false || $v === 0 || $v === '0') return 'No';
        return (string) $v;
    };

    $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));
    $rows    = [];

    foreach ($allKeys as $key) {
        $bVal = $fmt($before[$key] ?? null);
        $aVal = $fmt($after[$key]  ?? null);

        if ($bVal === $aVal) continue;

        if (!array_key_exists($key, $before)) {
            $rows[] = sprintf(
                '<div class="diff-row diff-added">'
                    . '<span class="diff-field">%s</span>'
                    . '<span class="diff-arrow"><i class="fas fa-plus"></i></span>'
                    . '<span class="diff-new">%s</span>'
                    . '</div>',
                htmlspecialchars($label($key)),
                htmlspecialchars($aVal)
            );
        } elseif (!array_key_exists($key, $after)) {
            $rows[] = sprintf(
                '<div class="diff-row diff-removed">'
                    . '<span class="diff-field">%s</span>'
                    . '<span class="diff-arrow"><i class="fas fa-minus"></i></span>'
                    . '<span class="diff-old">%s</span>'
                    . '</div>',
                htmlspecialchars($label($key)),
                htmlspecialchars($bVal)
            );
        } else {
            $rows[] = sprintf(
                '<div class="diff-row diff-changed">'
                    . '<span class="diff-field">%s</span>'
                    . '<span class="diff-old">%s</span>'
                    . '<span class="diff-arrow"><i class="fas fa-arrow-right"></i></span>'
                    . '<span class="diff-new">%s</span>'
                    . '</div>',
                htmlspecialchars($label($key)),
                htmlspecialchars($bVal),
                htmlspecialchars($aVal)
            );
        }
    }

    if (empty($rows)) return '';
    return '<div class="activity-diff">' . implode('', $rows) . '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="profile-container">

        <?php include 'includes/sidebar.php'; ?>

        <div class="profile-body">

            <header class="dashboard-header">
                <div class="header-left">
                    <h2 class="header-page-title">
                        <i class="fas fa-user-circle"></i> My Profile
                    </h2>
                </div>
            </header>

            <div class="dashboard-main">
                <div class="dashboard-content">
                    <div class="profile-grid">

                        <aside class="profile-sidebar">
                            <div class="profile-card">
                                <div class="profile-avatar-wrap">
                                    <div class="profile-avatar" id="avatarDisplay">
                                        <?php echo getInitials($currentUser['full_name']); ?>
                                    </div>
                                </div>
                                <div class="profile-card-name" id="cardName"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                                <div class="profile-card-badges">
                                    <span class="profile-badge badge-role">
                                        <i class="fas fa-shield-alt"></i>
                                        <?php echo e(ucfirst((string) ($currentUser['role'] ?? 'Staff'))); ?>
                                    </span>
                                    <?php if (!empty($currentUser['office_name'])): ?>
                                        <span class="profile-badge badge-office">
                                            <i class="fas fa-building"></i>
                                            <?php echo htmlspecialchars($currentUser['office_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <nav class="profile-nav">
                                <button class="profile-nav-item <?php echo $activeSection === 'profile'  ? 'active' : ''; ?>"
                                    onclick="switchSection('profile')">
                                    <span class="pnav-icon"><i class="fas fa-id-card"></i></span>
                                    <span class="pnav-label">Profile Info</span>
                                    <i class="fas fa-chevron-right pnav-arrow"></i>
                                </button>
                                <button class="profile-nav-item <?php echo $activeSection === 'security' ? 'active' : ''; ?>"
                                    onclick="switchSection('security')">
                                    <span class="pnav-icon"><i class="fas fa-lock"></i></span>
                                    <span class="pnav-label">Password & Security</span>
                                    <i class="fas fa-chevron-right pnav-arrow"></i>
                                </button>
                                <button class="profile-nav-item <?php echo $activeSection === 'activity' ? 'active' : ''; ?>"
                                    onclick="switchSection('activity')">
                                    <span class="pnav-icon"><i class="fas fa-history"></i></span>
                                    <span class="pnav-label">Account Activity</span>
                                    <i class="fas fa-chevron-right pnav-arrow"></i>
                                </button>
                            </nav>
                        </aside>

                        <div class="profile-main">

                            <div id="section-profile" class="profile-section <?php echo $activeSection === 'profile' ? 'active' : ''; ?>">
                                <div class="section-header-row">
                                    <div>
                                        <h3 class="section-title-sm">Profile Information</h3>
                                        <p class="section-subtitle">View and update your personal details</p>
                                    </div>
                                    <?php if (strtolower(trim((string) ($currentUser['role'] ?? ''))) !== 'staff'): ?>
                                        <button class="btn-edit-toggle" id="editToggleBtn" onclick="toggleEditMode()">
                                            <i class="fas fa-pen"></i> Edit Profile
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <div id="profileViewMode" class="profile-view-grid">
                                    <div class="profile-info-item">
                                        <div class="info-label"><i class="fas fa-user"></i> Full Name</div>
                                        <div class="info-value" id="viewName"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                                    </div>
                                    <div class="profile-info-item">
                                        <div class="info-label"><i class="fas fa-envelope"></i> Email Address</div>
                                        <div class="info-value" id="viewEmail"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                                    </div>
                                    <?php if (!empty($currentUser['office_name'])): ?>
                                        <div class="profile-info-item">
                                            <div class="info-label"><i class="fas fa-building"></i> Assigned Office</div>
                                            <div class="info-value"><?php echo htmlspecialchars($currentUser['office_name']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if (strtolower(trim((string) ($currentUser['role'] ?? ''))) !== 'staff'): ?>
                                    <div id="profileEditMode" class="profile-edit-form" style="display:none;">
                                        <div class="form-row-2">
                                            <div class="form-group">
                                                <label for="edit_full_name">Full Name <span class="required">*</span></label>
                                                <input type="text" id="edit_full_name"
                                                    value="<?php echo htmlspecialchars($currentUser['full_name']); ?>"
                                                    placeholder="Enter your full name" required minlength="3" maxlength="100"
                                                    pattern="(?:\p{L}\p{M}*)+(?:(?: +|[\-'])(?:\p{L}\p{M}*)+|\. *(?:\p{L}\p{M}*)+)*\.?"
                                                    title="Use letters, spaces, periods, hyphens, or apostrophes.">
                                            </div>
                                            <div class="form-group">
                                                <label for="edit_email">Email Address <span class="required">*</span></label>
                                                <input type="email" id="edit_email"
                                                    value="<?php echo htmlspecialchars($currentUser['email']); ?>"
                                                    placeholder="Enter your email">
                                            </div>
                                        </div>
                                        <div class="form-actions">
                                            <button class="btn btn-outline" onclick="cancelEdit()">Cancel</button>
                                            <button class="btn btn-primary" id="saveProfileBtn" onclick="submitEditProfile()">
                                                <i class="fas fa-save"></i> Save Changes
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div id="section-security" class="profile-section <?php echo $activeSection === 'security' ? 'active' : ''; ?>">
                                <div class="section-header-row">
                                    <div>
                                        <h3 class="section-title-sm">Password & Security</h3>
                                        <p class="section-subtitle">Keep your account safe with a strong password</p>
                                    </div>
                                </div>

                                <div class="security-info-bar">
                                    <i class="fas fa-info-circle"></i>
                                    <?php if (!empty($_SESSION['must_change_password'])): ?>
                                        Your password was reset by an administrator or department head. Please create a new strong password now.
                                    <?php else: ?>
                                        Use at least 8 characters with uppercase, lowercase, numbers, and a special character for a strong password.
                                    <?php endif; ?>
                                </div>

                                <div class="profile-edit-form">
                                    <div class="form-group">
                                        <label for="current_password">Current Password <span class="required">*</span></label>
                                        <div class="input-pw-wrap">
                                            <input type="password" id="current_password" placeholder="Enter your current password"
                                                >
                                            <button type="button" class="btn-pw-toggle" onclick="togglePw('current_password', this)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-row-2">
                                        <div class="form-group">
                                            <label for="new_password">New Password <span class="required">*</span></label>
                                            <div class="input-pw-wrap">
                                                <input type="password" id="new_password" placeholder="Enter new password"
                                                    oninput="checkPasswordStrength(this.value)">
                                                <button type="button" class="btn-pw-toggle" onclick="togglePw('new_password', this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="pw-strength-wrap" id="pwStrengthWrap" style="display:none;">
                                                <div class="pw-strength-bar">
                                                    <div class="pw-strength-fill" id="pwStrengthFill"></div>
                                                </div>
                                                <span class="pw-strength-label" id="pwStrengthLabel"></span>
                                            </div>
                                            <ul class="pw-checklist" id="pwChecklist" aria-live="polite">
                                                <li data-rule="length"><i class="far fa-circle"></i> At least 8 characters</li>
                                                <li data-rule="lowercase"><i class="far fa-circle"></i> One lowercase letter</li>
                                                <li data-rule="uppercase"><i class="far fa-circle"></i> One uppercase letter</li>
                                                <li data-rule="number"><i class="far fa-circle"></i> One number</li>
                                                <li data-rule="special"><i class="far fa-circle"></i> One special character</li>
                                            </ul>
                                        </div>
                                        <div class="form-group">
                                            <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                                            <div class="input-pw-wrap">
                                                <input type="password" id="confirm_password" placeholder="Re-enter new password"
                                                    oninput="checkPasswordMatch()">
                                                <button type="button" class="btn-pw-toggle" onclick="togglePw('confirm_password', this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="pw-match-feedback" id="pwMatchFeedback" aria-live="polite"></div>
                                        </div>
                                    </div>
                                    <div class="form-actions">
                                        <button class="btn btn-primary" id="changePasswordBtn" onclick="submitChangePassword()">
                                            <i class="fas fa-lock"></i> Update Password
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div id="section-activity" class="profile-section <?php echo $activeSection === 'activity' ? 'active' : ''; ?>">
                                <div class="section-header-row">
                                    <div>
                                        <h3 class="section-title-sm">Account Activity</h3>
                                        <p class="section-subtitle">Your recent actions in the system</p>
                                    </div>
                                    <span class="activity-count-badge" id="activityCountBadge"><?php echo number_format($totalLogs); ?> total</span>
                                </div>

                                <div class="activity-filter-bar">
                                    <div class="search-wrap">
                                        <i class="fas fa-search search-icon"></i>
                                        <input type="text" id="activitySearchInput" class="filter-input search-input"
                                            placeholder="Search activity…" autocomplete="off">
                                        <button class="search-clear" id="activitySearchClear" onclick="clearActivitySearch()" title="Clear">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="filter-group">
                                        <div class="date-range-group">
                                            <input type="date" id="activityDateFrom" class="filter-input" onchange="applyActivityFilters()">
                                            <span class="date-sep">to</span>
                                            <input type="date" id="activityDateTo" class="filter-input" onchange="applyActivityFilters()">
                                        </div>
                                        <button class="btn btn-outline" id="activityClearFiltersBtn" onclick="clearActivityFilters()">
                                            <i class="fas fa-times-circle"></i> Clear
                                        </button>
                                    </div>
                                </div>

                                <div id="activityResultsContainer">
                                    <?php if (empty($activityLogs)): ?>
                                        <div class="activity-empty" id="activityEmptyState">
                                            <div class="activity-empty-icon"><i class="fas fa-history"></i></div>
                                            <div class="activity-empty-title">No activity yet</div>
                                            <div class="activity-empty-desc">Your actions will appear here once you start using the system.</div>
                                        </div>
                                    <?php else: ?>
                                        <div class="activity-list" id="activityList">
                                            <?php foreach ($activityLogs as $log):
                                                $meta      = $activityIcons[$log['activity_type']] ?? ['icon' => 'fa-circle', 'color' => '#90a4ae'];
                                                $formatted = formatActivityLog($log);
                                                $diffHtml  = buildDiffHtml($log['before_values'] ?? [], $log['after_values'] ?? []);
                                            ?>
                                                <div class="activity-item">
                                                    <div class="activity-icon-wrap" style="background:<?php echo $meta['color']; ?>1a; color:<?php echo $meta['color']; ?>">
                                                        <i class="fas <?php echo $meta['icon']; ?>"></i>
                                                    </div>
                                                    <div class="activity-info">
                                                        <div class="activity-label"><?php echo htmlspecialchars($formatted['activity_label']); ?></div>
                                                        <?php if ($log['description']): ?>
                                                            <div class="activity-desc"><?php echo htmlspecialchars($log['description']); ?></div>
                                                        <?php endif; ?>
                                                        <?php echo $diffHtml; ?>
                                                        <div class="activity-meta">
                                                            <span><i class="fas fa-clock"></i> <?php echo $formatted['formatted_time']; ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if ($totalLogs > 20): ?>
                                            <div class="activity-load-more" id="activityLoadMoreWrap">
                                                <button class="btn-outline" id="loadMoreBtn" onclick="loadMoreActivity()">
                                                    <i class="fas fa-chevron-down"></i> Load More
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="confirmProfileModal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2><i class="fas fa-lock"></i> Confirm Your Password</h2>
                <button class="modal-close" onclick="closeConfirmModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>To save changes to your name or email, please enter your current password.</p>
                <div class="form-group" style="margin-top:16px; margin-bottom:0;">
                    <label for="confirm_identity_password">Current Password <span class="required">*</span></label>
                    <div class="input-pw-wrap">
                        <input type="password" id="confirm_identity_password"
                            placeholder="Enter your password">
                        <button type="button" class="btn-pw-toggle"
                            onclick="togglePw('confirm_identity_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeConfirmModal()">Cancel</button>
                <button class="btn btn-primary" id="confirmProfileSaveBtn" onclick="confirmAndSaveProfile()" data-action="confirm">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <div class="modal" id="otpVerificationModal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <div class="otp-modal-head">
                    <span class="otp-modal-icon"><i class="fas fa-shield-alt"></i></span>
                    <h2>Verify Your Email</h2>
                </div>
                <button class="modal-close" onclick="closeOtpModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Enter the 6-digit code sent to <strong><?php echo htmlspecialchars($currentUser['email']); ?></strong> to confirm your password change.</p>
                <div class="form-group" style="margin-top:18px; margin-bottom:0;">
                    <label for="modal_password_otp">Verification Code <span class="required">*</span></label>
                    <input type="text" id="modal_password_otp" inputmode="numeric"
                        pattern="[0-9]{6}" maxlength="6" placeholder="000000"
                        class="otp-input-lg" autocomplete="one-time-code">
                </div>
                <a href="#" id="resendOtpLink" class="otp-resend-link" onclick="resendPasswordOtp(event)">Didn't get it? Resend code</a>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeOtpModal()">Cancel</button>
                <button class="btn btn-primary" id="otpModalSubmitBtn" onclick="confirmOtpAndChangePassword()">
                    <i class="fas fa-check"></i> Verify & Update
                </button>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <script>
        const CSRF_TOKEN = <?php echo json_encode($csrf_token); ?>;
        const TOTAL_LOGS = <?php echo (int) $totalLogs; ?>;
        const ACTIVITY_ICONS = <?php echo json_encode($activityIcons); ?>;
        const REQUIRE_PASSWORD_OTP = <?php echo json_encode($requiresPasswordOtp); ?>;
    </script>
    <script src="scripts/profile.js"></script>
    <script src="scripts/modal-shortcuts.js"></script>
</body>

</html>