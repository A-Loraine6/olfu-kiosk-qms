<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/api/staff_functions.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$currentUser = getCurrentUser();
$csrf_token  = getCsrfToken();

$offices    = getAvailableOffices($db->getConnection());
$staff      = getAllStaff($db->getConnection());
$totalStaff = count($staff);
$activeStaff   = count(array_filter($staff, fn($s) => $s['is_active']));
$inactiveStaff = $totalStaff - $activeStaff;

$pageLimit = 10;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/staff.css">
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="staff-container">

        <?php include 'includes/sidebar.php'; ?>

        <div class="staff-body">

            <header class="dashboard-header">
                <div class="header-left">
                    <h2 class="header-page-title">
                        <i class="fas fa-users"></i> Staff Management
                    </h2>
                </div>
                <div class="header-right">
                    <button class="btn-primary" onclick="openModal('addStaffModal')">
                        <i class="fas fa-plus"></i> Add New Staff
                    </button>
                </div>
            </header>

            <div class="dashboard-main">
                <div class="dashboard-content">

                    <h2 class="section-title"><i class="fas fa-users"></i> Staff Overview</h2>
                    <div class="summary-cards">
                        <div class="card card-total-staff">
                            <div class="card-header">
                                <div class="card-icon"><i class="fas fa-users"></i></div>
                                <div class="card-label">Total Staff</div>
                            </div>
                            <div class="card-value" id="stat-total"><?php echo $totalStaff; ?></div>
                            <div class="card-description">All registered staff members</div>
                        </div>
                        <div class="card card-active-staff">
                            <div class="card-header">
                                <div class="card-icon"><i class="fas fa-user-check"></i></div>
                                <div class="card-label">Active Staff</div>
                            </div>
                            <div class="card-value" id="stat-active"><?php echo $activeStaff; ?></div>
                            <div class="card-description">Currently active staff members</div>
                        </div>
                        <div class="card card-inactive-staff">
                            <div class="card-header">
                                <div class="card-icon"><i class="fas fa-user-slash"></i></div>
                                <div class="card-label">Inactive Staff</div>
                            </div>
                            <div class="card-value" id="stat-inactive"><?php echo $inactiveStaff; ?></div>
                            <div class="card-description">Currently inactive staff members</div>
                        </div>
                    </div>

                    <h2 class="section-title"><i class="fas fa-th-list"></i> All Staff</h2>
                    <div class="staff-table-section">
                        <div class="staff-table-toolbar">
                            <div class="search-wrap">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" id="searchInput" class="filter-input search-input"
                                    placeholder="Search name, email, office…"
                                    autocomplete="off">
                                <button class="search-clear" id="searchClear" onclick="clearSearch()" title="Clear">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <div class="filter-group">
                                <select id="filterOffice" class="filter-select" onchange="applyFilters()">
                                    <option value="">All Offices</option>
                                    <?php foreach ($offices as $o): ?>
                                        <option value="<?php echo htmlspecialchars($o); ?>">
                                            <?php echo htmlspecialchars($o); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select id="filterStatus" class="filter-select" onchange="applyFilters()">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <table class="staff-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Office</th>
                                    <th>Window</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="staffTableBody">
                                <?php if (empty($staff)): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <div class="empty-state-icon"><i class="fas fa-users"></i></div>
                                                <div class="empty-state-title">No staff found</div>
                                                <div class="empty-state-message">Add a new staff member to get started.</div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($staff as $member): ?>
                                        <tr>
                                            <td>
                                                <div class="staff-name-cell">
                                                    <div class="staff-avatar">
                                                        <?php echo strtoupper(mb_substr($member['full_name'], 0, 1)); ?>
                                                    </div>
                                                    <div class="svc-name"><?php echo htmlspecialchars($member['full_name']); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                                            <td><?php echo htmlspecialchars($member['office_name'] ?? '—'); ?></td>
                                            <td><?php echo $member['window_number'] ? 'Window ' . (int)$member['window_number'] : '—'; ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $member['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="dropdown-toggle">
                                                        <i class="fas fa-ellipsis-h"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <button onclick="openEditStaffModal(<?php echo $member['id']; ?>)">
                                                            <i class="fas fa-pen"></i> Edit
                                                        </button>
                                                        <button onclick="openResetPasswordModal(<?php echo $member['id']; ?>)">
                                                            <i class="fas fa-key"></i> Reset Password
                                                        </button>
                                                        <?php if ($member['is_active']): ?>
                                                            <button onclick="openToggleModal(<?php echo $member['id']; ?>, true)">
                                                                <i class="fas fa-toggle-off"></i> Deactivate
                                                            </button>
                                                        <?php else: ?>
                                                            <button onclick="openToggleModal(<?php echo $member['id']; ?>, false)">
                                                                <i class="fas fa-toggle-on"></i> Activate
                                                            </button>
                                                        <?php endif; ?>
                                                        <button class="danger" onclick="openDeleteModal(<?php echo $member['id']; ?>)">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <div class="table-loading" id="tableLoading">
                            <div class="table-spinner"></div>
                        </div>

                        <div class="pagination-bar" id="paginationBar">
                            <div class="pagination-info" id="paginationInfo"></div>
                            <div class="pagination-controls" id="paginationControls"></div>
                            <div class="pagination-limit">
                                <label>Show:</label>
                                <select id="pageLimitSelect" onchange="changePageLimit()">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <div id="addStaffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add New Staff</h2>
                <button class="modal-close" onclick="closeModal('addStaffModal')">&times;</button>
            </div>

            <div class="form-group">
                <label for="add_full_name">Full Name <span class="required">*</span></label>
                <input type="text" id="add_full_name" placeholder="Enter full name">
            </div>

            <div class="form-group">
                <label for="add_email">Email <span class="required">*</span></label>
                <input type="email" id="add_email" placeholder="Enter email">
            </div>

            <div class="form-group">
                <label for="add_password">Password <span class="required">*</span></label>
                <div class="password-input-group">
                    <input type="password" id="add_password" placeholder="Enter password">
                    <button type="button" class="btn-toggle-password" onclick="togglePassword('add_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="add_office">Office <span class="required">*</span></label>
                <select id="add_office" onchange="loadWindowOptions('add_office', 'add_window', 'add_window_group')">
                    <option value="" disabled selected>Select office</option>
                    <?php foreach ($offices as $o): ?>
                        <option value="<?php echo htmlspecialchars($o); ?>">
                            <?php echo htmlspecialchars($o); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="add_window_group" style="display:none;">
                <label for="add_window">Window <span class="required">*</span></label>
                <select id="add_window">
                    <option value="" disabled selected>Select window</option>
                </select>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('addStaffModal')">Cancel</button>
                <button type="button" class="btn-primary" id="addStaffBtn" onclick="submitAddStaff()" data-action="confirm"><i class="fas fa-plus"></i> Add Staff</button>
            </div>
        </div>
    </div>

    <div id="editStaffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-pen"></i> Edit Staff</h2>
                <button class="modal-close" onclick="closeModal('editStaffModal')">&times;</button>
            </div>
            <input type="hidden" id="edit_staff_id">
            <div class="form-group">
                <label for="edit_full_name">Full Name <span class="required">*</span></label>
                <input type="text" id="edit_full_name">
            </div>
            <div class="form-group">
                <label for="edit_email">Email <span class="required">*</span></label>
                <input type="email" id="edit_email">
            </div>
            <div class="form-group">
                <label for="edit_office">Office <span class="required">*</span></label>
                <select id="edit_office" onchange="loadWindowOptions('edit_office', 'edit_window', 'edit_window_group')">
                    <option value="" disabled>Select Office</option>
                    <?php foreach ($offices as $o): ?>
                        <option value="<?php echo htmlspecialchars($o); ?>"><?php echo htmlspecialchars($o); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" id="edit_window_group" style="display:none;">
                <label for="edit_window">Window <span class="required">*</span></label>
                <select id="edit_window">
                    <option value="" disabled selected>Select window</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('editStaffModal')">Cancel</button>
                <button type="button" class="btn-primary" id="editStaffBtn" onclick="submitEditStaff()" data-action="confirm"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </div>
    </div>

    <div id="resetPasswordModal" class="modal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2><i class="fas fa-key"></i> Reset Password</h2>
                <button class="modal-close" onclick="closeModal('resetPasswordModal')">&times;</button>
            </div>
            <input type="hidden" id="reset_staff_id">
            <div class="form-group">
                <label for="new_password">New Password <span class="required">*</span></label>
                <div class="password-input-group">
                    <input type="password" id="new_password" placeholder="Min. 8 characters">
                    <button type="button" class="btn-toggle-password" onclick="togglePassword('new_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('resetPasswordModal')">Cancel</button>
                <button type="button" class="btn-primary" id="resetPasswordBtn" onclick="submitResetPassword()" data-action="confirm"><i class="fas fa-key"></i> Reset Password</button>
            </div>
        </div>
    </div>

    <div id="toggleStaffModal" class="modal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2 id="toggleModalTitle">Deactivate Staff</h2>
                <button class="modal-close" onclick="closeModal('toggleStaffModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p id="toggleModalBody">Deactivate this staff member? They will no longer be able to access the system.</p>
            </div>
            <input type="hidden" id="toggle_staff_id">
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('toggleStaffModal')">Cancel</button>
                <button type="button" id="toggleSubmitBtn" class="btn-warning" onclick="submitToggleStaff()" data-action="confirm">Deactivate</button>
            </div>
        </div>
    </div>

    <div id="deleteStaffModal" class="modal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2>Delete Staff</h2>
                <button class="modal-close" onclick="closeModal('deleteStaffModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this staff member?</p>
                <p class="modal-warning">This action cannot be undone.</p>
            </div>
            <input type="hidden" id="delete_staff_id">
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('deleteStaffModal')">Cancel</button>
                <button type="button" class="btn-danger" id="deleteStaffBtn" onclick="submitDeleteStaff()" data-action="confirm"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>
    </div>

    <script src="scripts/staff.js"></script>
    <script src="scripts/modal-shortcuts.js"></script>
    <script>
        const CSRF_TOKEN = <?php echo json_encode($csrf_token); ?>;
        const PAGINATION_LIMIT = 10;
        const TOTAL_STAFF = <?php echo (int)$totalStaff; ?>;
    </script>
</body>

</html>