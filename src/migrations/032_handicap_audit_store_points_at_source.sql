-- Migration 032: Store handicap points context at source in handicap_audit.
-- Adds points_scored / points_effective columns and backfills historical card_scoring rows.
-- Safe to run multiple times.

DELIMITER $$

DROP PROCEDURE IF EXISTS migrate_handicap_audit_store_points $$
CREATE PROCEDURE migrate_handicap_audit_store_points()
BEGIN
    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END; -- duplicate column
    DECLARE CONTINUE HANDLER FOR 1061 BEGIN END; -- duplicate key

    ALTER TABLE handicap_audit
        ADD COLUMN points_scored INT NULL AFTER row_id_player,
        ADD COLUMN points_effective INT NULL AFTER points_scored;

    UPDATE handicap_audit ha
    LEFT JOIN (
        SELECT
            hc.season_year,
            hc.number_round,
            hc.row_id_player,
            SUM(cbh.points) AS points_scored,
            SUM(CASE WHEN cbh.points = 0 THEN 1 ELSE cbh.points END) AS points_effective
        FROM TW4_history.card hc
        INNER JOIN TW4_history.card_by_hole cbh ON cbh.row_id_card = hc.row_id
        GROUP BY hc.season_year, hc.number_round, hc.row_id_player
    ) pts
      ON pts.season_year COLLATE utf8mb4_general_ci = ha.season_year
     AND pts.number_round = ha.number_round
     AND pts.row_id_player = ha.row_id_player
    SET ha.points_scored = COALESCE(pts.points_scored, 0),
        ha.points_effective = COALESCE(pts.points_effective, 0)
    WHERE ha.handicap_source = 'card_scoring'
      AND (ha.points_scored IS NULL OR ha.points_effective IS NULL);
END $$

CALL migrate_handicap_audit_store_points() $$
DROP PROCEDURE IF EXISTS migrate_handicap_audit_store_points $$

DELIMITER ;
