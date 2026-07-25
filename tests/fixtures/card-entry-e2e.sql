USE TW4_base;

INSERT INTO config_application
    (config_name, config_value_string, config_type, updated_by)
VALUES
    ('config_status', 'ready', 'string', 'e2e'),
    ('club_name', 'E2E Golf Club', 'string', 'e2e'),
    ('competition_name', 'Card Entry Test', 'string', 'e2e'),
    ('season_year', '26_27', 'string', 'e2e'),
    ('club_number', '294', 'int', 'e2e');

INSERT INTO staff
    (row_id, username, password_hash, first_name, last_name, role, is_active, updated_by)
VALUES
    (1, 'scorer', '$2y$10$vQa.GD0JiNf2.AnvgJ/oHuyi/DloM93cv1MRB16aDqgepOl.XNpui', 'E2E', 'Scorer', 'scorer', 1, 'e2e'),
    (2, 'admin', '$2y$10$vQa.GD0JiNf2.AnvgJ/oHuyi/DloM93cv1MRB16aDqgepOl.XNpui', 'E2E', 'Administrator', 'admin', 1, 'e2e');

INSERT INTO roster
    (row_id, player_identifier, first_name, last_name, alias, gender, status, handicap, updated_by)
VALUES
    (1, 'E2EPlayer', 'Card', 'Player', 'E2E Player', 'male', 'active', 9, 'e2e');

INSERT INTO course_played
    (row_id, name_course, name_club, ident_eclectic, updated_by)
VALUES
    (1, 'Test Nine', 'E2E Club', 'test-nine', 'e2e');

INSERT INTO course_club
    (name_club, gender, number_hole, name_hole, par, stroke, updated_by)
VALUES
    ('E2E Club', 'M', 1, 'Hole 1', 4, 1, 'e2e'),
    ('E2E Club', 'M', 2, 'Hole 2', 4, 2, 'e2e'),
    ('E2E Club', 'M', 3, 'Hole 3', 4, 3, 'e2e'),
    ('E2E Club', 'M', 4, 'Hole 4', 4, 4, 'e2e'),
    ('E2E Club', 'M', 5, 'Hole 5', 4, 5, 'e2e'),
    ('E2E Club', 'M', 6, 'Hole 6', 4, 6, 'e2e'),
    ('E2E Club', 'M', 7, 'Hole 7', 4, 7, 'e2e'),
    ('E2E Club', 'M', 8, 'Hole 8', 4, 8, 'e2e'),
    ('E2E Club', 'M', 9, 'Hole 9', 4, 9, 'e2e');

INSERT INTO course_played_hole
    (course_played_id, number_hole_course, number_hole_played, updated_by)
VALUES
    (1, 1, 1, 'e2e'),
    (1, 2, 2, 'e2e'),
    (1, 3, 3, 'e2e'),
    (1, 4, 4, 'e2e'),
    (1, 5, 5, 'e2e'),
    (1, 6, 6, 'e2e'),
    (1, 7, 7, 'e2e'),
    (1, 8, 8, 'e2e'),
    (1, 9, 9, 'e2e');

USE TW4_live;

INSERT INTO round
    (row_id, season_year, number_round, round_date, course_played_id, workflow_step, card_count, updated_by)
VALUES
    (1, '26_27', 1, '2026-07-25', 1, 'card_entry_open', 0, 'e2e');
