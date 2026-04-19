-- Migration 023: Add temporary scored status for roster players.
-- Scored players are hidden from the card-entry dropdown until finish-round resets them.

ALTER TABLE roster
    MODIFY COLUMN status ENUM('active', 'scored', 'inactive') NOT NULL DEFAULT 'active';
