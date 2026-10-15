<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/request_validation.php';
require_once __DIR__ . '/service_functions.php';
require_once __DIR__ . '/activity_logger.php';

if (!isLoggedIn()) {
    logAuthDenied($db->getConnection(), 'Unauthenticated access to service API', null, 'service');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentUser = getCurrentUser();
$canAddServices    = hasPermission('add_services');
$canEditServices   = hasPermission('edit_services');
$isAdmin           = ($_SESSION['role_id'] ?? null) === 1 || ($_SESSION['role'] ?? '') === 'admin';
$canDeleteServices = $isAdmin;
$canManageServices = $canAddServices || $canEditServices || $canDeleteServices;
$isOfficeScoped    = !$isAdmin && !empty($currentUser['office_id']);
$staffOffice       = $isOfficeScoped ? (int) ($currentUser['office_id'] ?? 0) : null;

$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = requestEnum($input, 'action', ['add_service', 'edit_service', 'get_windows_for_service', 'get_windows_for_office', 'toggle_service', 'delete_service', 'toggle_queue_mode', 'set_queue_mode', 'save_window_levels', 'get_services']);
$csrf   = requestString($input, 'csrf_token', 255, true);

if ($action === null || $csrf === null || !verifyCsrfToken($csrf)) {
    logSuspiciousApiActivity($db->getConnection(), 'Invalid CSRF token or malformed service action request', (int) ($_SESSION['user_id'] ?? 0), 'service');
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$conn = $db->getConnection();
$officeIsAllowed = static fn(int $officeId): bool => !$isOfficeScoped || $officeId === $staffOffice;
$denyOfficeScope = static function (): void {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You may only access or manage data in your office.']);
    exit;
};

$actionPermMap = [
    'add_service'    => 'add_services',
    'edit_service'   => 'edit_services',
    'toggle_service' => 'edit_services',
    'delete_service' => 'admin',
];
if (isset($actionPermMap[$action]) && $actionPermMap[$action] !== 'admin' && !hasPermission($actionPermMap[$action])) {
    logPermissionDenied($db->getConnection(), 'Attempted forbidden service action: ' . ($action ?? 'unknown'), (int) ($_SESSION['user_id'] ?? 0), 'service', requestInt($input, 'service_id') ?? null);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}
if (isset($actionPermMap[$action]) && $actionPermMap[$action] === 'admin' && !$isAdmin) {
    logPermissionDenied($db->getConnection(), 'Attempted admin-only service action: ' . ($action ?? 'unknown'), (int) ($_SESSION['user_id'] ?? 0), 'service', requestInt($input, 'service_id') ?? null);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if (in_array($action, ['toggle_queue_mode', 'set_queue_mode', 'save_window_levels'], true) && !hasPermission('manage_settings')) {
    logPermissionDenied($db->getConnection(), 'Attempted queue mode toggle without manage_settings permission', (int) ($_SESSION['user_id'] ?? 0), 'service');
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}
if ($action === 'save_window_levels' && !$isAdmin) {
    logPermissionDenied($db->getConnection(), 'Attempted window level update without admin role', (int) ($_SESSION['user_id'] ?? 0), 'service');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only an administrator can assign window levels.']);
    exit;
}

switch ($action) {

    case 'add_service':
        if (!$officeIsAllowed(requestInt($input, 'office_id') ?? 0)) {
            $denyOfficeScope();
        }
        $windows = requestIntList($input, 'windows');
        if ($windows === null) {
            echo json_encode(['success' => false, 'message' => 'Invalid service window list.']);
            exit;
        }
        $result = addService(
            $conn,
            requestString($input, 'name', 100, true) ?? '',
            requestInt($input, 'office_id') ?? 0,
            requestEnum($input, 'category', ['simple', 'regular', 'complicated']),
            requestString($input, 'description', 65535) ?? '',
            $windows
        );
        break;

    case 'edit_service':
        $serviceId = requestInt($input, 'service_id') ?? 0;
        $officeId = requestInt($input, 'office_id') ?? 0;
        if (
            !$officeIsAllowed($officeId)
            || ($isOfficeScoped && !serviceBelongsToOffice($conn, $serviceId, (int) $staffOffice))
        ) {
            $denyOfficeScope();
        }
        $windows = requestIntList($input, 'windows');
        if ($windows === null) {
            echo json_encode(['success' => false, 'message' => 'Invalid service window list.']);
            exit;
        }
        $result = updateService(
            $conn,
            $serviceId,
            requestString($input, 'name', 100, true) ?? '',
            $officeId,
            requestEnum($input, 'category', ['simple', 'regular', 'complicated']),
            requestString($input, 'description', 65535) ?? '',
            $windows
        );
        break;

    case 'get_windows_for_service':
        $serviceId = requestInt($input, 'service_id') ?? 0;
        if ($isOfficeScoped && $serviceId > 0 && !serviceBelongsToOffice($conn, $serviceId, (int) $staffOffice)) {
            $denyOfficeScope();
        }
        $windows   = $serviceId ? getWindowsForService($conn, $serviceId) : [];
        echo json_encode(['success' => true, 'windows' => $windows]);
        exit;

    case 'get_windows_for_office':
        $officeId = requestInt($input, 'office_id') ?? 0;
        if (!$officeId) {
            echo json_encode(['success' => false, 'window_count' => 0]);
            exit;
        }
        if (!$officeIsAllowed($officeId)) {
            $denyOfficeScope();
        }
        $stmt = $conn->prepare("SELECT window_count FROM offices WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $officeId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        echo json_encode([
            'success'      => (bool) $row,
            'window_count' => (int) ($row['window_count'] ?? 0),
        ]);
        exit;

    case 'toggle_service':
        $serviceId = requestInt($input, 'service_id') ?? 0;
        if ($isOfficeScoped && !serviceBelongsToOffice($conn, $serviceId, (int) $staffOffice)) {
            $denyOfficeScope();
        }
        $result = toggleServiceStatus($conn, $serviceId);
        break;

    case 'delete_service':
        $result = deleteService($conn, requestInt($input, 'service_id') ?? 0);
        break;

    case 'toggle_queue_mode':
        $officeId = requestInt($input, 'office_id') ?? 0;
        if (!$officeIsAllowed($officeId)) {
            $denyOfficeScope();
        }
        $result = toggleOfficeQueueMode($conn, $officeId);
        break;

    case 'set_queue_mode':
        $queueMode = requestEnum($input, 'queue_mode', ['unified', 'service-based', 'level-based']);
        if ($queueMode === null) {
            echo json_encode(['success' => false, 'message' => 'Invalid queue mode.']);
            exit;
        }
        if ($queueMode === 'level-based' && !$isAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Only an administrator can enable level-based queues.']);
            exit;
        }
        $officeId = requestInt($input, 'office_id') ?? 0;
        if (!$officeIsAllowed($officeId)) {
            $denyOfficeScope();
        }
        $result = setOfficeQueueMode($conn, $officeId, $queueMode);
        break;

    case 'save_window_levels':
        $inputLevels = $input['window_levels'] ?? null;
        if (!is_array($inputLevels)) {
            echo json_encode(['success' => false, 'message' => 'Invalid window level assignments.']);
            exit;
        }
        $levels = [];
        foreach ($inputLevels as $window => $level) {
            if (!ctype_digit((string) $window) || !is_string($level)) {
                echo json_encode(['success' => false, 'message' => 'Invalid window level assignments.']);
                exit;
            }
            $levels[(int) $window] = $level;
        }
        $result = saveOfficeWindowLevels($conn, requestInt($input, 'office_id') ?? 0, $levels);
        break;

    case 'get_services':
        if (!requestOptionalIntIsValid($input, 'office') || !requestOptionalIntIsValid($input, 'page', 1) || !requestOptionalIntIsValid($input, 'limit', 1)) {
            echo json_encode(['success' => false, 'message' => 'Invalid service list filter.']);
            exit;
        }
        $services     = getAllServicesWithOffice($conn, $staffOffice);
        $search       = strtolower(requestString($input, 'search', 100) ?? '');
        $filterOffice = $isOfficeScoped ? $staffOffice : (requestOptionalInt($input, 'office') ?? 0);
        $filterStatus = requestEnum($input, 'status', ['active', 'inactive'], false) ?? '';
        $filterCategory = requestEnum($input, 'category', ['simple', 'regular', 'complicated', 'none'], false) ?? '';

        $page  = requestOptionalInt($input, 'page', 1) ?? 1;
        $limit = requestOptionalInt($input, 'limit', 1) ?? 10;
        if (!in_array($limit, [10, 25, 50, 100])) $limit = 10;

        $filtered = array_filter($services, function ($s) use ($search, $filterOffice, $filterStatus, $filterCategory) {
            if ($filterOffice && (int)($s['office_id'] ?? 0) !== $filterOffice) return false;
            if ($filterStatus === 'active'   && !$s['is_active']) return false;
            if ($filterStatus === 'inactive' &&  $s['is_active']) return false;

            if ($filterCategory !== '') {
                if ($filterCategory === 'none') {
                    if (!empty($s['category'])) return false;
                } else {
                    if (($s['category'] ?? '') !== $filterCategory) return false;
                }
            }
            if (
                $search
                && strpos(strtolower($s['name']), $search) === false
                && strpos(strtolower($s['description'] ?? ''), $search) === false
                && strpos(strtolower($s['office_name'] ?? ''), $search) === false
            ) {
                return false;
            }
            return true;
        });

        $filtered     = array_values($filtered);
        $totalCount   = count($filtered);
        $totalPages   = $limit > 0 ? (int) ceil($totalCount / $limit) : 1;
        $page         = min($page, max(1, $totalPages));
        $offset       = ($page - 1) * $limit;
        $paginated    = array_slice($filtered, $offset, $limit);

        ob_start();
        renderServiceRows($paginated, $canEditServices, $canDeleteServices, $conn);
        $html = ob_get_clean();

        echo json_encode([
            'success'    => true,
            'html'       => $html,
            'count'      => $totalCount,
            'page'       => $page,
            'totalPages' => $totalPages,
            'limit'      => $limit,
        ]);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        exit;
}

if ($result['success']) {
    $services       = getAllServicesWithOffice($conn, $staffOffice);
    $totalServices  = count($services);
    $activeServices = count(array_filter($services, fn($s) => $s['is_active']));

    $limit      = 10;
    $paginated  = array_slice($services, 0, $limit);
    $totalPages = (int) ceil($totalServices / $limit);

    ob_start();
    renderServiceRows($paginated, $canEditServices, $canDeleteServices, $conn);
    $tableHtml = ob_get_clean();

    $result['tableHtml']        = $tableHtml;
    $result['totalServices']    = $totalServices;
    $result['activeServices']   = $activeServices;
    $result['inactiveServices'] = $totalServices - $activeServices;
    $result['count']            = $totalServices;
    $result['page']             = 1;
    $result['totalPages']       = $totalPages;
    $result['limit']            = $limit;
}

echo json_encode($result);

function getCategoryColor(?string $cat): string
{
    return match ($cat) {
        'simple'      => '#2a9d5c',
        'regular'     => '#2196f3',
        'complicated' => '#e07b1a',
        default       => '#c4cdd6',
    };
}

function renderServiceRows(array $services, bool $canEdit = false, bool $canDelete = false, ?mysqli $conn = null): void
{
    if (empty($services)) { ?>
        <tr>
            <td colspan="<?php echo ($canEdit || $canDelete) ? 5 : 4; ?>">
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-concierge-bell"></i></div>
                    <div class="empty-state-title">No services found</div>
                    <div class="empty-state-message">Add a new service or adjust your filters.</div>
                </div>
            </td>
        </tr>
    <?php return;
    }

    foreach ($services as $svc):
        $catColor        = getCategoryColor($svc['category'] ?? null);
        $assignedWindows = ($conn && $svc['id']) ? getWindowsForService($conn, $svc['id']) : [];
    ?>
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
            <?php if ($canEdit || $canDelete): ?>
                <td>
                    <div class="dropdown">
                        <button class="dropdown-toggle"><i class="fas fa-ellipsis-h"></i></button>
                        <div class="dropdown-menu">
                            <?php if ($canEdit): ?>
                                <button onclick="openEditServiceModal(<?php echo htmlspecialchars(json_encode($svc), ENT_QUOTES); ?>)">
                                    <i class="fas fa-pen"></i> Edit
                                </button>
                                <button onclick="openToggleModal(<?php echo $svc['id']; ?>, <?php echo $svc['is_active'] ? 'true' : 'false'; ?>)">
                                    <i class="fas fa-<?php echo $svc['is_active'] ? 'toggle-off' : 'toggle-on'; ?>"></i>
                                    <?php echo $svc['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                                <button class="danger" onclick="openDeleteModal(<?php echo $svc['id']; ?>)">
                                    <i class="fas fa-archive"></i> Archive
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
            <?php endif; ?>
        </tr>
<?php endforeach;
}
