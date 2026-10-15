SET @original_created_at_exists = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'tickets'
      AND column_name = 'original_created_at'
);

SET @original_created_at_ddl = IF(
    @original_created_at_exists = 0,
    'ALTER TABLE tickets ADD COLUMN original_created_at DATETIME NULL AFTER created_at',
    'SELECT 1'
);

PREPARE original_created_at_stmt FROM @original_created_at_ddl;
EXECUTE original_created_at_stmt;
DEALLOCATE PREPARE original_created_at_stmt;

UPDATE tickets
SET original_created_at = created_at
WHERE original_created_at IS NULL;

CREATE TABLE IF NOT EXISTS ticket_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    `event` VARCHAR(50) NOT NULL,
    from_status VARCHAR(20) NULL,
    to_status VARCHAR(20) NULL,
    staff_id INT NULL,
    office_id INT NOT NULL,
    window_id INT NULL,
    `meta` JSON NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY idx_ticket_events_ticket_created (ticket_id, created_at),
    KEY idx_ticket_events_office_created (office_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
