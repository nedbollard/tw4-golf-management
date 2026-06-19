-- Migration 035: Enforce canonical handicap_audit column order.
-- This is a readability/consistency migration only.
-- Safe to run multiple times.

DELIMITER $$

DROP PROCEDURE IF EXISTS migrate_handicap_audit_column_order_standard $$
CREATE PROCEDURE migrate_handicap_audit_column_order_standard()
BEGIN
    DECLARE v_table_exists INT DEFAULT 0;

    SELECT COUNT(*) INTO v_table_exists
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'handicap_audit';

    IF v_table_exists = 1 THEN
        ALTER TABLE handicap_audit
            MODIFY COLUMN season_year CHAR(5) NULL COMMENT 'Season the card was played, e.g. 25_26' AFTER row_id,
            MODIFY COLUMN number_round INT NULL COMMENT 'Round number within that season' AFTER season_year,
            MODIFY COLUMN row_id_player INT NOT NULL AFTER number_round,
            MODIFY COLUMN points_scored INT NULL COMMENT 'Raw Stableford points scored on the card' AFTER row_id_player,
            MODIFY COLUMN points_effective INT NULL COMMENT 'Points used for handicap change calculation' AFTER points_scored,
            MODIFY COLUMN handicap_previous INT NOT NULL COMMENT 'Handicap before this change' AFTER points_effective,
            MODIFY COLUMN handicap_new INT NOT NULL COMMENT 'Handicap after this change' AFTER handicap_previous,
            MODIFY COLUMN handicap_source ENUM('card_scoring','admin_adjustment','system_import')
                NOT NULL DEFAULT 'admin_adjustment' AFTER handicap_new,
            MODIFY COLUMN reason VARCHAR(255) NULL COMMENT 'Why was this changed (for admin adjustments)' AFTER handicap_source,
            MODIFY COLUMN updated_by VARCHAR(100) NOT NULL AFTER reason,
            MODIFY COLUMN updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER updated_by;
    END IF;
END $$

CALL migrate_handicap_audit_column_order_standard() $$
DROP PROCEDURE IF EXISTS migrate_handicap_audit_column_order_standard $$

DELIMITER ;
