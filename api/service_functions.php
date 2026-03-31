<?php
function serviceResponse(bool $success, string $message, array $extra = []): array
{
    return array_merge(['success' => $success, 'message' => $message], $extra);
}

function cleanStr(string $value): string
{
    return trim(strip_tags($value));
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
    if ($officeId <= 0) {
        return serviceResponse(false, 'Invalid office.');
    }

    $office = getOfficeById($conn, $officeId);
    if (!$office) {
        return serviceResponse(false, 'Office not found.');
    }

    $newMode = $office['queue_mode'] === 'service-based' ? 'unified' : 'service-based';

    $stmt = $conn->prepare("UPDATE offices SET queue_mode = ? WHERE id = ?");
    if (!$stmt) {
        return serviceResponse(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param('si', $newMode, $officeId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return serviceResponse(false, 'Failed to update queue mode.');
    }

    $label = $newMode === 'service-based' ? 'Service-Based' : 'Unified';
    return serviceResponse(true, "Queue mode switched to {$label} for \"{$office['name']}\".");
}

function getAllServicesWithOffice(mysqli $conn): array
{
    $sql = "SELECT
                tt.id,
                tt.name,
                tt.category,
                tt.description,
                tt.is_active,
                tt.office_id,
                o.name AS office_name
            FROM transaction_types tt
            LEFT JOIN offices o ON o.id = tt.office_id
            WHERE tt.is_deleted = 0
            ORDER BY o.name ASC, tt.name ASC";

    $res = $conn->query($sql);
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

/**
 * Single non-deleted service by ID.
 */
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
    return $row;
}

function validateCategory(?string $category): ?string
{
    if ($category === null || $category === '') return null;
    $allowed = ['simple', 'regular', 'complicated'];
    $cat     = strtolower(cleanStr($category));
    return in_array($cat, $allowed, true) ? $cat : null;
}

/**
 * Duplicate name check — ignores soft-deleted rows.
 */
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

function addService(
    mysqli  $conn,
    string  $name,
    mixed   $officeId,
    ?string $category,
    string  $description
): array {
    $name        = cleanStr($name);
    $officeId    = (int) $officeId;
    $category    = validateCategory($category);
    $description = cleanStr($description);

    if ($name === '') {
        return serviceResponse(false, 'Service name is required.');
    }
    if (strlen($name) > 100) {
        return serviceResponse(false, 'Service name must not exceed 100 characters.');
    }
    if ($officeId <= 0) {
        return serviceResponse(false, 'Please select a valid office.');
    }

    $office = getOfficeById($conn, $officeId);
    if (!$office) {
        return serviceResponse(false, 'Selected office does not exist.');
    }

    if (serviceNameExistsForOffice($conn, $name, $officeId)) {
        return serviceResponse(
            false,
            "A service named \"{$name}\" already exists for {$office['name']}."
        );
    }

    $stmt = $conn->prepare(
        "INSERT INTO transaction_types (name, category, description, is_active, is_deleted, office_id)
         VALUES (?, ?, ?, 1, 0, ?)"
    );
    if (!$stmt) {
        return serviceResponse(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param('sssi', $name, $category, $description, $officeId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return serviceResponse(false, 'Failed to add service. Please try again.');
    }

    return serviceResponse(true, "Service \"{$name}\" has been added successfully.");
}

function updateService(
    mysqli  $conn,
    int     $serviceId,
    string  $name,
    mixed   $officeId,
    ?string $category,
    string  $description
): array {
    $name        = cleanStr($name);
    $officeId    = (int) $officeId;
    $category    = validateCategory($category);
    $description = cleanStr($description);

    if ($serviceId <= 0) {
        return serviceResponse(false, 'Invalid service.');
    }
    if ($name === '') {
        return serviceResponse(false, 'Service name is required.');
    }
    if (strlen($name) > 100) {
        return serviceResponse(false, 'Service name must not exceed 100 characters.');
    }
    if ($officeId <= 0) {
        return serviceResponse(false, 'Please select a valid office.');
    }

    $service = getServiceById($conn, $serviceId);
    if (!$service) {
        return serviceResponse(false, 'Service not found.');
    }

    $office = getOfficeById($conn, $officeId);
    if (!$office) {
        return serviceResponse(false, 'Selected office does not exist.');
    }

    if (serviceNameExistsForOffice($conn, $name, $officeId, $serviceId)) {
        return serviceResponse(
            false,
            "A service named \"{$name}\" already exists for {$office['name']}."
        );
    }

    $stmt = $conn->prepare(
        "UPDATE transaction_types
         SET name = ?, category = ?, description = ?, office_id = ?
         WHERE id = ? AND is_deleted = 0"
    );
    if (!$stmt) {
        return serviceResponse(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param('sssii', $name, $category, $description, $officeId, $serviceId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return serviceResponse(false, 'Failed to update service. Please try again.');
    }

    return serviceResponse(true, "Service \"{$name}\" has been updated successfully.");
}

function toggleServiceStatus(mysqli $conn, int $serviceId): array
{
    if ($serviceId <= 0) {
        return serviceResponse(false, 'Invalid service.');
    }

    $service = getServiceById($conn, $serviceId);
    if (!$service) {
        return serviceResponse(false, 'Service not found.');
    }

    $newStatus = $service['is_active'] ? 0 : 1;
    $label     = $newStatus ? 'activated' : 'deactivated';

    $stmt = $conn->prepare(
        "UPDATE transaction_types SET is_active = ? WHERE id = ? AND is_deleted = 0"
    );
    if (!$stmt) {
        return serviceResponse(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param('ii', $newStatus, $serviceId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return serviceResponse(false, 'Failed to update service status.');
    }

    return serviceResponse(
        true,
        "Service \"{$service['name']}\" has been {$label}."
    );
}

/**
 * Soft-delete: marks is_deleted = 1 and deactivates the service.
 * The row stays in the database for audit/history purposes.
 */
function deleteService(mysqli $conn, int $serviceId): array
{
    if ($serviceId <= 0) {
        return serviceResponse(false, 'Invalid service.');
    }

    $service = getServiceById($conn, $serviceId);
    if (!$service) {
        return serviceResponse(false, 'Service not found.');
    }

    $stmt = $conn->prepare(
        "UPDATE transaction_types SET is_deleted = 1, is_active = 0 WHERE id = ?"
    );
    if (!$stmt) {
        return serviceResponse(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param('i', $serviceId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return serviceResponse(false, 'Failed to delete service. Please try again.');
    }

    return serviceResponse(
        true,
        "Service \"{$service['name']}\" has been deleted."
    );
}

function getTotalServiceCount(mysqli $conn): int
{
    $res = $conn->query(
        "SELECT COUNT(*) AS cnt FROM transaction_types WHERE is_deleted = 0"
    );
    if (!$res) return 0;
    return (int) $res->fetch_assoc()['cnt'];
}

function getActiveServiceCount(mysqli $conn): int
{
    $res = $conn->query(
        "SELECT COUNT(*) AS cnt FROM transaction_types WHERE is_active = 1 AND is_deleted = 0"
    );
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
