-- Migration 037: Introduce positive idle workflow state "between_rounds"
-- and migrate legacy "not_started" rows.
--
-- Run against TW4_live database
-- Example:
--   docker compose exec -e MYSQL_PWD=${DB_PASSWORD} db mysql -u root TW4_live < src/migrations/037_between_rounds_workflow_state.sql

USE TW4_live;

DELIMITER $$

CREATE PROCEDURE migrate_round_037()
BEGIN
    -- Keep legacy value available for compatibility while moving the canonical
    -- idle state to a positive label.
    ALTER TABLE round
        MODIFY COLUMN workflow_step ENUM(
            'between_rounds',
            'not_started',
            'card_entry_open',
            'results_presented',
            'finished',
            'cancelled'
        ) NOT NULL DEFAULT 'between_rounds';

    UPDATE round
       SET workflow_step = 'between_rounds'
     WHERE workflow_step = 'not_started';
END $$

CALL migrate_round_037() $$
DROP PROCEDURE migrate_round_037 $$

DELIMITER ;
