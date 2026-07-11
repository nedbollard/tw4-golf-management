-- Migration 039: Team Haggle floating-mode configuration and live tables.
-- Forward-only/idempotent migration.

USE TW4_base;

INSERT INTO config_application (
    config_name,
    config_value_string,
    config_value_int,
    config_type,
    updated_by
)
SELECT 'team_haggle_team_size', '4', 4, 'int', 'system'
WHERE NOT EXISTS (
    SELECT 1 FROM config_application WHERE config_name = 'team_haggle_team_size'
);

INSERT INTO config_application (
    config_name,
    config_value_string,
    config_type,
    updated_by
)
SELECT 'team_haggle_makeup_method', 'average', 'string', 'system'
WHERE NOT EXISTS (
    SELECT 1 FROM config_application WHERE config_name = 'team_haggle_makeup_method'
);

-- Canonicalize legacy state values to floating/serious.
UPDATE config_application
SET config_value_string = 'floating',
    updated_by = 'system'
WHERE config_name = 'team_haggle_state'
  AND LOWER(TRIM(COALESCE(config_value_string, ''))) IN ('f', 'floating', 'fun');

UPDATE config_application
SET config_value_string = 'serious',
    updated_by = 'system'
WHERE config_name = 'team_haggle_state'
  AND LOWER(TRIM(COALESCE(config_value_string, ''))) IN ('l', 's', 'serious', 'locked');

UPDATE config_application
SET config_value_string = 'floating',
    updated_by = 'system'
WHERE config_name = 'team_haggle_state'
  AND LOWER(TRIM(COALESCE(config_value_string, ''))) NOT IN ('floating', 'serious');

USE TW4_live;

CREATE TABLE IF NOT EXISTS best_five_team (
    row_id INT NOT NULL AUTO_INCREMENT,
    team_number INT NOT NULL,
    team_name VARCHAR(128) NOT NULL,
    team_points_total INT NOT NULL DEFAULT 0,
    updated_by VARCHAR(100) NOT NULL,
    updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (row_id),
    UNIQUE KEY uk_best_five_team_number (team_number),
    KEY idx_best_five_team_points (team_points_total)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS best_five_team_member (
    row_id INT NOT NULL AUTO_INCREMENT,
    team_number INT NOT NULL,
    player_identifier VARCHAR(100) NOT NULL,
    player_points_total INT NOT NULL DEFAULT 0,
    updated_by VARCHAR(100) NOT NULL,
    updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (row_id),
    KEY idx_best_five_team_member_team (team_number),
    KEY idx_best_five_team_member_player (player_identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
