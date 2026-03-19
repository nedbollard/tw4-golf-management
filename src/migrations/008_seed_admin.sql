-- Seed Admin User Migration
-- Insert default admin user for initial system setup

INSERT INTO staff (username, password_hash, first_name, last_name, role, is_active) VALUES 
('admin', '$2y$10$vQa.GD0JiNf2.AnvgJ/oHuyi/DloM93cv1MRB16aDqgepOl.XNpui', 'System', 'Administrator', 'admin', TRUE);
