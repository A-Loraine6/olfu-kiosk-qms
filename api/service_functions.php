<?php
require_once __DIR__ . '/activity_logger.php';

function serviceResponse(bool $success, string $message, array $extra = []): array
{
    return array_merge(['success' => $success, 'message' => $message], $extra);
}

function cleanStr(string $value): string
{
    return trim(strip_tags($value));
}

function diffSnapshot(array $before, array $after): array
{
    $changedBefore = [];
    $changedAfter  = [];
    foreach ($after as $key => $newVal) {
        $oldVal = $before[$key] ?? null;
        if ($oldVal != $newVal) {
            $changedBefore[$key] = $oldVal;
            $changedAfter[$key]  = $newVal;
        }
    }
    return [$changedBefore, $changedAfter];
}

function getAllOffices(mysqli $conn): array
{
    $sql = "SELECT id, name, code, description, window_count, queue_mode
            FROM offices
            ORDER BY name ASC";
    $res = $conn->query($sql);
    if (!$res) return [];

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $row['id']           = (int) $row['id'];
        $row['window_count'] = (int) $row['window_count'];
        $rows[]              = $row;
    }
    return $rows;
}

function getOfficeById(mysqli $conn, int $officeId): ?array
{
    $stmt = $conn->prepare(
        "SELECT id, name, code, description, window_count, queue_mode
         FROM offices WHERE id = ? LIMIT 1"
    );
    if (!$stmt) return null;

    $stmt->bind_param('i', $officeId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return null;
    $row['id']           = (int) $row['id'];
    $row['window_count'] = (int) $row['window_count'];
    return $row;
}

function toggleOfficeQueueMode(mysqli $conn, int $officeId): array
{
    if ($officeId <= 0) return serviceResponse(false, 'Invalid office.');

    $office = getOfficeById($conn, $officeId);
    if (!$office) return serviceResponse(false, 'Office not found.');

    $newMode = $office['queue_mode'] === 'service-based' ? 'unified' : 'service-based';

    $stmt = $conn->prepare("UPDATE offices SET queue_mode = ? WHERE id = ?");
    if (!$stmt) return serviceResponse(false, 'Database error: ' . $conn->error);

    $stmt->bind_param('si', $newMode, $officeId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) return serviceResponse(false, 'Failed to update queue mode.');

    logActivity(
        $conn,
        'OFFICE_QUEUE_MODE_TOGGLE',
        'OFFICE',
        $officeId,
        "Queue mode switched to $newMode for {$office['name']}",
        ['queue_mode' => $office['queue_mode']],
        ['queue_mode' => $newMode]
    );

    $label = $newMode === 'service-based' ? 'Service-Based' : 'Unified';
    return serviceResponse(true, "Queue mode switched to {$label} for \"{$office['name']}\".");
}

function getAllServicesWithOffice(mysqli $conn, ?int $officeId = null): array
{
    $sql = "SELECT
                tt.id, tt.name, tt.category, tt.description,
                tt.is_active, tt.office_id, o.name AS office_name
            FROM transaction_types tt
            LEFT JOIN offices o ON o.id = tt.office_id
            WHERE tt.is_deleted = 0";

    if ($officeId !== null) {
        $sql .= " AND tt.office_id = ?";
    }

    $sql .= " ORDER BY o.name ASC, tt.name ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    if ($officeId !== null) {
        $stmt->bind_param('i', $officeId);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res) return [];

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $row['id']        = (int)  $row['id'];
        $row['office_id'] = $row['office_id'] !== null ? (int) $row['office_id'] : null;
        $row['is_active'] = (bool) $row['is_active'];
        $rows[]           = $row;
    }
    return $rows;
}

