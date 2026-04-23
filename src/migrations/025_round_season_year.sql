-- Migration 025: Add season_year to the permanent TW4_live.round row and seed config.
-- Run against TW4_live database first, then TW4_base if config seed is needed.

USE TW4_live;

DELIMITER $$

DROP PROCEDURE IF EXISTS migrate_round_025 $$
CREATE PROCEDURE migrate_round_025()
BEGIN
    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END;
    DECLARE CONTINUE HANDLER FOR 1061 BEGIN END;

    ALTER TABLE round ADD COLUMN season_year CHAR(5) NULL AFTER row_id;
    ALTER TABLE round ADD UNIQUE KEY uk_round_season_number (season_year, number_round);
END $$

CALL migrate_round_025() $$
DROP PROCEDURE migrate_round_025 $$

DELIMITER ;