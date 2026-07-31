-- Track whether card entry was reopened so saved cards remain selectable only after a reset.

SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'TW4_live'
      AND TABLE_NAME = 'round'
      AND COLUMN_NAME = 'card_entry_reopened'
);

SET @sql = IF(
    @column_exists = 0,
    'ALTER TABLE TW4_live.round ADD COLUMN card_entry_reopened TINYINT(1) NOT NULL DEFAULT 0 AFTER card_count',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;