-- Add is_active column for logical deletion
ALTER TABLE staff ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER user_role;

-- Update existing records to active
UPDATE staff SET is_active = TRUE;
