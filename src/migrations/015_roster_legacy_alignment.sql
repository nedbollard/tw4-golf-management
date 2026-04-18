-- Consolidate legacy roster migrations from database/migrations into canonical src/migrations
-- This migration aligns roster schema with production expectations and is safe to run once.

-- Ensure date_first_played exists in the expected position.
SET @has_date_first_played := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'roster'
      AND COLUMN_NAME = 'date_first_played'
);

SET @add_date_sql := IF(
    @has_date_first_played = 0,
    'ALTER TABLE roster ADD COLUMN date_first_played DATE NULL AFTER handicap',
    'SELECT 1'
);
PREPARE add_date_stmt FROM @add_date_sql;
EXECUTE add_date_stmt;
DEALLOCATE PREPARE add_date_stmt;

-- Keep updated_by consistent with application usage.
ALTER TABLE roster
    MODIFY COLUMN updated_by VARCHAR(50) NULL;

-- If legacy first_play_date exists, migrate values and remove old column.
SET @has_first_play_date := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'roster'
      AND COLUMN_NAME = 'first_play_date'
);

SET @migrate_sql := IF(
    @has_first_play_date > 0,
    'UPDATE roster SET date_first_played = COALESCE(date_first_played, first_play_date)',
    'SELECT 1'
);
PREPARE migrate_stmt FROM @migrate_sql;
EXECUTE migrate_stmt;
DEALLOCATE PREPARE migrate_stmt;

SET @drop_sql := IF(
    @has_first_play_date > 0,
    'ALTER TABLE roster DROP COLUMN first_play_date',
    'SELECT 1'
);
PREPARE drop_stmt FROM @drop_sql;
EXECUTE drop_stmt;
DEALLOCATE PREPARE drop_stmt;
