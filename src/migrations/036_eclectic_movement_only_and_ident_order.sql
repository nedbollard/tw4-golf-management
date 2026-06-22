-- Migration 036: Eclectic alignment
-- 1) Ensure ident_eclectic precedes season_year in live/holding/history eclectic tables
-- 2) Move history eclectic to movement-only semantics (drop number_round_snapshot)
-- 3) Rebuild movement indexes for history eclectic

DELIMITER $$

DROP PROCEDURE IF EXISTS migrate_eclectic_alignment_036 $$
CREATE PROCEDURE migrate_eclectic_alignment_036()
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'TW4_live'
          AND table_name = 'eclectic_scores'
          AND column_name = 'ident_eclectic'
    ) THEN
        ALTER TABLE TW4_live.eclectic_scores
            MODIFY COLUMN ident_eclectic VARCHAR(16) NOT NULL AFTER row_id,
            MODIFY COLUMN season_year CHAR(5) NOT NULL AFTER ident_eclectic;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'TW4_holding'
          AND table_name = 'eclectic_scores'
          AND column_name = 'ident_eclectic'
    ) THEN
        ALTER TABLE TW4_holding.eclectic_scores
            MODIFY COLUMN ident_eclectic VARCHAR(16) NOT NULL AFTER row_id,
            MODIFY COLUMN season_year CHAR(5) NOT NULL AFTER ident_eclectic;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'TW4_history'
          AND table_name = 'eclectic_scores'
          AND column_name = 'ident_eclectic'
    ) THEN
        IF EXISTS (
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = 'TW4_history'
              AND table_name = 'eclectic_scores'
              AND index_name = 'uk_history_eclectic_scores_snapshot_ident_player'
        ) THEN
            DROP INDEX uk_history_eclectic_scores_snapshot_ident_player ON TW4_history.eclectic_scores;
        END IF;

        IF EXISTS (
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = 'TW4_history'
              AND table_name = 'eclectic_scores'
              AND index_name = 'idx_history_eclectic_scores_snapshot'
        ) THEN
            DROP INDEX idx_history_eclectic_scores_snapshot ON TW4_history.eclectic_scores;
        END IF;

        IF EXISTS (
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = 'TW4_history'
              AND table_name = 'eclectic_scores'
              AND index_name = 'idx_history_eclectic_scores_ident'
        ) THEN
            DROP INDEX idx_history_eclectic_scores_ident ON TW4_history.eclectic_scores;
        END IF;

        IF EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema = 'TW4_history'
              AND table_name = 'eclectic_scores'
              AND column_name = 'number_round_snapshot'
        ) THEN
            ALTER TABLE TW4_history.eclectic_scores
                DROP COLUMN number_round_snapshot;
        END IF;

        ALTER TABLE TW4_history.eclectic_scores
            MODIFY COLUMN ident_eclectic VARCHAR(16) NOT NULL AFTER row_id,
            MODIFY COLUMN season_year CHAR(5) NOT NULL AFTER ident_eclectic;

        IF NOT EXISTS (
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = 'TW4_history'
              AND table_name = 'eclectic_scores'
              AND index_name = 'uk_history_eclectic_scores_movement_ident_player'
        ) THEN
            CREATE UNIQUE INDEX uk_history_eclectic_scores_movement_ident_player
                ON TW4_history.eclectic_scores (ident_eclectic, season_year, number_round_movement, row_id_player);
        END IF;

        IF NOT EXISTS (
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = 'TW4_history'
              AND table_name = 'eclectic_scores'
              AND index_name = 'idx_history_eclectic_scores_movement_ident'
        ) THEN
            CREATE INDEX idx_history_eclectic_scores_movement_ident
                ON TW4_history.eclectic_scores (ident_eclectic, season_year, number_round_movement);
        END IF;
    END IF;
END $$

CALL migrate_eclectic_alignment_036() $$
DROP PROCEDURE IF EXISTS migrate_eclectic_alignment_036 $$

DELIMITER ;
