-- Migration 020: Seed club number configuration for default round-start course selection.

INSERT INTO config_application (
    config_name,
    config_value_string,
    config_value_int,
    config_type,
    updated_by
)
SELECT
    'club_number',
    '294',
    294,
    'int',
    'system'
WHERE NOT EXISTS (
    SELECT 1 FROM config_application WHERE config_name = 'club_number'
);