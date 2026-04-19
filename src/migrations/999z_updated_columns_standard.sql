-- Migration 999z: Ensure all tables have updated_by and updated_ts, with both as trailing columns.
-- Runs against the current selected database.

DELIMITER $$

DROP PROCEDURE IF EXISTS enforce_updated_columns_standard $$
CREATE PROCEDURE enforce_updated_columns_standard()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_table VARCHAR(64);
    DECLARE v_anchor VARCHAR(64);
    DECLARE v_has_updated_by INT DEFAULT 0;
    DECLARE v_has_updated_ts INT DEFAULT 0;
    DECLARE v_updated_by_def TEXT;
    DECLARE v_updated_ts_def TEXT;
    DECLARE v_parts TEXT;
    DECLARE v_sql TEXT;

    DECLARE cur CURSOR FOR
        SELECT t.table_name
        FROM information_schema.tables t
        WHERE t.table_schema = DATABASE()
          AND t.table_type = 'BASE TABLE';

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;

    table_loop: LOOP
        FETCH cur INTO v_table;
        IF done = 1 THEN
            LEAVE table_loop;
        END IF;

        SET v_anchor = NULL;
        SET v_has_updated_by = 0;
        SET v_has_updated_ts = 0;
        SET v_updated_by_def = NULL;
        SET v_updated_ts_def = NULL;
        SET v_parts = '';

        SELECT c.column_name
          INTO v_anchor
        FROM information_schema.columns c
        WHERE c.table_schema = DATABASE()
          AND c.table_name = v_table
          AND c.column_name NOT IN ('updated_by', 'updated_ts')
        ORDER BY c.ordinal_position DESC
        LIMIT 1;

        SELECT COUNT(*) INTO v_has_updated_by
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = v_table
          AND column_name = 'updated_by';

        SELECT COUNT(*) INTO v_has_updated_ts
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = v_table
          AND column_name = 'updated_ts';

        IF v_has_updated_by = 0 THEN
            SET v_sql = CONCAT(
                'ALTER TABLE `', REPLACE(v_table, '`', '``'),
                '` ADD COLUMN `updated_by` VARCHAR(100) NULL ',
                IF(v_anchor IS NULL, 'FIRST', CONCAT('AFTER `', REPLACE(v_anchor, '`', '``'), '`'))
            );
            SET @add_updated_by_sql = v_sql;
            PREPARE stmt_add_updated_by FROM @add_updated_by_sql;
            EXECUTE stmt_add_updated_by;
            DEALLOCATE PREPARE stmt_add_updated_by;
        END IF;

        SELECT COUNT(*) INTO v_has_updated_by
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = v_table
          AND column_name = 'updated_by';

        IF v_has_updated_ts = 0 THEN
            SET v_sql = CONCAT(
                'ALTER TABLE `', REPLACE(v_table, '`', '``'),
                '` ADD COLUMN `updated_ts` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ',
                IF(
                    v_has_updated_by > 0,
                    'AFTER `updated_by`',
                    IF(v_anchor IS NULL, 'FIRST', CONCAT('AFTER `', REPLACE(v_anchor, '`', '``'), '`'))
                )
            );
            SET @add_updated_ts_sql = v_sql;
            PREPARE stmt_add_updated_ts FROM @add_updated_ts_sql;
            EXECUTE stmt_add_updated_ts;
            DEALLOCATE PREPARE stmt_add_updated_ts;
        END IF;

        SELECT CONCAT(
                   '`updated_by` ',
                   COLUMN_TYPE,
                   ' ',
                   IF(IS_NULLABLE = 'NO', 'NOT NULL', 'NULL'),
                   CASE
                       WHEN COLUMN_DEFAULT IS NULL AND IS_NULLABLE = 'YES' THEN ' DEFAULT NULL'
                       WHEN COLUMN_DEFAULT IS NULL THEN ''
                       WHEN UPPER(COLUMN_DEFAULT) = 'CURRENT_TIMESTAMP' THEN ' DEFAULT CURRENT_TIMESTAMP'
                       ELSE CONCAT(' DEFAULT ', QUOTE(COLUMN_DEFAULT))
                   END,
                   CASE
                       WHEN EXTRA IS NULL OR TRIM(REPLACE(EXTRA, 'DEFAULT_GENERATED', '')) = '' THEN ''
                       ELSE CONCAT(' ', TRIM(REPLACE(EXTRA, 'DEFAULT_GENERATED', '')))
                   END
               )
          INTO v_updated_by_def
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = v_table
          AND column_name = 'updated_by'
        LIMIT 1;

        SELECT CONCAT(
                   '`updated_ts` ',
                   COLUMN_TYPE,
                   ' ',
                   IF(IS_NULLABLE = 'NO', 'NOT NULL', 'NULL'),
                   CASE
                       WHEN COLUMN_DEFAULT IS NULL AND IS_NULLABLE = 'YES' THEN ' DEFAULT NULL'
                       WHEN COLUMN_DEFAULT IS NULL THEN ''
                       WHEN UPPER(COLUMN_DEFAULT) = 'CURRENT_TIMESTAMP' THEN ' DEFAULT CURRENT_TIMESTAMP'
                       ELSE CONCAT(' DEFAULT ', QUOTE(COLUMN_DEFAULT))
                   END,
                   CASE
                       WHEN EXTRA IS NULL OR TRIM(REPLACE(EXTRA, 'DEFAULT_GENERATED', '')) = '' THEN ''
                       ELSE CONCAT(' ', TRIM(REPLACE(EXTRA, 'DEFAULT_GENERATED', '')))
                   END
               )
          INTO v_updated_ts_def
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = v_table
          AND column_name = 'updated_ts'
        LIMIT 1;

        SELECT c.column_name
          INTO v_anchor
        FROM information_schema.columns c
        WHERE c.table_schema = DATABASE()
          AND c.table_name = v_table
          AND c.column_name NOT IN ('updated_by', 'updated_ts')
        ORDER BY c.ordinal_position DESC
        LIMIT 1;

        SET v_parts = CONCAT(
            'MODIFY COLUMN ',
            v_updated_by_def,
            IF(v_anchor IS NULL, ' FIRST', CONCAT(' AFTER `', REPLACE(v_anchor, '`', '``'), '`')),
            ', MODIFY COLUMN ',
            v_updated_ts_def,
            ' AFTER `updated_by`'
        );

        SET v_sql = CONCAT('ALTER TABLE `', REPLACE(v_table, '`', '``'), '` ', v_parts);
        SET @reorder_sql = v_sql;
        PREPARE stmt_reorder FROM @reorder_sql;
        EXECUTE stmt_reorder;
        DEALLOCATE PREPARE stmt_reorder;
    END LOOP;

    CLOSE cur;
END $$

CALL enforce_updated_columns_standard() $$
DROP PROCEDURE enforce_updated_columns_standard $$

DELIMITER ;