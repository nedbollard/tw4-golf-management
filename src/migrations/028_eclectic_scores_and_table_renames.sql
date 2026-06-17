-- Structural setup for eclectic scoring and naming alignment
-- 1) Rename best_five -> best_five_scores across live/holding/history
-- 2) Refactor course_played_hole to number_hole_course + number_hole_played
-- 3) Create eclectic_scores tables in live/holding/history

-- Rename best_five tables where required
DROP PROCEDURE IF EXISTS migrate_best_five_table_names;
DELIMITER $$
CREATE PROCEDURE migrate_best_five_table_names()
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'TW4_live' AND table_name = 'best_five'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'TW4_live' AND table_name = 'best_five_scores'
    ) THEN
        RENAME TABLE TW4_live.best_five TO TW4_live.best_five_scores;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'TW4_holding' AND table_name = 'best_five'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'TW4_holding' AND table_name = 'best_five_scores'
    ) THEN
        RENAME TABLE TW4_holding.best_five TO TW4_holding.best_five_scores;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'TW4_history' AND table_name = 'best_five'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'TW4_history' AND table_name = 'best_five_scores'
    ) THEN
        RENAME TABLE TW4_history.best_five TO TW4_history.best_five_scores;
    END IF;
END$$
DELIMITER ;
CALL migrate_best_five_table_names();
DROP PROCEDURE IF EXISTS migrate_best_five_table_names;

-- course_played_hole refactor
-- Existing mapping rows are intentionally discarded per implementation decision.
DELETE FROM course_played_hole;

DROP PROCEDURE IF EXISTS migrate_course_played_hole_columns;
DELIMITER $$
CREATE PROCEDURE migrate_course_played_hole_columns()
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'course_played_hole'
          AND column_name = 'number_hole'
    ) THEN
        ALTER TABLE course_played_hole
            CHANGE COLUMN number_hole number_hole_course INT NOT NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'course_played_hole'
          AND column_name = 'number_hole_played'
    ) THEN
        ALTER TABLE course_played_hole
            ADD COLUMN number_hole_played INT NOT NULL AFTER number_hole_course;
    END IF;
END$$
DELIMITER ;
CALL migrate_course_played_hole_columns();
DROP PROCEDURE IF EXISTS migrate_course_played_hole_columns;

DROP PROCEDURE IF EXISTS migrate_course_played_hole_indexes;
DELIMITER $$
CREATE PROCEDURE migrate_course_played_hole_indexes()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'course_played_hole'
          AND index_name = 'idx_course_played_hole_course_played_id'
    ) THEN
        CREATE INDEX idx_course_played_hole_course_played_id
            ON course_played_hole (course_played_id);
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'course_played_hole'
          AND index_name = 'unique_course_played_number_hole'
    ) THEN
        DROP INDEX unique_course_played_number_hole ON course_played_hole;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'course_played_hole'
          AND index_name = 'unique_course_played_number_hole_course'
    ) THEN
        CREATE UNIQUE INDEX unique_course_played_number_hole_course
            ON course_played_hole (course_played_id, number_hole_course);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'course_played_hole'
          AND index_name = 'unique_course_played_number_hole_played'
    ) THEN
        CREATE UNIQUE INDEX unique_course_played_number_hole_played
            ON course_played_hole (course_played_id, number_hole_played);
    END IF;
END$$
DELIMITER ;
CALL migrate_course_played_hole_indexes();
DROP PROCEDURE IF EXISTS migrate_course_played_hole_indexes;

