<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/api/activity_logger.php';
require_once __DIR__ . '/api/staff_functions.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$currentUser = getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf)) {
        $error = 'Invalid request. Please refresh and try again.';
    } elseif ($action === 'add_staff') {
        $result = addStaff(
            $db->getConnection(),
            $_POST['full_name'] ?? '',
            $_POST['email'] ?? '',
            $_POST['password'] ?? '',
            $_POST['office'] ?? ''
        );
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'edit_staff') {
        $result = updateStaff(
            $db->getConnection(),
            (int) $_POST['staff_id'],
            $_POST['full_name'] ?? '',
            $_POST['email'] ?? '',
            $_POST['office'] ?? ''
        );
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'delete_staff') {
        $result = deleteStaff($db->getConnection(), (int) $_POST['staff_id']);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'activate_staff') {
        $result = activateStaff($db->getConnection(), (int) $_POST['staff_id']);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'deactivate_staff') {
        $result = deactivateStaff($db->getConnection(), (int) $_POST['staff_id']);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'reset_password') {
        $result = resetStaffPassword(
            $db->getConnection(),
            (int) $_POST['staff_id'],
            $_POST['new_password'] ?? ''
        );
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

$csrf_token = getCsrfToken();
$page = (int) ($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;
$totalStaff = getTotalStaffCount($db->getConnection());
$staff = getAllStaff($db->getConnection(), $limit, $offset);
$offices = getAvailableOffices($db->getConnection());
$totalPages = ceil($totalStaff / $limit);

// Determine which modal to show
$showModal = isset($_GET['modal']) ? $_GET['modal'] : '';
$editStaffId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editStaff = $editStaffId > 0 ? getStaffById($db->getConnection(), $editStaffId) : null;

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

        <?php
        include 'includes/sidebar.php';
        include 'includes/empty_state.php'
        ?>

        <div class="staff-body">
            <header class="dashboard-header">
                <div class="header-left">
                    <h2 class="header-page-title">
                        <i class="fas fa-users"></i> Staff Management
                    </h2>
                </div>
                <div class="header-right">
                    <button class="btn-primary" onclick="openAddStaffModal()">Add New Staff</button>
                </div>
            </header>

            <div class="dashboard-main">
                <div class="dashboard-content">

                    <h2 class="section-title">
                        <i class="fas fa-users"></i> Staff Overview
                    </h2>

                    <div class="summary-cards">
                        <div class="card card-total-staff">
                            <div class="card-header">
                                <div class="card-icon"><i class="fas fa-users"></i></div>
                                <div class="card-label">Total Staff</div>
                            </div>
                            <div class="card-value"><?php echo $totalStaff; ?></div>
                            <div class="card-description">All registered staff members</div>
                        </div>

                        <div class="card card-active-staff">
                            <div class="card-header">
                                <div class="card-icon"><i class="fas fa-user-check"></i></div>
                                <div class="card-label">Active Staff</div>
                            </div>
                            <div class="card-value"><?php echo count(array_filter($staff, fn($s) => $s['is_active'])); ?></div>
                            <div class="card-description">Currently active staff members</div>
                        </div>

                        <div class="card card-inactive-staff">
                            <div class="card-header">
                                <div class="card-icon"><i class="fas fa-user-slash"></i></div>
                                <div class="card-label">Inactive Staff</div>
                            </div>
                            <div class="card-value"><?php echo count(array_filter($staff, fn($s) => !$s['is_active'])); ?></div>
                            <div class="card-description">Currently inactive staff members</div>
                        </div>
                    </div>

                    <table class="staff-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Office</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td><?php echo htmlspecialchars($member['office_name'] ?? 'N/A'); ?></td>
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
                                                <button onclick="openEditStaffModal(<?php echo $member['id']; ?>)">Edit</button>

                                                <?php if ($member['is_active']): ?>
                                                    <button onclick="openDeactivateModal(<?php echo $member['id']; ?>)">Deactivate</button>
                                                <?php else: ?>
                                                    <button onclick="confirmActivate(<?php echo $member['id']; ?>)">Activate</button>
                                                <?php endif; ?>

                                                <button onclick="openResetPasswordModal(<?php echo $member['id']; ?>)">Reset Password</button>

                                                <button class="danger" onclick="openDeleteModal(<?php echo $member['id']; ?>)">Delete</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=1">First</a>
                                <a href="?page=<?php echo $page - 1; ?>">Previous</a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>">Next</a>
                                <a href="?page=<?php echo $totalPages; ?>">Last</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <div id="addStaffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Staff Member</h2>
                <button class="modal-close" onclick="closeModal('addStaffModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="add_staff">

                <div class="form-group">
                    <label for="add_full_name">Full Name</label>
                    <input type="text" id="add_full_name" name="full_name" required>
                </div>

                <div class="form-group">
                    <label for="add_email">Email</label>
                    <input type="email" id="add_email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="add_password">Password</label>
                    <div class="password-input-group">
                        <input type="password" id="add_password" name="password" required>
                        <button type="button" class="btn-toggle-password" onclick="togglePassword('add_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_office">Office</label>
                    <select id="add_office" name="office" required>
                        <option value="" disabled selected>Select Office</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?php echo htmlspecialchars($office); ?>"><?php echo htmlspecialchars($office); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('addStaffModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Add Staff</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editStaffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Staff Member</h2>
                <button class="modal-close" onclick="closeModal('editStaffModal')">&times;</button>
            </div>
            <form method="POST" id="editStaffForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="edit_staff">
                <input type="hidden" name="staff_id" id="edit_staff_id">

                <div class="form-group">
                    <label for="edit_full_name">Full Name</label>
                    <input type="text" id="edit_full_name" name="full_name" required>
                </div>

                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="edit_office">Office</label>
                    <select id="edit_office" name="office" required>
                        <option value="" disabled selected>Select Office</option>
                        <?php foreach ($offices as $office): ?>
                            <option value="<?php echo htmlspecialchars($office); ?>"><?php echo htmlspecialchars($office); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('editStaffModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Update Staff</button>
                </div>
            </form>
        </div>
    </div>

    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Reset Password</h2>
                <button class="modal-close" onclick="closeModal('resetPasswordModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="staff_id" id="reset_staff_id">

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-input-group">
                        <input type="password" id="new_password" name="new_password" required>
                        <button type="button" class="btn-toggle-password" onclick="togglePassword('new_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('resetPasswordModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Reset Password</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteStaffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete Staff</h2>
                <button class="modal-close" onclick="closeModal('deleteStaffModal')">&times;</button>
            </div>

            <div class="modal-body">
                <p>Are you sure you want to delete this staff member?</p>
                <p style="color: var(--text-3); font-size: 13px;">
                    This action cannot be undone.
                </p>
            </div>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="delete_staff">
                <input type="hidden" name="staff_id" id="delete_staff_id">

                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('deleteStaffModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deactivateStaffModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Deactivate Staff</h2>
                <button class="modal-close" onclick="closeModal('deactivateStaffModal')">&times;</button>
            </div>

            <div class="modal-body">
                <p>Deactivate this staff member?</p>
                <p style="color: var(--text-3); font-size: 13px;">
                    They will no longer be able to access the system.
                </p>
            </div>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="deactivate_staff">
                <input type="hidden" name="staff_id" id="deactivate_staff_id">

                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal('deactivateStaffModal')">Cancel</button>
                    <button type="submit" class="btn btn-warning">Deactivate</button>
                </div>
            </form>
        </div>
    </div>

    <script src="scripts/staff.js"></script>
</body>

</html>