-- Add audit columns to config_application table
ALTER TABLE config_application 
ADD COLUMN updated_by VARCHAR(100) AFTER config_value_int,
ADD COLUMN updated_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER updated_by;
