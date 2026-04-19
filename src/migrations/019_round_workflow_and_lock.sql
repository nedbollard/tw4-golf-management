-- Migration 019: Extend TW4_live.round with scorer workflow + locking columns
-- Run against TW4_live database
--
-- Example:
--   docker compose exec -e MYSQL_PWD=${DB_PASSWORD} db mysql -u root TW4_live < src/migrations/019_round_workflow_and_lock.sql

USE TW4_live;

DELIMITER $$

CREATE PROCEDURE migrate_round_019()
BEGIN
    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END;
    DECLARE CONTINUE HANDLER FOR 1061 BEGIN END;

    ALTER TABLE round ADD COLUMN round_date DATE NULL AFTER number_round;
    ALTER TABLE round ADD COLUMN workflow_step ENUM('not_started','card_entry_open','results_presented','finished','cancelled') NOT NULL DEFAULT 'not_started' AFTER updated_ts;
    ALTER TABLE round ADD COLUMN card_count INT NOT NULL DEFAULT 0 AFTER workflow_step;
    ALTER TABLE round ADD COLUMN results_presented_at TIMESTAMP NULL DEFAULT NULL AFTER card_count;
    ALTER TABLE round ADD COLUMN finished_at TIMESTAMP NULL DEFAULT NULL AFTER results_presented_at;
    ALTER TABLE round ADD COLUMN locked_by_staff_id INT NULL DEFAULT NULL AFTER finished_at;
    ALTER TABLE round ADD COLUMN lock_acquired_at TIMESTAMP NULL DEFAULT NULL AFTER locked_by_staff_id;
    ALTER TABLE round ADD COLUMN lock_expires_at TIMESTAMP NULL DEFAULT NULL AFTER lock_acquired_at;
    ALTER TABLE round ADD COLUMN lock_released_by_staff_id INT NULL DEFAULT NULL AFTER lock_expires_at;
    ALTER TABLE round ADD COLUMN lock_released_at TIMESTAMP NULL DEFAULT NULL AFTER lock_released_by_staff_id;
    ALTER TABLE round ADD COLUMN lock_release_reason ENUM('logout','session_expired','admin_forced','finished') NULL DEFAULT NULL AFTER lock_released_at;
    ALTER TABLE round MODIFY COLUMN updated_by VARCHAR(100) NULL AFTER lock_release_reason;
    ALTER TABLE round MODIFY COLUMN updated_ts TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER updated_by;

    ALTER TABLE round ADD INDEX idx_round_workflow_step (workflow_step);
    ALTER TABLE round ADD INDEX idx_round_locked_by_staff_id (locked_by_staff_id);
    ALTER TABLE round ADD INDEX idx_round_lock_expires_at (lock_expires_at);
END $$

CALL migrate_round_019() $$
DROP PROCEDURE migrate_round_019 $$

DELIMITER ;

-- Ensure seeded baseline row exists after shape change.
INSERT INTO round (number_round, updated_by)
SELECT 0, 'system'
WHERE NOT EXISTS (SELECT 1 FROM round);
