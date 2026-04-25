<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/staff_functions.php';

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

    case 'add_staff':
        $result = addStaff(
            $conn,
            $input['full_name']     ?? '',
            $input['email']         ?? '',
            $input['password']      ?? '',
            $input['office']        ?? '',
            isset($input['window_number']) && $input['window_number'] !== ''
                ? (int) $input['window_number']
                : null
        );
        break;

    case 'edit_staff':
        $result = updateStaff(
            $conn,
            (int) ($input['staff_id'] ?? 0),
            $input['full_name']     ?? '',
            $input['email']         ?? '',
            $input['office']        ?? '',
            isset($input['window_number']) && $input['window_number'] !== ''
                ? (int) $input['window_number']
                : null
        );
        break;

    case 'get_windows_for_office':
        $officeName = trim($input['office'] ?? '');
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
        $isActive = (bool) ($input['is_active'] ?? false);
        if ($isActive) {
            $result = deactivateStaff($conn, (int) ($input['staff_id'] ?? 0));
        } else {
            $result = activateStaff($conn, (int) ($input['staff_id'] ?? 0));
        }
        break;

    case 'delete_staff':
        $result = deleteStaff($conn, (int) ($input['staff_id'] ?? 0));
        break;

    case 'reset_password':
        $result = resetStaffPassword(
            $conn,
            (int) ($input['staff_id']    ?? 0),
            $input['new_password'] ?? ''
        );
        break;

    case 'get_staff_list':
        $staff        = getAllStaff($conn);
        $search       = strtolower(trim($input['search'] ?? ''));
        $filterOffice = trim($input['office'] ?? '');
        $filterStatus = $input['status'] ?? '';

        $filtered = array_filter($staff, function ($s) use ($search, $filterOffice, $filterStatus) {
            if ($filterOffice && ($s['office_name'] ?? '') !== $filterOffice) return false;
            if ($filterStatus === 'active'   && !$s['is_active']) return false;
            if ($filterStatus === 'inactive' &&  $s['is_active']) return false;
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

        ob_start();
        renderStaffRows(array_values($filtered));
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
    $staff         = getAllStaff($conn);
    $totalStaff    = count($staff);
    $activeStaff   = count(array_filter($staff, fn($s) => $s['is_active']));

    ob_start();
    renderStaffRows($staff);
    $tableHtml = ob_get_clean();

    $result['tableHtml']      = $tableHtml;
    $result['totalStaff']     = $totalStaff;
    $result['activeStaff']    = $activeStaff;
    $result['inactiveStaff']  = $totalStaff - $activeStaff;
}

echo json_encode($result);

function renderStaffRows(array $staff): void
{
    if (empty($staff)) { ?>
        <tr>
            <td colspan="5">
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
            <td>
                <span class="status-badge <?php echo $member['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </td>
            <td>
                <div class="dropdown">
                    <button class="dropdown-toggle"><i class="fas fa-ellipsis-h"></i></button>
                    <div class="dropdown-menu">
                        <button onclick="openEditStaffModal(<?php echo $member['id']; ?>)">
                            <i class="fas fa-pen"></i> Edit
                        </button>
                        <button onclick="openToggleModal(<?php echo $member['id']; ?>, <?php echo $member['is_active'] ? 'true' : 'false'; ?>)">
                            <i class="fas fa-<?php echo $member['is_active'] ? 'toggle-off' : 'toggle-on'; ?>"></i>
                            <?php echo $member['is_active'] ? 'Deactivate' : 'Activate'; ?>
                        </button>
                        <button onclick="openResetPasswordModal(<?php echo $member['id']; ?>)">
                            <i class="fas fa-key"></i> Reset Password
                        </button>
                        <button class="danger" onclick="openDeleteModal(<?php echo $member['id']; ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </td>
        </tr>
<?php endforeach;
}
