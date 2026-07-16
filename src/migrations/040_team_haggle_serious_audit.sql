-- Migration 040: Serious team-haggle revision tracking and membership audit history.
-- Forward-only/idempotent migration.

USE TW4_base;

INSERT INTO config_application (
    config_name,
    config_value_string,
    config_value_int,
    config_type,
    updated_by
)
SELECT 'team_haggle_serious_revision', '0', 0, 'int', 'system'
WHERE NOT EXISTS (
    SELECT 1 FROM config_application WHERE config_name = 'team_haggle_serious_revision'
);

USE TW4_history;

CREATE TABLE IF NOT EXISTS best_five_team_member_audit (
    row_id INT NOT NULL AUTO_INCREMENT,
    season_year CHAR(5) NOT NULL,
    number_round INT NOT NULL,
    serious_revision INT NOT NULL DEFAULT 0,
    team_number INT NOT NULL,
    slot_number INT NOT NULL,
    action_type ENUM('assign','replace','remove','makeup','finish_refresh') NOT NULL,
    old_player_identifier VARCHAR(100) DEFAULT NULL,
    new_player_identifier VARCHAR(100) DEFAULT NULL,
    old_player_points INT NOT NULL DEFAULT 0,
    new_player_points INT NOT NULL DEFAULT 0,
    note VARCHAR(255) DEFAULT NULL,
    updated_by VARCHAR(100) NOT NULL,
    updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (row_id),
    KEY idx_best_five_team_member_audit_round (season_year, number_round, serious_revision),
    KEY idx_best_five_team_member_audit_team_slot (team_number, slot_number),
    KEY idx_best_five_team_member_audit_action (action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
