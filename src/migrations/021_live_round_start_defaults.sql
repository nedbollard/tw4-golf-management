-- Migration 021: Add course_played selection to the permanent TW4_live.round row.
-- Run against TW4_live database.

USE TW4_live;

DELIMITER $$

DROP PROCEDURE IF EXISTS migrate_round_021 $$
CREATE PROCEDURE migrate_round_021()
BEGIN
    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END;
    DECLARE CONTINUE HANDLER FOR 1061 BEGIN END;

    ALTER TABLE round ADD COLUMN course_played_id INT NULL AFTER round_date;
    ALTER TABLE round ADD INDEX idx_round_course_played_id (course_played_id);
END $$

CALL migrate_round_021() $$
DROP PROCEDURE migrate_round_021 $$

DELIMITER ;

INSERT INTO round (number_round, updated_by)
SELECT 0, 'system'
WHERE NOT EXISTS (SELECT 1 FROM round);