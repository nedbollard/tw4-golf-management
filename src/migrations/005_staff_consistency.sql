-- Update staff table column names for consistency
-- Rename updated_at to updated_ts
ALTER TABLE staff CHANGE COLUMN updated_at updated_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add updated_by column
ALTER TABLE staff ADD COLUMN updated_by VARCHAR(100) AFTER updated_ts;
