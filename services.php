<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/api/service_functions.php';
require_once __DIR__ . '/includes/queue_functions.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$currentUser = getCurrentUser();
$csrf_token  = getCsrfToken();

$canAddServices    = hasPermission('add_services');
$canEditServices   = hasPermission('edit_services');
$canDeleteServices = hasPermission('delete_services');
$canManageServices = $canAddServices || $canEditServices || $canDeleteServices;
$isAdmin           = ($_SESSION['role_id'] ?? null) === 1 || ($_SESSION['role'] ?? '') === 'admin';
$isOfficeScoped    = !$isAdmin && !empty($currentUser['office_id']);
$staffOfficeId     = $currentUser['office_id'] ?? null;
$officeInfo        = ($isOfficeScoped && $staffOfficeId) ? getOfficeInfo($db, $staffOfficeId) : null;
$staffOfficeName   = $officeInfo['name'] ?? null;

$offices  = getAllOffices($db->getConnection());
$services = $isOfficeScoped
    ? getAllServicesWithOffice($db->getConnection(), $staffOfficeId)
    : getAllServicesWithOffice($db->getConnection());
$totalServices  = count($services);
$activeServices = count(array_filter($services, fn($s) => $s['is_active']));

$pageLimit = 10;

$servicesByOffice = [];
foreach ($services as $svc) {
    $servicesByOffice[$svc['office_id']][] = $svc;
}

