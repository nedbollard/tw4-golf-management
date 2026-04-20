-- Migration 024: Ensure TW4_live.results exists for present-results workflow.
-- This migration supports legacy live table names/columns and normalizes to:
-- results(row_id, type_result, number_result, player_identifier, value_result, updated_by, updated_ts)

USE TW4_live;

DELIMITER $$

DROP PROCEDURE IF EXISTS migrate_results_024 $$
CREATE PROCEDURE migrate_results_024()
BEGIN
    DECLARE v_result_exists INT DEFAULT 0;
    DECLARE v_results_exists INT DEFAULT 0;
    DECLARE v_ident_player_exists INT DEFAULT 0;
    DECLARE v_row_id_player_exists INT DEFAULT 0;

    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END;
    DECLARE CONTINUE HANDLER FOR 1061 BEGIN END;
    DECLARE CONTINUE HANDLER FOR 1091 BEGIN END;

    SELECT COUNT(*) INTO v_result_exists
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'result';

    SELECT COUNT(*) INTO v_results_exists
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'results';

    IF v_result_exists > 0 AND v_results_exists = 0 THEN
        RENAME TABLE result TO results;
    END IF;

    CREATE TABLE IF NOT EXISTS results (
        row_id INT NOT NULL AUTO_INCREMENT,
        type_result VARCHAR(16) NOT NULL,
        number_result INT NOT NULL,
        player_identifier VARCHAR(50) NOT NULL,
        value_result INT NOT NULL,
        updated_by VARCHAR(100) NOT NULL,
        updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (row_id),
        INDEX idx_results_type_result (type_result),
        INDEX idx_results_player_identifier (player_identifier)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    SELECT COUNT(*) INTO v_ident_player_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'results' AND COLUMN_NAME = 'ident_player';

    IF v_ident_player_exists > 0 THEN
        ALTER TABLE results CHANGE COLUMN ident_player player_identifier VARCHAR(50) NOT NULL;
    END IF;

    SELECT COUNT(*) INTO v_row_id_player_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'results' AND COLUMN_NAME = 'row_id_player';

    IF v_row_id_player_exists > 0 THEN
        ALTER TABLE results ADD COLUMN player_identifier VARCHAR(50) NULL AFTER number_result;

        UPDATE results rs
        INNER JOIN TW4_base.roster r ON r.row_id = rs.row_id_player
        SET rs.player_identifier = r.player_identifier
        WHERE rs.player_identifier IS NULL;

        UPDATE results
        SET player_identifier = 'unknown'
        WHERE player_identifier IS NULL OR TRIM(player_identifier) = '';

        ALTER TABLE results MODIFY COLUMN player_identifier VARCHAR(50) NOT NULL;
        ALTER TABLE results DROP COLUMN row_id_player;
    END IF;

    ALTER TABLE results MODIFY COLUMN type_result VARCHAR(16) NOT NULL;
    ALTER TABLE results MODIFY COLUMN number_result INT NOT NULL;
    ALTER TABLE results MODIFY COLUMN player_identifier VARCHAR(50) NOT NULL;
    ALTER TABLE results MODIFY COLUMN value_result INT NOT NULL;
    ALTER TABLE results MODIFY COLUMN updated_by VARCHAR(100) NOT NULL;

    ALTER TABLE results ADD INDEX idx_results_type_result (type_result);
    ALTER TABLE results ADD INDEX idx_results_player_identifier (player_identifier);
END $$

CALL migrate_results_024() $$
DROP PROCEDURE migrate_results_024 $$

DELIMITER ;
