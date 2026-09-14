<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config_loader.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/request_validation.php';
require_once __DIR__ . '/staff_functions.php';
require_once __DIR__ . '/feedback_functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = requestEnum($input, 'action', ['add_staff', 'edit_staff', 'get_windows_for_office', 'toggle_staff', 'delete_staff', 'reset_password', 'get_staff_feedback', 'get_staff_list']);
$csrf   = requestString($input, 'csrf_token', 255, true);

if ($action === null || $csrf === null || !verifyCsrfToken($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$canEdit   = hasPermission('edit_staff');
$isAdmin   = ($_SESSION['role_id'] ?? null) === 1 || ($_SESSION['role'] ?? '') === 'admin';
$canDelete = $isAdmin;
$canToggle = hasPermission('toggle_staff');
$canManage = $canEdit || $canDelete || $canToggle;
$canView   = $canManage || hasPermission('view_staff');

if (!$canView) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$actionPermMap = [
    'add_staff'      => 'add_staff',
    'edit_staff'     => 'edit_staff',
    'reset_password' => 'edit_staff',
    'toggle_staff'   => 'toggle_staff',
    'delete_staff'   => 'admin',
];
if (isset($actionPermMap[$action]) && $actionPermMap[$action] !== 'admin' && !hasPermission($actionPermMap[$action])) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to perform this action.']);
    exit;
}
if (isset($actionPermMap[$action]) && $actionPermMap[$action] === 'admin' && !$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to perform this action.']);
    exit;
}

$conn = $db->getConnection();

switch ($action) {

    case 'add_staff':
        if (!requestOptionalIntIsValid($input, 'window_number') || !requestOptionalIntIsValid($input, 'role_id')) {
            echo json_encode(['success' => false, 'message' => 'Invalid staff assignment.']);
            exit;
        }
        $result = addStaff(
            $conn,
            requestString($input, 'full_name', 100, true) ?? '',
            requestString($input, 'email', 100, true) ?? '',
            requestString($input, 'password', 255, true) ?? '',
            requestString($input, 'office', 100, true) ?? '',
            requestOptionalInt($input, 'window_number'),
            requestOptionalInt($input, 'role_id')
        );
        break;

    case 'edit_staff':
        if (!requestOptionalIntIsValid($input, 'window_number') || !requestOptionalIntIsValid($input, 'role_id')) {
            echo json_encode(['success' => false, 'message' => 'Invalid staff assignment.']);
            exit;
        }
        $result = updateStaff(
            $conn,
            requestInt($input, 'staff_id') ?? 0,
            requestString($input, 'full_name', 100, true) ?? '',
            requestString($input, 'email', 100, true) ?? '',
            requestString($input, 'office', 100, true) ?? '',
            requestOptionalInt($input, 'window_number'),
            requestOptionalInt($input, 'role_id')
        );
        break;

    case 'get_windows_for_office':
        $officeName = requestString($input, 'office', 100, true);
        if (!$officeName) {
            echo json_encode(['success' => false, 'message' => 'No office specified.']);
            exit;
        }
        $wcStmt = $conn->prepare("SELECT window_count FROM offices WHERE name = ? LIMIT 1");
        $wcStmt->bind_param('s', $officeName);
        $wcStmt->execute();
        $wcRow = $wcStmt->get_result()->fetch_assoc();
        $wcStmt->close();
        echo json_encode([
            'success'      => (bool) $wcRow,
            'window_count' => (int) ($wcRow['window_count'] ?? 1),
        ]);
        exit;

    case 'toggle_staff':
        $isActive = requestBool($input, 'is_active');
        if ($isActive === null) { echo json_encode(['success' => false, 'message' => 'Invalid active status.']); exit; }
        $staffId = requestInt($input, 'staff_id');
        if ($staffId === null) { echo json_encode(['success' => false, 'message' => 'Invalid staff ID.']); exit; }
        if ($isActive) {
            $result = deactivateStaff($conn, $staffId);
        } else {
            $result = activateStaff($conn, $staffId);
        }
        break;

    case 'delete_staff':
        $result = deleteStaff($conn, requestInt($input, 'staff_id') ?? 0);
        break;

    case 'reset_password':
        $result = resetStaffPassword(
            $conn,
            requestInt($input, 'staff_id') ?? 0,
            requestString($input, 'new_password', 255, true) ?? ''
        );
        break;

    case 'get_staff_feedback':
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'message' => 'Only administrators can view feedback.']);
            exit;
        }

        $staffId = requestInt($input, 'staff_id');
        if ($staffId === null) {
            echo json_encode(['success' => false, 'message' => 'Invalid staff member selected.']);
            exit;
        }

        $staff = getStaffById($conn, $staffId);
        if (!$staff) {
            echo json_encode(['success' => false, 'message' => 'Staff member not found.']);
            exit;
        }

        $roleName = $conn->prepare("SELECT name FROM roles WHERE id = ? LIMIT 1");
        $roleName->bind_param('i', $staff['role_id']);
        $roleName->execute();
        $roleRow = $roleName->get_result()->fetch_assoc();
        $roleName->close();
        $staff['role_name'] = $roleRow['name'] ?? 'staff';

        $officeName = $conn->prepare("SELECT name FROM offices WHERE id = ? LIMIT 1");
        $officeName->bind_param('i', $staff['office_id']);
        $officeName->execute();
        $officeRow = $officeName->get_result()->fetch_assoc();
        $officeName->close();
        $staff['office_name'] = $officeRow['name'] ?? null;

        if (!requestOptionalIntIsValid($input, 'rating', 1, 5) || !requestOptionalIntIsValid($input, 'page', 1) || !requestOptionalIntIsValid($input, 'limit', 1, 20)) {
            echo json_encode(['success' => false, 'message' => 'Invalid feedback filter.']);
            exit;
        }
        $ratingFilter = requestOptionalInt($input, 'rating', 1, 5);

        $allFeedback = getStaffFeedbackForStaff($conn, $staffId, $ratingFilter);
        $page = requestOptionalInt($input, 'page', 1) ?? 1;
        $limit = requestOptionalInt($input, 'limit', 1, 20) ?? 5;
        $totalCount = count($allFeedback);
        $totalPages = max(1, (int) ceil($totalCount / $limit));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $limit;
        $feedback = array_slice($allFeedback, $offset, $limit);

        $summary = ['all' => count(getStaffFeedbackForStaff($conn, $staffId))];
        for ($star = 5; $star >= 1; $star--) {
            $summary[(string) $star] = count(getStaffFeedbackForStaff($conn, $staffId, $star));
        }

        echo json_encode([
            'success' => true,
            'staff' => $staff,
            'feedback' => $feedback,
            'page' => $page,
            'limit' => $limit,
            'total' => $totalCount,
            'totalPages' => $totalPages,
            'rating_summary' => $summary,
            'rating_filter' => $ratingFilter,
        ]);
        exit;

    case 'get_staff_list':
        if (!requestOptionalIntIsValid($input, 'role') || !requestOptionalIntIsValid($input, 'page', 1) || !requestOptionalIntIsValid($input, 'limit', 1)) {
            echo json_encode(['success' => false, 'message' => 'Invalid staff list filter.']);
            exit;
        }
        $staff        = getAllStaff($conn);
        $search       = strtolower(requestString($input, 'search', 100) ?? '');
        $filterOffice = requestString($input, 'office', 100) ?? '';
        $filterStatus = requestEnum($input, 'status', ['active', 'inactive'], false) ?? '';
        $filterRole   = requestOptionalInt($input, 'role');

        $page  = requestOptionalInt($input, 'page', 1) ?? 1;
        $limit = requestOptionalInt($input, 'limit', 1) ?? 10;
        if (!in_array($limit, [10, 25, 50, 100])) $limit = 10;

        $filtered = array_filter($staff, function ($s) use ($search, $filterOffice, $filterStatus, $filterRole) {
            if ($filterOffice && ($s['office_name'] ?? '') !== $filterOffice) return false;
            if ($filterStatus === 'active'   && !$s['is_active']) return false;
            if ($filterStatus === 'inactive' &&  $s['is_active']) return false;
            if ($filterRole !== null && (int)($s['role_id'] ?? 0) !== $filterRole) return false;

            if (
                $search
                && strpos(strtolower($s['full_name']), $search) === false
                && strpos(strtolower($s['email']), $search) === false
                && strpos(strtolower($s['office_name'] ?? ''), $search) === false
            ) {
                return false;
            }
            return true;
        });

        $filtered   = array_values($filtered);
        $totalCount = count($filtered);
        $totalPages = $limit > 0 ? (int) ceil($totalCount / $limit) : 1;
        $page       = min($page, max(1, $totalPages));
        $offset     = ($page - 1) * $limit;
        $paginated  = array_slice($filtered, $offset, $limit);

        ob_start();
        renderStaffRows($paginated, $canManage);
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
    $staff         = getAllStaff($conn);
    $totalStaff    = count($staff);
    $activeStaff   = count(array_filter($staff, fn($s) => $s['is_active']));

    $limit      = 10;
    $paginated  = array_slice($staff, 0, $limit);
    $totalPages = (int) ceil($totalStaff / $limit);

    ob_start();
    renderStaffRows($paginated, $canManage);
    $tableHtml = ob_get_clean();

    $result['tableHtml']     = $tableHtml;
    $result['totalStaff']    = $totalStaff;
    $result['activeStaff']   = $activeStaff;
    $result['inactiveStaff'] = $totalStaff - $activeStaff;
    $result['count']         = $totalStaff;
    $result['page']          = 1;
    $result['totalPages']    = $totalPages;
    $result['limit']         = $limit;
}

