-- Migration 018: Seed initial persistent live round row
-- Run against the TW4_live database.
--
-- Example:
--   docker compose exec -e MYSQL_PWD=${DB_PASSWORD} db mysql -u root TW4_live < src/migrations/018_seed_live_round.sql

USE TW4_live;

INSERT INTO round (
    number_round,
    updated_by
)
SELECT
    0,
    'system'
WHERE NOT EXISTS (
    SELECT 1 FROM round
);
