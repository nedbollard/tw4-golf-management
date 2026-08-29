-- Seed Admin User Migration
-- Insert default admin user for initial system setup.
--
-- SECURITY: the hash below is published in this repository, so this account is
-- an installation-time credential only. Immediately after installation, create a
-- real admin account and deactivate this one, or rotate its password with
-- `php scripts/set-staff-password.php admin`. See README "Initial admin account".

INSERT INTO staff (username, password_hash, first_name, last_name, role, is_active) VALUES 
('admin', '$2y$10$vQa.GD0JiNf2.AnvgJ/oHuyi/DloM93cv1MRB16aDqgepOl.XNpui', 'System', 'Administrator', 'admin', TRUE);