echo json_encode($result);

function renderStaffRows(array $staff, bool $canManage = false): void
{
    if (empty($staff)) { ?>
        <tr>
            <td colspan="<?php echo $canManage ? '7' : '6'; ?>">
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-users"></i></div>
                    <div class="empty-state-title">No staff found</div>
                    <div class="empty-state-message">Add a new staff member or adjust your filters.</div>
                </div>
            </td>
        </tr>
    <?php return;
    }

    foreach ($staff as $member): ?>
        <tr>
            <td>
                <div class="staff-name-cell">
                    <div class="staff-avatar">
                        <?php echo htmlspecialchars(strtoupper(mb_substr($member['full_name'], 0, 1))); ?>
                    </div>
                    <div class="svc-name"><?php echo htmlspecialchars($member['full_name']); ?></div>
                </div>
            </td>
            <td><?php echo htmlspecialchars($member['email']); ?></td>
            <td><?php echo htmlspecialchars($member['office_name'] ?? '—'); ?></td>
            <td><?php echo $member['window_number'] ? 'Window ' . (int)$member['window_number'] : '—'; ?></td>
            <td><?php echo htmlspecialchars(ucfirst($member['role_name'] ?? '—')); ?></td>
            <td>
                <span class="status-badge <?php echo $member['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </td>
            <?php
            $canEdit   = hasPermission('edit_staff');
            $isAdminRole = ($_SESSION['role_id'] ?? null) === 1 || ($_SESSION['role'] ?? '') === 'admin';
            $canDelete = $isAdminRole;
            $canToggle = hasPermission('toggle_staff');
            if ($canEdit || $canDelete || $canToggle): ?>
                <td>
                    <div class="dropdown">
                        <button class="dropdown-toggle"><i class="fas fa-ellipsis-h"></i></button>
                        <div class="dropdown-menu">
                            <?php if ($canEdit): ?>
                                <button onclick="openEditStaffModal(<?php echo $member['id']; ?>)">
                                    <i class="fas fa-pen"></i> Edit
                                </button>
                                <button onclick="openResetPasswordModal(<?php echo $member['id']; ?>)">
                                    <i class="fas fa-key"></i> Reset Password
                                </button>
                            <?php endif; ?>
                            <?php if ($canToggle): ?>
                                <button onclick="openStaffLogsModal(<?php echo $member['id']; ?>)">
                                    <i class="fas fa-history"></i> View Logs
                                </button>
                                <button onclick="openToggleModal(<?php echo $member['id']; ?>, <?php echo $member['is_active'] ? 'true' : 'false'; ?>)">
                                    <i class="fas fa-<?php echo $member['is_active'] ? 'toggle-off' : 'toggle-on'; ?>"></i>
                                    <?php echo $member['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            <?php endif; ?>
                            <?php if ($isAdminRole): ?>
                                <button onclick="openStaffFeedbackModal(<?php echo $member['id']; ?>)">
                                    <i class="fas fa-star"></i> View Feedback
                                </button>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                                <button class="danger" onclick="openDeleteModal(<?php echo $member['id']; ?>)">
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
