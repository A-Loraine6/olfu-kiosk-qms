<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/api/archive_functions.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$currentUser = getCurrentUser();
$csrf_token  = getCsrfToken();

$canDeleteStaff = hasPermission('delete_staff');
$canDeleteService = hasPermission('delete_service');

if (!($canDeleteStaff || $canDeleteService)) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$conn = $db->getConnection();

$deletedStaff = $canDeleteStaff ? getDeletedStaff($conn) : [];
$deletedServices = $canDeleteService ? getDeletedServices($conn) : [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive Management</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/archive.css">
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="archive-container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="archive-body">
            <header class="dashboard-header">
                <div class="header-left">
                    <h2 class="header-page-title">
                        <i class="fas fa-archive"></i> Archive Management
                    </h2>
                </div>
            </header>

            <div class="dashboard-main">
                <div class="archive-tabs">
                    <button class="tab-btn active" data-tab="staff-tab">
                        <i class="fas fa-users"></i> Deleted Staff
                        <span class="tab-badge"><?php echo count($deletedStaff); ?></span>
                    </button>
                    <button class="tab-btn" data-tab="services-tab">
                        <i class="fas fa-concierge-bell"></i> Deleted Services
                        <span class="tab-badge"><?php echo count($deletedServices); ?></span>
                    </button>
                </div>
                <div class="dashboard-content">
                    <?php if ($canDeleteStaff): ?>
                        <div class="tab-content active" id="staff-tab">
                            <div class="section-header">
                                <h3><i class="fas fa-users"></i> Deleted Staff Members</h3>
                                <?php if (count($deletedStaff) > 0): ?>
                                    <p class="section-subtitle">View and restore deleted staff members or permanently remove them.</p>
                                <?php endif; ?>
                            </div>

                            <?php if (count($deletedStaff) === 0): ?>
                                <div class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <h3>No Deleted Staff</h3>
                                    <p>All staff members are active.</p>
                                </div>
                            <?php else: ?>
                                <div class="archive-list">
                                    <div class="list-header">
                                        <div class="col-name">Name</div>
                                        <div class="col-email">Email</div>
                                        <div class="col-office">Office</div>
                                        <div class="col-deleted">Deleted</div>
                                        <div class="col-actions">Actions</div>
                                    </div>
                                    <?php foreach ($deletedStaff as $staff): ?>
                                        <div class="list-item" data-id="<?php echo $staff['id']; ?>" data-type="staff">
                                            <div class="col-name">
                                                <div class="item-name"><?php echo htmlspecialchars($staff['full_name']); ?></div>
                                            </div>
                                            <div class="col-email"><?php echo htmlspecialchars($staff['email']); ?></div>
                                            <div class="col-office"><?php echo htmlspecialchars($staff['office_name'] ?? 'N/A'); ?></div>
                                            <div class="col-deleted">
                                                <?php echo $staff['deleted_at']
                                                    ? date('M d, Y', strtotime($staff['deleted_at']))
                                                    : 'Unknown'; ?>
                                            </div>
                                            <div class="col-actions">
                                                <button class="btn-restore-item" onclick="restoreStaff(<?php echo $staff['id']; ?>)" title="Restore Staff">
                                                    <i class="fas fa-undo"></i> Restore
                                                </button>
                                                <button class="btn-delete-item" onclick="openDeleteArchiveModal(<?php echo $staff['id']; ?>, 'staff')" title="Permanently Delete">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($canDeleteService): ?>
                        <div class="tab-content" id="services-tab">
                            <div class="section-header">
                                <h3><i class="fas fa-concierge-bell"></i> Deleted Services</h3>
                                <?php if (count($deletedServices) > 0): ?>
                                    <p class="section-subtitle">View and restore deleted services or permanently remove them.</p>
                                <?php endif; ?>
                            </div>

                            <?php if (count($deletedServices) === 0): ?>
                                <div class="empty-state">
                                    <i class="fas fa-concierge-bell"></i>
                                    <h3>No Deleted Services</h3>
                                    <p>All services are active.</p>
                                </div>
                            <?php else: ?>
                                <div class="archive-list">
                                    <div class="list-header">
                                        <div class="col-name">Service Name</div>
                                        <div class="col-category">Category</div>
                                        <div class="col-office">Office</div>
                                        <div class="col-deleted">Deleted</div>
                                        <div class="col-actions">Actions</div>
                                    </div>
                                    <?php foreach ($deletedServices as $service): ?>
                                        <div class="list-item" data-id="<?php echo $service['id']; ?>" data-type="service">
                                            <div class="col-name">
                                                <div class="item-name"><?php echo htmlspecialchars($service['name']); ?></div>
                                            </div>
                                            <div class="col-category">
                                                <?php echo ucfirst(htmlspecialchars($service['category'] ?? 'N/A')); ?>
                                            </div>
                                            <div class="col-office"><?php echo htmlspecialchars($service['office_name'] ?? 'N/A'); ?></div>
                                            <div class="col-deleted">
                                                <?php echo $service['deleted_at']
                                                    ? date('M d, Y', strtotime($service['deleted_at']))
                                                    : 'Unknown'; ?>
                                            </div>
                                            <div class="col-actions">
                                                <button class="btn-restore-item" onclick="restoreService(<?php echo $service['id']; ?>)" title="Restore Service">
                                                    <i class="fas fa-undo"></i> Restore
                                                </button>
                                                <button class="btn-delete-item" onclick="openDeleteArchiveModal(<?php echo $service['id']; ?>, 'service')" title="Permanently Delete">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="restoreArchiveModal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2><i class="fas fa-undo"></i> Restore Item</h2>
                <button class="modal-close" onclick="closeModal('restoreArchiveModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p id="restoreArchiveMessage">Are you sure you want to restore this item?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('restoreArchiveModal')">Cancel</button>
                <button type="button" class="btn-primary" id="confirmRestoreBtn" onclick="confirmRestore()">
                    <i class="fas fa-undo"></i> Restore
                </button>
            </div>
        </div>
    </div>

    <div class="modal" id="deleteArchiveModal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2><i class="fas fa-trash"></i> Permanent Delete</h2>
                <button class="modal-close" onclick="closeModal('deleteArchiveModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p id="deleteArchiveMessage">Are you sure you want to permanently delete this item?</p>
                <p class="modal-warning"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('deleteArchiveModal')">Cancel</button>
                <button type="button" class="btn-danger" id="confirmDeleteArchiveBtn" onclick="confirmDeleteArchive()">
                    <i class="fas fa-trash"></i> Delete Permanently
                </button>
            </div>
        </div>
    </div>
    <script>
        const CSRF_TOKEN = <?php echo json_encode($csrf_token); ?>;
        const TAB_COUNTS = {
            staff: <?php echo count($deletedStaff); ?>,
            services: <?php echo count($deletedServices); ?>
        };
    </script>
    <script src="scripts/modal-shortcuts.js"></script>
    <script src="scripts/archive.js"></script>
</body>

</html>