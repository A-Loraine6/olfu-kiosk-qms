<?php
require_once __DIR__ . '/includes/config_loader.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

if (($_SESSION['role_id'] ?? null) !== 1 && ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$currentUser = getCurrentUser();
$csrf_token  = getCsrfToken();

$roles = [];
$stmt = $db->getConnection()->prepare("SELECT * FROM roles WHERE id != 1 ORDER BY name ASC");
if ($stmt) {
    $stmt->execute();
    $roles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$permissions = [];
$stmt = $db->getConnection()->prepare("SELECT * FROM permissions ORDER BY group_name, label ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $permissions[$row['group_name']][] = $row;
    }
    $stmt->close();
}

$rolePermsMap = [];
$stmt = $db->getConnection()->prepare(
    "SELECT rp.role_id, p.key FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id"
);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rolePermsMap[$row['role_id']][] = $row['key'];
    }
    $stmt->close();
}

$activeRoleId = isset($_GET['role']) ? (int) $_GET['role'] : ($roles[0]['id'] ?? 0);
$activeRole   = null;
foreach ($roles as $r) {
    if ($r['id'] === $activeRoleId) {
        $activeRole = $r;
        break;
    }
}
if (!$activeRole && !empty($roles)) {
    $activeRole   = $roles[0];
    $activeRoleId = $activeRole['id'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles & Permissions</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/settings.css">
    <link rel="stylesheet" href="css/roles.css">
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="roles-container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="roles-body">

            <header class="dashboard-header">
                <div class="header-left">
                    <h2 class="header-page-title">
                        <i class="fas fa-shield-alt"></i> Roles & Permissions
                    </h2>
                </div>
            </header>

            <div class="dashboard-main">
                <div class="dashboard-content">
                    <div class="roles-grid">

                        <div class="role-selector">
                            <div class="role-selector-header">
                                <span class="role-selector-title">Roles</span>
                                <button class="btn-add-role" title="Add role" onclick="openModal('addRoleModal')">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <ul class="role-selector-list">
                                <?php if (empty($roles)): ?>
                                    <div class="role-selector-empty">No roles yet</div>
                                <?php else: ?>
                                    <?php foreach ($roles as $role): ?>
                                        <li class="role-selector-item <?php echo $role['id'] === $activeRoleId ? 'active' : ''; ?>"
                                            id="role-item-<?php echo $role['id']; ?>"
                                            onclick="selectRole(<?php echo $role['id']; ?>)">
                                            <i class="fas fa-user-tag role-icon"></i>
                                            <span class="role-item-name"><?php echo htmlspecialchars(ucfirst($role['name'])); ?></span>
                                            <div class="role-item-actions">
                                                <button class="role-item-btn" title="Edit"
                                                    onclick="event.stopPropagation(); openEditRoleModal(<?php echo htmlspecialchars(json_encode($role), ENT_QUOTES); ?>)">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button class="role-item-btn del" title="Delete"
                                                    onclick="event.stopPropagation(); openDeleteRoleModal(<?php echo $role['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <div class="settings-sections" id="permsContainer">
                            <?php if (empty($roles)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="fas fa-shield-alt"></i></div>
                                    <div class="empty-state-title">No roles yet</div>
                                    <div class="empty-state-message">Add a role on the left to start assigning permissions.</div>
                                </div>

                            <?php else: ?>
                                <?php foreach ($roles as $ri => $role): ?>
                                    <div id="perms-view-<?php echo $role['id']; ?>"
                                        style="display:<?php echo $role['id'] === $activeRoleId ? 'contents' : 'none'; ?>">

                                        <div class="perms-section-header-bar">
                                            <span class="perms-role-title"><?php echo htmlspecialchars(ucfirst($role['name'])); ?></span>
                                            <span class="perms-hint">Toggle to grant or revoke permissions</span>
                                        </div>

                                        <?php $rolePerms = $rolePermsMap[$role['id']] ?? []; ?>
                                        <?php foreach ($permissions as $group => $perms): ?>

                                            <div class="settings-section">
                                                <div class="settings-section-header">
                                                    <div class="settings-section-icon">
                                                        <?php
                                                        $groupIcon = match (strtolower($group)) {
                                                            'queue'    => 'fa-ticket-alt',
                                                            'reports'  => 'fa-chart-bar',
                                                            'services' => 'fa-concierge-bell',
                                                            'settings' => 'fa-cog',
                                                            'staff'    => 'fa-users',
                                                            default    => 'fa-key',
                                                        };
                                                        ?>
                                                        <i class="fas <?php echo $groupIcon; ?>"></i>
                                                    </div>
                                                    <div>
                                                        <div class="settings-section-title"><?php echo htmlspecialchars($group); ?></div>
                                                        <div class="settings-section-desc">
                                                            <?php echo count($perms); ?> permission<?php echo count($perms) !== 1 ? 's' : ''; ?> in this group
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="settings-section-body">
                                                    <div class="perm-rows">
                                                        <?php foreach ($perms as $perm): ?>
                                                            <div class="perm-row">
                                                                <div class="perm-row-info">
                                                                    <div class="perm-row-label"><?php echo htmlspecialchars($perm['label']); ?></div>
                                                                </div>
                                                                <label class="toggle-switch perm-toggle">
                                                                    <input type="checkbox"
                                                                        data-role="<?php echo $role['id']; ?>"
                                                                        data-perm="<?php echo htmlspecialchars($perm['key']); ?>"
                                                                        <?php echo in_array($perm['key'], $rolePerms) ? 'checked' : ''; ?>
                                                                        onchange="togglePermission(this)">
                                                                    <span class="toggle-track"></span>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>

                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <div id="toast-container"></div>

    <div id="addRoleModal" class="modal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Add Role</h2>
                <button class="modal-close" onclick="closeModal('addRoleModal')">&times;</button>
            </div>
            <div class="form-group">
                <label for="add_role_name">Role Name <span class="required">*</span></label>
                <input type="text" id="add_role_name" placeholder="Enter role name">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('addRoleModal')">Cancel</button>
                <button type="button" class="btn-primary" id="addRoleBtn" onclick="submitAddRole()">
                    <i class="fas fa-plus"></i> Add Role
                </button>
            </div>
        </div>
    </div>

    <div id="editRoleModal" class="modal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2><i class="fas fa-pen"></i> Edit Role</h2>
                <button class="modal-close" onclick="closeModal('editRoleModal')">&times;</button>
            </div>
            <input type="hidden" id="edit_role_id">
            <div class="form-group">
                <label for="edit_role_name">Role Name <span class="required">*</span></label>
                <input type="text" id="edit_role_name">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('editRoleModal')">Cancel</button>
                <button type="button" class="btn-primary" id="editRoleBtn" onclick="submitEditRole()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <div id="deleteRoleModal" class="modal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2>Delete Role</h2>
                <button class="modal-close" onclick="closeModal('deleteRoleModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this role?</p>
                <p class="modal-warning"><i class="fas fa-exclamation-triangle" style="color: red;"></i> Staff assigned to this role will have their role removed.</p>
            </div>
            <input type="hidden" id="delete_role_id">
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('deleteRoleModal')">Cancel</button>
                <button type="button" class="btn-danger" id="deleteRoleBtn" onclick="submitDeleteRole()">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <script src="scripts/roles.js"></script>
    <script>
        const CSRF_TOKEN = <?php echo json_encode($csrf_token); ?>;

        function selectRole(roleId) {
            document.querySelectorAll('.role-selector-item').forEach(el => el.classList.remove('active'));
            document.getElementById('role-item-' + roleId)?.classList.add('active');

            document.querySelectorAll('[id^="perms-view-"]').forEach(el => el.style.display = 'none');
            const view = document.getElementById('perms-view-' + roleId);
            if (view) view.style.display = 'contents';
        }
    </script>
</body>

</html>