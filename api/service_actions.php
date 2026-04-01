<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/service_functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? '';
$csrf   = $input['csrf_token'] ?? '';

if (!verifyCsrfToken($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$conn = $db->getConnection();

switch ($action) {

    case 'add_service':
        $result = addService(
            $conn,
            $input['name']        ?? '',
            $input['office_id']   ?? 0,
            $input['category']    ?? null,
            $input['description'] ?? ''
        );
        break;

    case 'edit_service':
        $result = updateService(
            $conn,
            (int) ($input['service_id'] ?? 0),
            $input['name']        ?? '',
            $input['office_id']   ?? 0,
            $input['category']    ?? null,
            $input['description'] ?? ''
        );
        break;

    case 'toggle_service':
        $result = toggleServiceStatus($conn, (int) ($input['service_id'] ?? 0));
        break;

    case 'delete_service':
        $result = deleteService($conn, (int) ($input['service_id'] ?? 0));
        break;

    case 'toggle_queue_mode':
        $result = toggleOfficeQueueMode($conn, (int) ($input['office_id'] ?? 0));
        break;

    case 'get_services':
        $services     = getAllServicesWithOffice($conn);
        $search       = strtolower(trim($input['search'] ?? ''));
        $filterOffice = (int) ($input['office'] ?? 0);
        $filterStatus = $input['status'] ?? '';

        $filtered = array_filter($services, function ($s) use ($search, $filterOffice, $filterStatus) {
            if ($filterOffice && $s['office_id'] !== $filterOffice) return false;
            if ($filterStatus === 'active'   && !$s['is_active'])   return false;
            if ($filterStatus === 'inactive' &&  $s['is_active'])   return false;
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

        ob_start();
        renderServiceRows(array_values($filtered));
        $html = ob_get_clean();

        echo json_encode([
            'success' => true,
            'html'    => $html,
            'count'   => count($filtered),
        ]);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        exit;
}

if ($result['success']) {
    $services       = getAllServicesWithOffice($conn);
    $totalServices  = count($services);
    $activeServices = count(array_filter($services, fn($s) => $s['is_active']));

    ob_start();
    renderServiceRows($services);
    $tableHtml = ob_get_clean();

    $result['tableHtml']        = $tableHtml;
    $result['totalServices']    = $totalServices;
    $result['activeServices']   = $activeServices;
    $result['inactiveServices'] = $totalServices - $activeServices;
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

function renderServiceRows(array $services): void
{
    if (empty($services)) { ?>
        <tr>
            <td colspan="5">
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
        $catColor = getCategoryColor($svc['category'] ?? null);
    ?>
        <tr>
            <td>
                <div class="service-name-cell">
                    <div class="service-icon-dot" style="background:<?php echo $catColor; ?>"></div>
                    <div>
                        <div class="svc-name"><?php echo htmlspecialchars($svc['name']); ?></div>
                        <?php if ($svc['description']): ?>
                            <div class="svc-desc"><?php echo htmlspecialchars($svc['description']); ?></div>
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
            <td>
                <div class="dropdown">
                    <button class="dropdown-toggle"><i class="fas fa-ellipsis-h"></i></button>
                    <div class="dropdown-menu">
                        <button onclick="openEditServiceModal(<?php echo htmlspecialchars(json_encode($svc), ENT_QUOTES); ?>)">
                            <i class="fas fa-pen"></i> Edit
                        </button>
                        <button onclick="openToggleModal(<?php echo $svc['id']; ?>, <?php echo $svc['is_active'] ? 'true' : 'false'; ?>)">
                            <i class="fas fa-<?php echo $svc['is_active'] ? 'toggle-off' : 'toggle-on'; ?>"></i>
                            <?php echo $svc['is_active'] ? 'Deactivate' : 'Activate'; ?>
                        </button>
                        <button class="danger" onclick="openDeleteModal(<?php echo $svc['id']; ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </td>
        </tr>
<?php endforeach;
}
