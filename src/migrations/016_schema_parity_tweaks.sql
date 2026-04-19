-- Final schema parity tweaks for canonical rebuild vs live TW4
-- Focus: roster index/comment normalization and course_played_hole collation/comment alignment.

-- ---------------------------
-- roster parity adjustments
-- ---------------------------

-- Drop legacy secondary indexes if present
SET @has_idx_alias := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'roster'
      AND INDEX_NAME = 'idx_alias'
);
SET @sql_drop_idx_alias := IF(@has_idx_alias > 0, 'ALTER TABLE roster DROP INDEX idx_alias', 'SELECT 1');
PREPARE stmt_drop_idx_alias FROM @sql_drop_idx_alias;
EXECUTE stmt_drop_idx_alias;
DEALLOCATE PREPARE stmt_drop_idx_alias;

SET @has_idx_status := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'roster'
      AND INDEX_NAME = 'idx_status'
);
SET @sql_drop_idx_status := IF(@has_idx_status > 0, 'ALTER TABLE roster DROP INDEX idx_status', 'SELECT 1');
PREPARE stmt_drop_idx_status FROM @sql_drop_idx_status;
EXECUTE stmt_drop_idx_status;
DEALLOCATE PREPARE stmt_drop_idx_status;

SET @has_idx_player_identifier := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'roster'
      AND INDEX_NAME = 'idx_player_identifier'
);
SET @sql_drop_idx_player_identifier := IF(@has_idx_player_identifier > 0, 'ALTER TABLE roster DROP INDEX idx_player_identifier', 'SELECT 1');
PREPARE stmt_drop_idx_player_identifier FROM @sql_drop_idx_player_identifier;
EXECUTE stmt_drop_idx_player_identifier;
DEALLOCATE PREPARE stmt_drop_idx_player_identifier;

-- Rename unique key from uk_player_identifier to player_identifier if needed
SET @has_uk_player_identifier := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'roster'
      AND INDEX_NAME = 'uk_player_identifier'
);
SET @has_player_identifier_idx := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'roster'
      AND INDEX_NAME = 'player_identifier'
);

SET @sql_rekey_player_identifier := IF(
    @has_uk_player_identifier > 0 AND @has_player_identifier_idx = 0,
    'ALTER TABLE roster DROP INDEX uk_player_identifier, ADD UNIQUE KEY player_identifier (player_identifier)',
    IF(@has_uk_player_identifier > 0 AND @has_player_identifier_idx > 0,
       'ALTER TABLE roster DROP INDEX uk_player_identifier',
       'SELECT 1')
);
PREPARE stmt_rekey_player_identifier FROM @sql_rekey_player_identifier;
EXECUTE stmt_rekey_player_identifier;
DEALLOCATE PREPARE stmt_rekey_player_identifier;

-- Remove column comments/table comments to match live TW4 definition
ALTER TABLE roster
    MODIFY COLUMN row_id INT NOT NULL AUTO_INCREMENT,
    MODIFY COLUMN player_identifier VARCHAR(50) NOT NULL,
    MODIFY COLUMN first_name VARCHAR(100) NOT NULL,
    MODIFY COLUMN last_name VARCHAR(100) NOT NULL,
    MODIFY COLUMN alias VARCHAR(50) NULL,
    MODIFY COLUMN gender ENUM('male', 'female') NOT NULL,
  MODIFY COLUMN status ENUM('active', 'scored', 'inactive') NOT NULL DEFAULT 'active' AFTER gender,
  MODIFY COLUMN handicap INT DEFAULT 0 AFTER status;

ALTER TABLE roster COMMENT = '';

-- ------------------------------------
-- course_played_hole parity adjustments
-- ------------------------------------

ALTER TABLE course_played_hole
  MODIFY COLUMN updated_by VARCHAR(32) NOT NULL,
    COMMENT = ' ',
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