-- Eclectic scores tables
CREATE TABLE IF NOT EXISTS TW4_live.eclectic_scores (
    row_id INT NOT NULL AUTO_INCREMENT,
    season_year CHAR(5) NOT NULL,
    ident_eclectic VARCHAR(16) NOT NULL,
    row_id_player INT NOT NULL,
    number_round_movement INT NOT NULL DEFAULT 0,
    score_total INT NOT NULL DEFAULT 0,
    score_hole_1 INT NOT NULL DEFAULT 0,
    score_hole_2 INT NOT NULL DEFAULT 0,
    score_hole_3 INT NOT NULL DEFAULT 0,
    score_hole_4 INT NOT NULL DEFAULT 0,
    score_hole_5 INT NOT NULL DEFAULT 0,
    score_hole_6 INT NOT NULL DEFAULT 0,
    score_hole_7 INT NOT NULL DEFAULT 0,
    score_hole_8 INT NOT NULL DEFAULT 0,
    score_hole_9 INT NOT NULL DEFAULT 0,
    updated_by VARCHAR(100) NOT NULL,
    updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (row_id),
    UNIQUE KEY uk_eclectic_scores_season_ident_player (season_year, ident_eclectic, row_id_player),
    KEY idx_eclectic_scores_player (row_id_player),
    KEY idx_eclectic_scores_season (season_year),
    KEY idx_eclectic_scores_ident (ident_eclectic)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS TW4_holding.eclectic_scores (
    row_id INT NOT NULL AUTO_INCREMENT,
    season_year CHAR(5) NOT NULL,
    ident_eclectic VARCHAR(16) NOT NULL,
    row_id_player INT NOT NULL,
    number_round_movement INT NOT NULL DEFAULT 0,
    score_total INT NOT NULL DEFAULT 0,
    score_hole_1 INT NOT NULL DEFAULT 0,
    score_hole_2 INT NOT NULL DEFAULT 0,
    score_hole_3 INT NOT NULL DEFAULT 0,
    score_hole_4 INT NOT NULL DEFAULT 0,
    score_hole_5 INT NOT NULL DEFAULT 0,
    score_hole_6 INT NOT NULL DEFAULT 0,
    score_hole_7 INT NOT NULL DEFAULT 0,
    score_hole_8 INT NOT NULL DEFAULT 0,
    score_hole_9 INT NOT NULL DEFAULT 0,
    updated_by VARCHAR(100) NOT NULL,
    updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (row_id),
    UNIQUE KEY uk_eclectic_scores_season_ident_player (season_year, ident_eclectic, row_id_player),
    KEY idx_eclectic_scores_player (row_id_player),
    KEY idx_eclectic_scores_season (season_year),
    KEY idx_eclectic_scores_ident (ident_eclectic)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS TW4_history.eclectic_scores (
    row_id INT NOT NULL AUTO_INCREMENT,
    season_year CHAR(5) NOT NULL,
    number_round_snapshot INT NOT NULL,
    ident_eclectic VARCHAR(16) NOT NULL,
    row_id_player INT NOT NULL,
    number_round_movement INT NOT NULL DEFAULT 0,
    score_total INT NOT NULL DEFAULT 0,
    score_hole_1 INT NOT NULL DEFAULT 0,
    score_hole_2 INT NOT NULL DEFAULT 0,
    score_hole_3 INT NOT NULL DEFAULT 0,
    score_hole_4 INT NOT NULL DEFAULT 0,
    score_hole_5 INT NOT NULL DEFAULT 0,
    score_hole_6 INT NOT NULL DEFAULT 0,
    score_hole_7 INT NOT NULL DEFAULT 0,
    score_hole_8 INT NOT NULL DEFAULT 0,
    score_hole_9 INT NOT NULL DEFAULT 0,
    updated_by VARCHAR(100) NOT NULL,
    updated_ts TIMESTAMP NULL DEFAULT NULL,
    hist_updated_by VARCHAR(100) NOT NULL,
    hist_updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (row_id),
    UNIQUE KEY uk_history_eclectic_scores_snapshot_ident_player (season_year, number_round_snapshot, ident_eclectic, row_id_player),
    KEY idx_history_eclectic_scores_snapshot (season_year, number_round_snapshot),
    KEY idx_history_eclectic_scores_player (row_id_player),
    KEY idx_history_eclectic_scores_ident (ident_eclectic)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
