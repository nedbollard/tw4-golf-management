-- Migration 038: Add per-round
-- history context for eclectic report naming/behavior.
--
-- Forward-only change: no backfill is performed.

USE TW4_base;

CREATE TABLE IF NOT EXISTS TW4_history.round_eclectic_context (
    row_id INT NOT NULL AUTO_INCREMENT,
    season_year CHAR(5) NOT NULL,
    number_round INT NOT NULL,
    include_eclectic TINYINT(1) NOT NULL DEFAULT 0,
    configured_ident_eclectic VARCHAR(16) DEFAULT NULL,
    played_course_name VARCHAR(64) DEFAULT NULL,
    combined_name VARCHAR(64) DEFAULT NULL,
    course_report_files_json TEXT DEFAULT NULL,
    combined_report_filename VARCHAR(128) DEFAULT NULL,
    updated_by VARCHAR(100) NOT NULL,
    updated_ts TIMESTAMP NULL DEFAULT NULL,
    hist_updated_by VARCHAR(100) NOT NULL,
    hist_updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (row_id),
    UNIQUE KEY uk_round_eclectic_context_round (season_year, number_round),
    KEY idx_round_eclectic_context_include (include_eclectic)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;