function getCategoryColor(?string $cat): string
{
    return match ($cat) {
        'simple'      => '#2a9d5c',
        'regular'     => '#2196f3',
        'complicated' => '#e07b1a',
        default       => '#c4cdd6',
    };
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Management</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/services.css">
    <link rel="shortcut icon" href="img/olfu-logo.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="services-container">

        <?php include 'includes/sidebar.php'; ?>

        <div class="services-body">

            <header class="dashboard-header">
                <div class="header-left">
                    <h2 class="header-page-title">
                        <i class="fas fa-concierge-bell"></i> Service Management
                    </h2>
                    <?php if ($isOfficeScoped && $staffOfficeName): ?>
                        <span class="office-badge">
                            <i class="fas fa-building"></i>
                            <?php echo htmlspecialchars($staffOfficeName); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="header-right">
                    <?php if ($canAddServices): ?>
                        <button class="btn-primary" onclick="openModal('addServiceModal')">
                            <i class="fas fa-plus"></i> Add New Service
                        </button>
                    <?php endif; ?>
                </div>
            </header>

            <div class="dashboard-main">
                <div class="dashboard-content">

                    <h2 class="section-title"><i class="fas fa-concierge-bell"></i> Service Overview</h2>
                    <div class="summary-cards">
                        <div class="card card-total-services">
                            <div class="card-header">
                                <div class="card-icon"><i class="fas fa-list-alt"></i></div>
                                <div class="card-label">Total Services</div>
                            </div>
                            <div class="card-value" id="stat-total"><?php echo $totalServices; ?></div>
                            <div class="card-description">
                                <?php echo $isOfficeScoped ? 'Services in your office' : 'All registered transaction types'; ?>
                            </div>
                        </div>
                        <div class="card card-active-services">
                            <div class="card-header">
                                <div class="card-icon"><i class="fas fa-toggle-on"></i></div>
                                <div class="card-label">Active Services</div>
                            </div>
                            <div class="card-value" id="stat-active"><?php echo $activeServices; ?></div>
                            <div class="card-description">
                                <?php echo $isOfficeScoped ? 'Active in your office' : 'Currently shown in kiosk'; ?>
                            </div>
                        </div>
                        <div class="card card-inactive-services">
                            <div class="card-header">
                                <div class="card-icon"><i class="fas fa-toggle-off"></i></div>
                                <div class="card-label">Inactive Services</div>
                            </div>
                            <div class="card-value" id="stat-inactive"><?php echo $totalServices - $activeServices; ?></div>
                            <div class="card-description">
                                <?php echo $isOfficeScoped ? 'Inactive in your office' : 'Hidden from kiosk'; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (hasPermission('manage_settings')): ?>
                        <h2 class="section-title"><i class="fas fa-building"></i> Office Queue Mode</h2>
                        <div class="office-mode-grid">
                            <?php foreach ($offices as $office): ?>
                                <div class="office-mode-card" id="office-card-<?php echo $office['id']; ?>">
                                    <div class="office-mode-header">
                                        <div class="office-mode-icon"><i class="fas fa-building"></i></div>
                                        <div>
                                            <div class="office-mode-name"><?php echo htmlspecialchars($office['name']); ?></div>
                                            <?php if ($office['code']): ?>
                                                <div class="office-mode-code"><?php echo htmlspecialchars($office['code']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="office-mode-body">
                                        <div class="queue-mode-label">Queue Mode</div>
                                        <div class="queue-mode-toggle-wrap">
                                            <span class="queue-mode-value <?php echo $office['queue_mode'] === 'service-based' ? 'mode-service' : 'mode-unified'; ?>"
                                                id="office-mode-label-<?php echo $office['id']; ?>">
                                                <?php echo $office['queue_mode'] === 'service-based' ? 'Service-Based' : 'Unified'; ?>
                                            </span>
                                            <button type="button" class="btn-mode-toggle"
                                                title="Switch queue mode"
                                                onclick="toggleQueueMode(<?php echo $office['id']; ?>, this)">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                        </div>
                                        <p class="queue-mode-desc" id="office-mode-desc-<?php echo $office['id']; ?>">
                                            <?php if ($office['queue_mode'] === 'service-based'): ?>
                                                Kiosk shows service selection before issuing a number.
                                            <?php else: ?>
                                                Kiosk issues a number directly without service selection.
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <div class="office-mode-footer">
                                        <?php $cnt = count($servicesByOffice[$office['id']] ?? []); ?>
                                        <span class="svc-count-badge">
                                            <?php echo $cnt; ?> service<?php echo $cnt !== 1 ? 's' : ''; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <h2 class="section-title">
                        <i class="fas fa-th-list"></i>
                        <?php echo $isOfficeScoped ? htmlspecialchars($staffOfficeName) . ' — Services' : 'All Services'; ?>
                    </h2>
                    <div class="services-table-section">
                        <div class="services-table-toolbar">
                            <div class="search-wrap">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" id="searchInput" class="filter-input search-input"
                                    placeholder="Search services, offices…"
                                    autocomplete="off">
                                <button class="search-clear" id="searchClear" onclick="clearSearch()" title="Clear">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <div class="filter-group">
                                <?php if ($isAdmin): ?>
                                    <select id="filterOffice" class="filter-select" onchange="applyFilters()">
                                        <option value="">All Offices</option>
                                        <?php foreach ($offices as $o): ?>
                                            <option value="<?php echo $o['id']; ?>">
                                                <?php echo htmlspecialchars($o['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="hidden" id="filterOffice" value="<?php echo (int)($currentUser['office_id'] ?? 0); ?>">
                                <?php endif; ?>
                                <select id="filterCategory" class="filter-select" onchange="applyFilters()">
                                    <option value="">All Categories</option>
                                    <option value="simple">Simple</option>
                                    <option value="regular">Regular</option>
                                    <option value="complicated">Complicated</option>
                                </select>
                                <select id="filterStatus" class="filter-select" onchange="applyFilters()">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <table class="services-table">
                            <thead>
                                <tr>
                                    <th>Service Name</th>
                                    <th>Office</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <?php if ($canEditServices || $canDeleteServices): ?>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="servicesTableBody">
                                <?php if (empty($services)): ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <div class="empty-state-icon"><i class="fas fa-concierge-bell"></i></div>
                                                <div class="empty-state-title">No services found</div>
                                                <div class="empty-state-message">Add a new service to get started.</div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($services, 0, $pageLimit) as $svc): ?>
                                        <?php $assignedWindows = getWindowsForService($db->getConnection(), $svc['id']); ?>
                                        <tr>
                                            <td>
                                                <div class="service-name-cell">
                                                    <div>
                                                        <div class="svc-name"><?php echo htmlspecialchars($svc['name']); ?></div>
                                                        <?php if ($svc['description']): ?>
                                                            <div class="svc-desc"><?php echo htmlspecialchars($svc['description']); ?></div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($assignedWindows)): ?>
                                                            <div class="svc-windows-hint">
                                                                <i class="fas fa-desktop"></i>
                                                                Windows: <?php echo implode(', ', $assignedWindows); ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="svc-windows-hint svc-windows-all">
                                                                <i class="fas fa-desktop"></i>
                                                                All windows
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($svc['office_name'] ?? '—'); ?></td>
                                            <td>
                                                <?php if ($svc['category']): ?>
                                                    <span class="category-badge category-<?php echo strtolower($svc['category']); ?>">
                                                        <?php echo ucfirst($svc['category']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="category-none">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $svc['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo $svc['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <?php if ($canEditServices || $canDeleteServices): ?>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="dropdown-toggle">
                                                            <i class="fas fa-ellipsis-h"></i>
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <?php if ($canEditServices): ?>
                                                                <button onclick="openEditServiceModal(<?php echo htmlspecialchars(json_encode($svc), ENT_QUOTES); ?>)">
                                                                    <i class="fas fa-pen"></i> Edit
                                                                </button>
                                                                <button onclick="openToggleModal(<?php echo $svc['id']; ?>, <?php echo $svc['is_active'] ? 'true' : 'false'; ?>)">
                                                                    <i class="fas fa-<?php echo $svc['is_active'] ? 'toggle-off' : 'toggle-on'; ?>"></i>
                                                                    <?php echo $svc['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                                </button>
                                                            <?php endif; ?>
                                                            <?php if ($canDeleteServices): ?>
                                                                <button class="danger" onclick="openDeleteModal(<?php echo $svc['id']; ?>)">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
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

    <div id="addServiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Add New Service</h2>
            </div>

            <div class="modal-body-scroll">
                <div class="form-group">
                    <label for="add_name">Service Name <span class="required">*</span></label>
                    <input type="text" id="add_name" placeholder="Enter service name">
                </div>

                <div class="form-group">
                    <label for="add_office">Assigned Office <span class="required">*</span></label>
                    <select id="add_office" onchange="loadServiceWindowCheckboxes('add_office', 'add_windows_list', 'add_windows_group', [])">
                        <option value="" disabled selected>Select office</option>
                        <?php foreach ($offices as $o): ?>
                            <option value="<?php echo $o['id']; ?>">
                                <?php echo htmlspecialchars($o['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="add_windows_group" style="display:none;">
                    <label>Assign to Windows <span class="optional">(optional)</span></label>
                    <div class="window-checkbox-list" id="add_windows_list"></div>
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i>
                        Leave all unchecked to make available at all windows.
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_category">Category</label>
                    <select id="add_category">
                        <option value="" disabled selected>Select a category</option>
                        <option value="simple">Simple</option>
                        <option value="regular">Regular</option>
                        <option value="complicated">Complicated</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="add_description">Description <span class="optional">(optional)</span></label>
                    <textarea id="add_description" placeholder="Enter description"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('addServiceModal')">Cancel</button>
                <button type="button" class="btn-primary" id="addServiceBtn" onclick="submitAddService()" data-action="confirm"><i class="fas fa-plus"></i> Add Service</button>
            </div>
        </div>
    </div>

    <div id="editServiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-pen"></i> Edit Service</h2>
                <button class="modal-close" onclick="closeModal('editServiceModal')">&times;</button>
            </div>
            <input type="hidden" id="edit_service_id">
            <div class="modal-body-scroll">
                <div class="form-group">
                    <label for="edit_name">Service Name <span class="required">*</span></label>
                    <input type="text" id="edit_name">
                </div>
                <div class="form-group">
                    <label for="edit_office">Assigned Office <span class="required">*</span></label>
                    <select id="edit_office" onchange="loadServiceWindowCheckboxes('edit_office', 'edit_windows_list', 'edit_windows_group', [])">
                        <option value="" disabled>Select Office</option>
                        <?php foreach ($offices as $o): ?>
                            <option value="<?php echo $o['id']; ?>"><?php echo htmlspecialchars($o['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="edit_windows_group" style="display:none;">
                    <label>Assign to Windows <span class="optional">(optional)</span></label>
                    <div class="window-checkbox-list" id="edit_windows_list"></div>
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i>
                        Leave all unchecked to make available at all windows.
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_category">Category</label>
                    <select id="edit_category">
                        <option value="" disabled selected>Select a category</option>
                        <option value="simple">Simple</option>
                        <option value="regular">Regular</option>
                        <option value="complicated">Complicated</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description <span class="optional">(optional)</span></label>
                    <textarea id="edit_description"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('editServiceModal')">Cancel</button>
                <button type="button" class="btn-primary" id="editServiceBtn" onclick="submitEditService()" data-action="confirm"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </div>
    </div>

    <div id="toggleServiceModal" class="modal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2 id="toggleModalTitle">Deactivate Service</h2>
                <button class="modal-close" onclick="closeModal('toggleServiceModal')">&times;</button>
            </div>
            <div class="modal-body-scroll">
                <div class="modal-body">
                    <p id="toggleModalBody">Deactivate this service? It will be hidden from the kiosk.</p>
                </div>
            </div>
            <input type="hidden" id="toggle_service_id">
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('toggleServiceModal')">Cancel</button>
                <button type="button" id="toggleSubmitBtn" class="btn-warning" onclick="submitToggleService()" data-action="confirm">Deactivate</button>
            </div>
        </div>
    </div>

    <div id="deleteServiceModal" class="modal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2>Delete Service</h2>
                <button class="modal-close" onclick="closeModal('deleteServiceModal')">&times;</button>
            </div>
            <div class="modal-body-scroll">
                <div class="modal-body">
                    <p>Are you sure you want to delete this service?</p>
                    <p class="modal-warning">This action cannot be undone.</p>
                </div>
            </div>
            <input type="hidden" id="delete_service_id">
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal('deleteServiceModal')">Cancel</button>
                <button type="button" class="btn-danger" id="deleteServiceBtn" onclick="submitDeleteService()" data-action="confirm"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>
    </div>

    <script src="scripts/services.js"></script>
    <script src="scripts/modal-shortcuts.js"></script>
    <script>
        const CSRF_TOKEN = <?php echo json_encode($csrf_token); ?>;
        const PAGINATION_LIMIT = <?php echo (int)$pageLimit; ?>;
        const TOTAL_SERVICES = <?php echo (int)$totalServices; ?>;
        const CAN_ADD_SERVICES = <?php echo json_encode($canAddServices); ?>;
        const CAN_EDIT_SERVICES = <?php echo json_encode($canEditServices); ?>;
        const CAN_DELETE_SERVICES = <?php echo json_encode($canDeleteServices); ?>;
    </script>

</body>

</html>