-- Add staff_id column for login purposes
ALTER TABLE staff ADD COLUMN staff_id VARCHAR(16) UNIQUE AFTER row_id;

-- Update staff_id with row_id values for existing records
UPDATE staff SET staff_id = CONCAT('STAFF', LPAD(row_id, 3, '0')) WHERE staff_id IS NULL;
