-- Migration: Fix updated_by column type from INT to VARCHAR(50)
-- Date: 2026-04-13
-- Description: Change updated_by to store staff username directly

-- Drop foreign key constraint first
ALTER TABLE roster DROP FOREIGN KEY fk_roster_updated_by;

-- Drop index
ALTER TABLE roster DROP INDEX idx_roster_updated_by;

-- Change column type from INT to VARCHAR(50)
ALTER TABLE roster MODIFY COLUMN updated_by VARCHAR(50) NULL;

-- Update existing records to have NULL for updated_by
UPDATE roster SET updated_by = NULL WHERE updated_by IS NOT NULL;
