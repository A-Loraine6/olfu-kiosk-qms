ALTER TABLE offices
    MODIFY COLUMN queue_mode ENUM('unified', 'service-based', 'level-based') DEFAULT 'unified';

SET @student_level_exists = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'tickets'
      AND column_name = 'student_level'
);

SET @student_level_ddl = IF(
    @student_level_exists = 0,
    'ALTER TABLE tickets ADD COLUMN student_level ENUM(''shs'', ''college'') NULL AFTER transaction_type',
    'SELECT 1'
);

PREPARE student_level_stmt FROM @student_level_ddl;
EXECUTE student_level_stmt;
DEALLOCATE PREPARE student_level_stmt;

SET @student_level_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'tickets'
      AND index_name = 'idx_tickets_office_student_level_status'
);

SET @student_level_index_ddl = IF(
    @student_level_index_exists = 0,
    'ALTER TABLE tickets ADD INDEX idx_tickets_office_student_level_status (office_id, student_level, status)',
    'SELECT 1'
);

PREPARE student_level_index_stmt FROM @student_level_index_ddl;
EXECUTE student_level_index_stmt;
DEALLOCATE PREPARE student_level_index_stmt;

CREATE TABLE IF NOT EXISTS window_levels (
    office_id INT NOT NULL,
    window_number TINYINT UNSIGNED NOT NULL,
    student_level ENUM('shs', 'college', 'both') NOT NULL DEFAULT 'both',
    PRIMARY KEY (office_id, window_number),
    KEY idx_window_levels_office_level (office_id, student_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
