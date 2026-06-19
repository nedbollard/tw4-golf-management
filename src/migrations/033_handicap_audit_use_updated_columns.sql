-- Migration 033: Align handicap_audit audit metadata to app convention (updated_*).
-- Copies data from changed_* into updated_* where needed, then drops changed_* columns.
-- Safe to run multiple times.

DELIMITER $$

DROP PROCEDURE IF EXISTS migrate_handicap_audit_use_updated_columns $$
CREATE PROCEDURE migrate_handicap_audit_use_updated_columns()
BEGIN
    DECLARE v_has_changed_by INT DEFAULT 0;
    DECLARE v_has_changed_at INT DEFAULT 0;
    DECLARE v_has_updated_by INT DEFAULT 0;
    DECLARE v_has_updated_ts INT DEFAULT 0;
    DECLARE v_has_idx_changed_at INT DEFAULT 0;
    DECLARE v_has_idx_updated_ts INT DEFAULT 0;

    SELECT COUNT(*) INTO v_has_changed_by
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'handicap_audit'
      AND column_name = 'changed_by';

    SELECT COUNT(*) INTO v_has_changed_at
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'handicap_audit'
      AND column_name = 'changed_at';

    SELECT COUNT(*) INTO v_has_updated_by
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'handicap_audit'
      AND column_name = 'updated_by';

    SELECT COUNT(*) INTO v_has_updated_ts
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'handicap_audit'
      AND column_name = 'updated_ts';

    IF v_has_changed_by = 1 AND v_has_updated_by = 1 THEN
        UPDATE handicap_audit
        SET updated_by = changed_by
        WHERE (updated_by IS NULL OR TRIM(updated_by) = '')
          AND changed_by IS NOT NULL
          AND TRIM(changed_by) <> '';
    END IF;

    IF v_has_changed_at = 1 AND v_has_updated_ts = 1 THEN
        UPDATE handicap_audit
        SET updated_ts = changed_at
        WHERE changed_at IS NOT NULL;
    END IF;

    SELECT COUNT(*) INTO v_has_idx_changed_at
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'handicap_audit'
      AND index_name = 'idx_changed_at';

    IF v_has_idx_changed_at > 0 THEN
        ALTER TABLE handicap_audit DROP INDEX idx_changed_at;
    END IF;

    IF v_has_changed_by = 1 THEN
        ALTER TABLE handicap_audit DROP COLUMN changed_by;
    END IF;

    IF v_has_changed_at = 1 THEN
        ALTER TABLE handicap_audit DROP COLUMN changed_at;
    END IF;

    SELECT COUNT(*) INTO v_has_idx_updated_ts
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'handicap_audit'
      AND index_name = 'idx_updated_ts';

    IF v_has_idx_updated_ts = 0 THEN
        CREATE INDEX idx_updated_ts ON handicap_audit (updated_ts);
    END IF;
END $$

CALL migrate_handicap_audit_use_updated_columns() $$
DROP PROCEDURE IF EXISTS migrate_handicap_audit_use_updated_columns $$

DELIMITER ;
