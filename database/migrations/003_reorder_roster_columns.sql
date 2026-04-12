-- Migration: Reorder roster columns for better aesthetics
-- Date: 2026-04-13
-- Description: Add date_first_played column and reorder columns as requested

-- Add new date_first_played column after handicap
ALTER TABLE roster ADD COLUMN date_first_played DATE NULL AFTER handicap;

-- Update existing records to have NULL for new column
UPDATE roster SET date_first_played = NULL WHERE date_first_played IS NOT NULL;

-- Reorder columns by modifying table structure
-- This will change the physical order of columns in SELECT * queries
-- MySQL doesn't support direct column reordering, but we can recreate table

-- Create temporary table with new column order
CREATE TABLE roster_new (
    row_id int AUTO_INCREMENT PRIMARY KEY,
    player_identifier varchar(50) NOT NULL UNIQUE,
    first_name varchar(100) NOT NULL,
    last_name varchar(100) NOT NULL,
    alias varchar(50) NULL UNIQUE,
    gender enum('male','female') NOT NULL,
    handicap int DEFAULT 0,
    date_first_played DATE NULL,
    status enum('active','inactive') NOT NULL DEFAULT 'active',
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_by varchar(50) NULL,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Copy data from old table to new table
INSERT INTO roster_new (row_id, player_identifier, first_name, last_name, alias, gender, handicap, status, created_at, updated_by, updated_at)
SELECT row_id, player_identifier, first_name, last_name, alias, gender, handicap, status, created_at, updated_by, updated_at FROM roster;

-- Drop old table
DROP TABLE roster;

-- Rename new table to roster
RENAME TABLE roster_new TO roster;
