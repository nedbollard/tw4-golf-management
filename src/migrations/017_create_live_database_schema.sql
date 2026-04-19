-- Migration 017: Create TW4_live schema
-- TW4_live is the active scoring database. It holds data only for the current
-- round. On "Finish Round" its contents are transferred to TW4_history and
-- then cleared from here.
--
-- Run against the TW4_live database:
--   docker compose exec -e MYSQL_PWD=${DB_PASSWORD} db mysql -u root TW4_live < src/migrations/017_create_live_database_schema.sql

USE TW4_live;

-- ─────────────────────────────────────────────────────────────────────────────
-- live_rounds
-- One row exists at any time representing the round currently in progress.
-- workflow_step drives the scorer menu state machine.
-- locked_by_staff_id / lock_* columns enforce single-scorer operation.
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS live_rounds (
    round_id             INT            NOT NULL AUTO_INCREMENT,

    -- Round identity (mirrors TW4.rounds once transferred to history)
    season_type          ENUM('standard','twilight') NOT NULL DEFAULT 'standard',
    round_number         INT            NOT NULL,
    round_date           DATE           NOT NULL,
    course_played_id     INT            NULL COMMENT 'FK to TW4.course_played',
    notes                TEXT           NULL,

    -- Scorer workflow state machine
    -- not_started       : round row created, card entry not yet opened
    -- card_entry_open   : scorer is entering cards (Enter Cards = In Progress)
    -- results_presented : Present Results completed; card entry closed
    -- finished          : Finish Round completed; ready for history transfer
    workflow_step        ENUM(
                             'not_started',
                             'card_entry_open',
                             'results_presented',
                             'finished'
                         ) NOT NULL DEFAULT 'not_started',

    results_presented_at TIMESTAMP      NULL DEFAULT NULL,
    finished_at          TIMESTAMP      NULL DEFAULT NULL,

    -- History transfer tracking (set during Finish Round)
    transfer_status      ENUM('pending','in_progress','completed','failed')
                             NOT NULL DEFAULT 'pending',
    transfer_started_at  TIMESTAMP      NULL DEFAULT NULL,
    transfer_completed_at TIMESTAMP     NULL DEFAULT NULL,

    -- Single-scorer lock
    -- locked_by_staff_id references TW4.staff.row_id (cross-DB; enforced in app)
    locked_by_staff_id   INT            NULL DEFAULT NULL,
    lock_acquired_at     TIMESTAMP      NULL DEFAULT NULL,
    lock_expires_at      TIMESTAMP      NULL DEFAULT NULL,
    lock_released_at     TIMESTAMP      NULL DEFAULT NULL,
    lock_release_reason  ENUM('logout','session_expired','admin_forced','finished')
                             NULL DEFAULT NULL,

    -- Audit
    created_by_staff_id  INT            NOT NULL COMMENT 'FK to TW4.staff.row_id',
    created_at           TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP
                             ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (round_id),
    INDEX idx_workflow_step    (workflow_step),
    INDEX idx_locked_by        (locked_by_staff_id),
    INDEX idx_lock_expires     (lock_expires_at),
    INDEX idx_round_date       (round_date),
    INDEX idx_transfer_status  (transfer_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ─────────────────────────────────────────────────────────────────────────────
-- live_score_cards
-- One row per player per round. Populated during Enter Cards.
-- Cleared from here after successful transfer to history.
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS live_score_cards (
    card_id          INT  NOT NULL AUTO_INCREMENT,
    round_id         INT  NOT NULL,

    -- Player identity (references TW4.roster.row_id; enforced in app)
    player_id        INT  NOT NULL,
    handicap_used    INT  NULL,

    -- Running totals updated as hole scores are saved
    total_score      INT  NOT NULL DEFAULT 0,
    total_points     INT  NOT NULL DEFAULT 0,

    -- Card lifecycle
    status           ENUM('pending','verified') NOT NULL DEFAULT 'pending',
    verified_by      INT  NULL COMMENT 'FK to TW4.staff.row_id',
    verified_at      TIMESTAMP NULL DEFAULT NULL,

    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                         ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (card_id),
    UNIQUE KEY uk_round_player (round_id, player_id),
    CONSTRAINT fk_lsc_round FOREIGN KEY (round_id)
        REFERENCES live_rounds (round_id) ON DELETE CASCADE,
    INDEX idx_lsc_round    (round_id),
    INDEX idx_lsc_player   (player_id),
    INDEX idx_lsc_status   (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ─────────────────────────────────────────────────────────────────────────────
-- live_hole_scores
-- Individual hole scores for each card.
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS live_hole_scores (
    score_id    INT NOT NULL AUTO_INCREMENT,
    card_id     INT NOT NULL,

    -- Hole reference (references TW4.course_played_hole; enforced in app)
    hole_number TINYINT UNSIGNED NOT NULL,
    score       TINYINT UNSIGNED NOT NULL,
    points      TINYINT UNSIGNED NOT NULL DEFAULT 0,

    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (score_id),
    UNIQUE KEY  uk_card_hole (card_id, hole_number),
    CONSTRAINT  fk_lhs_card  FOREIGN KEY (card_id)
        REFERENCES live_score_cards (card_id) ON DELETE CASCADE,
    INDEX idx_lhs_card (card_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
