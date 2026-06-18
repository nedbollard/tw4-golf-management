-- Best five history should track only standing changes (movement), not full round snapshots.
-- Remove number_round_snapshot and move uniqueness/indexing to movement round + player.

DROP PROCEDURE IF EXISTS migrate_best_five_history_movement_only;
DELIMITER $$
CREATE PROCEDURE migrate_best_five_history_movement_only()
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = 'TW4_history'
          AND table_name = 'best_five_scores'
          AND index_name = 'uk_history_best_five_scores_snapshot_player'
    ) THEN
        DROP INDEX uk_history_best_five_scores_snapshot_player ON TW4_history.best_five_scores;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = 'TW4_history'
          AND table_name = 'best_five_scores'
          AND index_name = 'uk_history_best_five_snapshot_player'
    ) THEN
        DROP INDEX uk_history_best_five_snapshot_player ON TW4_history.best_five_scores;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = 'TW4_history'
          AND table_name = 'best_five_scores'
          AND index_name = 'idx_history_best_five_scores_snapshot'
    ) THEN
        DROP INDEX idx_history_best_five_scores_snapshot ON TW4_history.best_five_scores;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = 'TW4_history'
          AND table_name = 'best_five_scores'
          AND index_name = 'idx_history_best_five_snapshot'
    ) THEN
        DROP INDEX idx_history_best_five_snapshot ON TW4_history.best_five_scores;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'TW4_history'
          AND table_name = 'best_five_scores'
          AND column_name = 'number_round_snapshot'
    ) THEN
        ALTER TABLE TW4_history.best_five_scores
            DROP COLUMN number_round_snapshot;
    END IF;

    -- Historical snapshot-era data can contain duplicate rows for the same
    -- movement round + player. Keep the latest row before enforcing uniqueness.
    DELETE bf1
    FROM TW4_history.best_five_scores bf1
    INNER JOIN TW4_history.best_five_scores bf2
        ON bf1.season_year = bf2.season_year
       AND bf1.number_round_movement = bf2.number_round_movement
       AND bf1.row_id_player = bf2.row_id_player
       AND bf1.row_id < bf2.row_id;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = 'TW4_history'
          AND table_name = 'best_five_scores'
          AND index_name = 'uk_history_best_five_scores_movement_player'
    ) THEN
        CREATE UNIQUE INDEX uk_history_best_five_scores_movement_player
            ON TW4_history.best_five_scores (season_year, number_round_movement, row_id_player);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = 'TW4_history'
          AND table_name = 'best_five_scores'
          AND index_name = 'idx_history_best_five_scores_movement'
    ) THEN
        CREATE INDEX idx_history_best_five_scores_movement
            ON TW4_history.best_five_scores (season_year, number_round_movement);
    END IF;
END$$
DELIMITER ;

CALL migrate_best_five_history_movement_only();
DROP PROCEDURE IF EXISTS migrate_best_five_history_movement_only;
