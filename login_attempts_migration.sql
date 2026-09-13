-- Run once on an existing QMS database.
CREATE TABLE IF NOT EXISTS login_attempts (
  identifier VARCHAR(320) NOT NULL,
  attempt_count INT NOT NULL DEFAULT 0,
  last_attempt_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (identifier),
  KEY idx_login_attempts_last_attempt (last_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE activity_logs
  MODIFY user_id INT NULL;