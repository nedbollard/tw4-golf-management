-- Migration: Add updated_by column to roster table
-- Date: 2026-04-12
-- Description: Add tracking field for which staff member updated roster entries

ALTER TABLE roster 
ADD COLUMN updated_by INT NULL AFTER handicap;

-- Add foreign key constraint to reference staff table
ALTER TABLE roster 
ADD CONSTRAINT fk_roster_updated_by 
FOREIGN KEY (updated_by) REFERENCES staff(row_id) 
ON DELETE SET NULL;

-- Add index for performance
CREATE INDEX idx_roster_updated_by ON roster(updated_by);

-- Update existing records to have NULL for updated_by
-- (New records will be populated by application logic)
