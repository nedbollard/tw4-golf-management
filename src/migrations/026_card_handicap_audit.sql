-- Migration 026: Refactor card table for handicap tracking and create dedicated handicap audit trail
-- Changes:
--  1. Remove row_id_round from live card table (redundant; only current round exists)
--  2. Rename handicap to handicap_applied (the handicap used to calculate points)
--  3. Add handicap_updated after points (handicap value after card scoring)
--  4. Create handicap_audit table in TW4_base for independent admin handicap adjustments
--
-- Run against TW4_live first, then TW4_base:
--   docker compose exec -e MYSQL_PWD=${DB_PASSWORD} db mysql -u root TW4_live < src/migrations/026_card_handicap_audit.sql
--   docker compose exec -e MYSQL_PWD=${DB_PASSWORD} db mysql -u root TW4_base < src/migrations/026_card_handicap_audit.sql

USE TW4_live;

DELIMITER $$

DROP PROCEDURE IF EXISTS migrate_card_026_live $$
CREATE PROCEDURE migrate_card_026_live()
BEGIN
    DECLARE CONTINUE HANDLER FOR 1054 BEGIN END; -- unknown column (already renamed/dropped)
    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END; -- duplicate column
    DECLARE CONTINUE HANDLER FOR 1061 BEGIN END; -- duplicate key
    DECLARE CONTINUE HANDLER FOR 1091 BEGIN END; -- can't drop index (doesn't exist)

    -- Drop old foreign key and index
    ALTER TABLE card DROP FOREIGN KEY fk_card_round;
    ALTER TABLE card DROP INDEX uk_card_round_player;
    ALTER TABLE card DROP INDEX idx_card_round;

    -- Drop row_id_round column
    ALTER TABLE card DROP COLUMN row_id_round;

    -- Rename handicap to handicap_applied
    ALTER TABLE card CHANGE COLUMN handicap handicap_applied INT NOT NULL;

    -- Add handicap_updated after points
    ALTER TABLE card ADD COLUMN handicap_updated INT NULL AFTER points;

    -- Create new unique key on player only (one live round, one card per player)
    ALTER TABLE card ADD UNIQUE KEY uk_card_player (row_id_player);

END $$

CALL migrate_card_026_live() $$
DROP PROCEDURE migrate_card_026_live $$

DELIMITER ;

-- ─────────────────────────────────────────────────────────────────────────────
-- Now create the handicap audit table in TW4_base
-- ─────────────────────────────────────────────────────────────────────────────

USE TW4_base;

DELIMITER $$

DROP PROCEDURE IF EXISTS migrate_card_026_base $$
CREATE PROCEDURE migrate_card_026_base()
BEGIN
    DECLARE CONTINUE HANDLER FOR 1050 BEGIN END;

    CREATE TABLE IF NOT EXISTS handicap_audit (
        row_id INT AUTO_INCREMENT PRIMARY KEY,

        -- Player identity (references roster.row_id)
        row_id_player INT NOT NULL,

        -- Handicap change details
        handicap_previous INT NOT NULL COMMENT 'Handicap before this change',
        handicap_new INT NOT NULL COMMENT 'Handicap after this change',
        handicap_source ENUM(
            'card_scoring',      -- Automatically applied after card entry
            'admin_adjustment',  -- Manually changed by admin
            'system_import'      -- Bulk imported/reset
        ) NOT NULL DEFAULT 'admin_adjustment',

        -- Context for the change (durable references; live card/round row are ephemeral)
        season_year CHAR(5) NULL COMMENT 'Season the card was played, e.g. 25_26',
        number_round INT NULL COMMENT 'Round number within that season',
        reason VARCHAR(255) NULL COMMENT 'Why was this changed (for admin adjustments)',

        -- Audit trail
        changed_by VARCHAR(100) NOT NULL,
        changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_by VARCHAR(100) NOT NULL,
        updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        FOREIGN KEY (row_id_player) REFERENCES roster (row_id) ON DELETE CASCADE,
        INDEX idx_player (row_id_player),
        INDEX idx_source (handicap_source),
        INDEX idx_changed_at (changed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

END $$

CALL migrate_card_026_base() $$
DROP PROCEDURE migrate_card_026_base $$

DELIMITER ;
