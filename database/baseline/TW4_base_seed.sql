
USE TW4_base;

-- Controlled seed data for baseline provisioning.
-- Scope intentionally limited to config_application and staff.

DELETE FROM config_application;
INSERT INTO config_application
	(row_id, config_name, config_value_string, config_value_int, config_type, updated_by, updated_ts)
VALUES
	(1, 'team_haggle_state', 'F', 0, 'string', 'admin', '2026-05-07 08:17:03'),
	(2, 'club_number', '294', 294, 'int', 'admin', '2026-05-07 08:17:05'),
	(3, 'config_status', 'waiting', NULL, 'string', 'system', '2026-05-07 08:17:50'),
	(4, 'club_name', 'TW4 Golf Club', NULL, 'string', 'admin', '2026-05-07 08:17:50'),
	(5, 'competition_name', 'Twilight', NULL, 'string', 'admin', '2026-05-07 08:17:50'),
	(6, 'season_year', '25_26', NULL, 'string', 'admin', '2026-05-07 08:17:50'),
	(8, 'max_handicap', '54', 54, 'int', 'admin', '2026-05-07 08:17:50'),
	(9, 'entry_fee', '2', 2, 'int', 'admin', '2026-05-07 08:17:50'),
	(10, 'handicap_method', 'modern', NULL, 'string', 'admin', '2026-05-07 08:41:14');

DELETE FROM staff;
INSERT INTO staff
	(row_id, username, password_hash, first_name, last_name, role, is_active, created_at, last_login, updated_by, updated_ts)
VALUES
	(1, 'admin', '$2y$10$vQa.GD0JiNf2.AnvgJ/oHuyi/DloM93cv1MRB16aDqgepOl.XNpui', 'System', 'Administrator', 'admin', 1, '2026-05-07 08:17:03', NULL, NULL, '2026-05-07 08:17:03');

