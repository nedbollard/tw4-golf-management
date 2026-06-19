-- Migration 034: Ensure row_id_player is positioned before points columns in handicap_audit.
-- This is a column-order readability migration only.
-- Safe to run multiple times.

DELIMITER $$

DROP PROCEDURE IF EXISTS migrate_handicap_audit_promote_row_id_player $$
CREATE PROCEDURE migrate_handicap_audit_promote_row_id_player()
BEGIN
    DECLARE v_has_row_id_player INT DEFAULT 0;

    SELECT COUNT(*) INTO v_has_row_id_player
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'handicap_audit'
      AND column_name = 'row_id_player';

    IF v_has_row_id_player = 1 THEN
        ALTER TABLE handicap_audit
            MODIFY COLUMN row_id_player INT NOT NULL AFTER number_round;
    END IF;
END $$

CALL migrate_handicap_audit_promote_row_id_player() $$
DROP PROCEDURE IF EXISTS migrate_handicap_audit_promote_row_id_player $$

DELIMITER ;