function getServiceById(mysqli $conn, int $serviceId): ?array
{
    $stmt = $conn->prepare(
        "SELECT tt.id, tt.name, tt.category, tt.description,
                tt.is_active, tt.office_id, o.name AS office_name
         FROM transaction_types tt
         LEFT JOIN offices o ON o.id = tt.office_id
         WHERE tt.id = ? AND tt.is_deleted = 0
         LIMIT 1"
    );
    if (!$stmt) return null;

    $stmt->bind_param('i', $serviceId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return null;
    $row['id']        = (int)  $row['id'];
    $row['office_id'] = $row['office_id'] !== null ? (int) $row['office_id'] : null;
    $row['is_active'] = (bool) $row['is_active'];
    $row['assigned_windows'] = getWindowsForService($conn, $row['id']);
    return $row;
}

function validateCategory(?string $category): ?string
{
    if ($category === null || $category === '') return null;
    $allowed = ['simple', 'regular', 'complicated'];
    $cat     = strtolower(cleanStr($category));
    return in_array($cat, $allowed, true) ? $cat : null;
}

function serviceNameExistsForOffice(
    mysqli $conn,
    string $name,
    int    $officeId,
    int    $excludeId = 0
): bool {
    $stmt = $conn->prepare(
        "SELECT id FROM transaction_types
         WHERE name = ? AND office_id = ? AND id != ? AND is_deleted = 0
         LIMIT 1"
    );
    if (!$stmt) return false;

    $stmt->bind_param('sii', $name, $officeId, $excludeId);
    $stmt->execute();
    $stmt->store_result();
    $found = $stmt->num_rows > 0;
    $stmt->close();
    return $found;
}

function addService(mysqli $conn, string $name, mixed $officeId, ?string $category, string $description, array $windowNumbers = []): array
{
    $name        = cleanStr($name);
    $officeId    = (int) $officeId;
    $category    = validateCategory($category);
    $description = cleanStr($description);

    if ($name === '')        return serviceResponse(false, 'Service name is required.');
    if (strlen($name) > 100) return serviceResponse(false, 'Service name must not exceed 100 characters.');
    if ($officeId <= 0)      return serviceResponse(false, 'Please select a valid office.');

    $office = getOfficeById($conn, $officeId);
    if (!$office) return serviceResponse(false, 'Selected office does not exist.');

    if (serviceNameExistsForOffice($conn, $name, $officeId)) {
        return serviceResponse(false, "A service named \"{$name}\" already exists for {$office['name']}.");
    }

    $stmt = $conn->prepare(
        "INSERT INTO transaction_types (name, category, description, is_active, is_deleted, office_id)
         VALUES (?, ?, ?, 1, 0, ?)"
    );
    if (!$stmt) return serviceResponse(false, 'Database error: ' . $conn->error);

    $stmt->bind_param('sssi', $name, $category, $description, $officeId);
    $ok    = $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();

    if (!$ok) return serviceResponse(false, 'Failed to add service. Please try again.');

    syncServiceWindows($conn, $newId, $officeId, $windowNumbers);

    logActivity(
        $conn,
        'SERVICE_ADD',
        'SERVICE',
        $newId,
        "Added new service \"$name\" for {$office['name']}",
        null,
        ['name' => $name, 'category' => $category, 'description' => $description, 'office' => $office['name'], 'windows' => $windowNumbers]
    );

    return serviceResponse(true, "Service \"{$name}\" has been added successfully.", ['service_id' => $newId]);
}

function updateService(mysqli $conn, int $serviceId, string $name, mixed $officeId, ?string $category, string $description, array $windowNumbers = []): array
{
    $name        = cleanStr($name);
    $officeId    = (int) $officeId;
    $category    = validateCategory($category);
    $description = cleanStr($description);

    if ($serviceId <= 0)     return serviceResponse(false, 'Invalid service.');
    if ($name === '')        return serviceResponse(false, 'Service name is required.');
    if (strlen($name) > 100) return serviceResponse(false, 'Service name must not exceed 100 characters.');
    if ($officeId <= 0)      return serviceResponse(false, 'Please select a valid office.');

    $service = getServiceById($conn, $serviceId);
    if (!$service) return serviceResponse(false, 'Service not found.');

    $office = getOfficeById($conn, $officeId);
    if (!$office) return serviceResponse(false, 'Selected office does not exist.');

    if (serviceNameExistsForOffice($conn, $name, $officeId, $serviceId)) {
        return serviceResponse(false, "A service named \"{$name}\" already exists for {$office['name']}.");
    }

    $stmt = $conn->prepare(
        "UPDATE transaction_types
         SET name = ?, category = ?, description = ?, office_id = ?
         WHERE id = ? AND is_deleted = 0"
    );
    if (!$stmt) return serviceResponse(false, 'Database error: ' . $conn->error);

    $stmt->bind_param('sssii', $name, $category, $description, $officeId, $serviceId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) return serviceResponse(false, 'Failed to update service. Please try again.');

    syncServiceWindows($conn, $serviceId, $officeId, $windowNumbers);

    [$before, $after] = diffSnapshot(
        ['name' => $service['name'], 'category' => $service['category'], 'description' => $service['description'], 'office' => $service['office_name'], 'windows' => $service['assigned_windows']],
        ['name' => $name,            'category' => $category,            'description' => $description,            'office' => $office['name'], 'windows' => $windowNumbers]
    );

    if (!empty($before)) {
        logActivity($conn, 'SERVICE_EDIT', 'SERVICE', $serviceId, "Updated service \"$name\"", $before, $after);
    }

    return serviceResponse(true, "Service \"$name\" has been updated successfully.");
}

function toggleServiceStatus(mysqli $conn, int $serviceId): array
{
    if ($serviceId <= 0) return serviceResponse(false, 'Invalid service.');

    $service = getServiceById($conn, $serviceId);
    if (!$service) return serviceResponse(false, 'Service not found.');

    $newStatus = $service['is_active'] ? 0 : 1;
    $label     = $newStatus ? 'activated' : 'deactivated';

    $stmt = $conn->prepare(
        "UPDATE transaction_types SET is_active = ? WHERE id = ? AND is_deleted = 0"
    );
    if (!$stmt) return serviceResponse(false, 'Database error: ' . $conn->error);

    $stmt->bind_param('ii', $newStatus, $serviceId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) return serviceResponse(false, 'Failed to update service status.');

    logActivity(
        $conn,
        'SERVICE_TOGGLE_STATUS',
        'SERVICE',
        $serviceId,
        "Service \"{$service['name']}\" has been $label",
        ['is_active' => $service['is_active']],
        ['is_active' => (bool) $newStatus]
    );

    return serviceResponse(true, "Service \"{$service['name']}\" has been {$label}.");
}

function deleteService(mysqli $conn, int $serviceId): array
{
    if ($serviceId <= 0) return serviceResponse(false, 'Invalid service.');

    $service = getServiceById($conn, $serviceId);
    if (!$service) return serviceResponse(false, 'Service not found.');

    $stmt = $conn->prepare(
        "UPDATE transaction_types SET is_deleted = 1, deleted_at = NOW(), is_active = 0 WHERE id = ?"
    );
    if (!$stmt) return serviceResponse(false, 'Database error: ' . $conn->error);

    $stmt->bind_param('i', $serviceId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) return serviceResponse(false, 'Failed to delete service. Please try again.');

    logActivity(
        $conn,
        'SERVICE_DELETE',
        'SERVICE',
        $serviceId,
        "Deleted service: {$service['name']}"
    );

    return serviceResponse(true, "Service \"{$service['name']}\" has been deleted.");
}

function getTotalServiceCount(mysqli $conn): int
{
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM transaction_types WHERE is_deleted = 0");
    if (!$res) return 0;
    return (int) $res->fetch_assoc()['cnt'];
}

function getActiveServiceCount(mysqli $conn): int
{
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM transaction_types WHERE is_active = 1 AND is_deleted = 0");
    if (!$res) return 0;
    return (int) $res->fetch_assoc()['cnt'];
}

function getServicesByOffice(mysqli $conn, int $officeId): array
{
    $stmt = $conn->prepare(
        "SELECT id, name, category, description, is_active
         FROM transaction_types
         WHERE office_id = ? AND is_deleted = 0
         ORDER BY name ASC"
    );
    if (!$stmt) return [];

    $stmt->bind_param('i', $officeId);
    $stmt->execute();
    $res  = $stmt->get_result();
    $stmt->close();

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $row['id']        = (int)  $row['id'];
        $row['is_active'] = (bool) $row['is_active'];
        $rows[]           = $row;
    }
    return $rows;
}

