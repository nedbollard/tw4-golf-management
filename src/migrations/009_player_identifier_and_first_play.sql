-- Rename member_identifier to player_identifier and add first_play_date
-- Author: Ned Bollard
-- Description: Update roster table for better golf context

-- Rename member_identifier column to player_identifier
ALTER TABLE roster CHANGE COLUMN member_identifier player_identifier VARCHAR(50) NOT NULL;

-- Add first_play_date field to track when player first plays a round
ALTER TABLE roster ADD COLUMN first_play_date DATE NULL COMMENT 'Date of first played round';

-- Update indexes for the renamed column
DROP INDEX idx_member_identifier ON roster;
CREATE INDEX idx_player_identifier ON roster (player_identifier);

-- Update unique constraint for the renamed column
ALTER TABLE roster DROP INDEX member_identifier;
ALTER TABLE roster ADD UNIQUE KEY uk_player_identifier (player_identifier);

-- Add comment to clarify the purpose of player_identifier
ALTER TABLE roster MODIFY COLUMN player_identifier VARCHAR(50) NOT NULL COMMENT 'Unique player identifier (e.g., JohnD)';
