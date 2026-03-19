-- Staff Management Enhancement Migration
-- Rename name_user to staff_name for clarity
ALTER TABLE staff CHANGE COLUMN name_user staff_name VARCHAR(64);

-- Add is_active column for logical deletion
ALTER TABLE staff ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER user_role;

-- Update existing records to active
UPDATE staff SET is_active = TRUE;

-- Update all updated_by columns to use staff_id (as string)
UPDATE staff SET updated_by = CAST(row_id AS CHAR) WHERE updated_by IS NOT NULL;