function getActiveServicesByOffice(mysqli $conn, int $officeId): array
{
    $stmt = $conn->prepare(
        "SELECT id, name, category, description
         FROM transaction_types
         WHERE office_id = ? AND is_active = 1 AND is_deleted = 0
         ORDER BY
             FIELD(category, 'simple', 'regular', 'complicated') ASC,
             name ASC"
    );
    if (!$stmt) return [];

    $stmt->bind_param('i', $officeId);
    $stmt->execute();
    $res  = $stmt->get_result();
    $stmt->close();

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $row['id'] = (int) $row['id'];
        $rows[]    = $row;
    }
    return $rows;
}

function getWindowsForService(mysqli $conn, int $serviceId): array
{
    $stmt = $conn->prepare(
        "SELECT window_number FROM service_windows WHERE service_id = ? ORDER BY window_number ASC"
    );
    if (!$stmt) return [];
    $stmt->bind_param('i', $serviceId);
    $stmt->execute();
    $res  = $stmt->get_result();
    $stmt->close();

    $windows = [];
    while ($row = $res->fetch_assoc()) {
        $windows[] = (int) $row['window_number'];
    }
    return $windows;
}

function syncServiceWindows(mysqli $conn, int $serviceId, int $officeId, array $windowNumbers): bool
{
    $del = $conn->prepare("DELETE FROM service_windows WHERE service_id = ?");
    if (!$del) return false;
    $del->bind_param('i', $serviceId);
    $del->execute();
    $del->close();

    if (empty($windowNumbers)) return true;

    $ins = $conn->prepare(
        "INSERT INTO service_windows (service_id, office_id, window_number) VALUES (?, ?, ?)"
    );
    if (!$ins) return false;

    foreach ($windowNumbers as $wn) {
        $wn = (int) $wn;
        if ($wn < 1) continue;
        $ins->bind_param('iii', $serviceId, $officeId, $wn);
        $ins->execute();
    }
    $ins->close();
    return true;
}
