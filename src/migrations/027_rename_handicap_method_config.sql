-- Rename legacy handicap config keys to handicap_method and normalize values.
-- Supported values are: modern, legacy, none.

START TRANSACTION;

INSERT INTO config_application (
    config_name,
    config_value_string,
    config_value_int,
    config_type,
    updated_by
)
SELECT
    'handicap_method',
    CASE
        WHEN LOWER(TRIM(COALESCE(c.config_value_string, ''))) IN ('m', 'modern') THEN 'modern'
        WHEN LOWER(TRIM(COALESCE(c.config_value_string, ''))) IN ('l', 'legacy') THEN 'legacy'
        WHEN LOWER(TRIM(COALESCE(c.config_value_string, ''))) IN ('n', 'none') THEN 'none'
        ELSE 'modern'
    END,
    NULL,
    'string',
    COALESCE(c.updated_by, 'system')
FROM config_application c
WHERE c.config_name IN ('handicap_sytem', 'handicap_system')
  AND NOT EXISTS (
      SELECT 1
      FROM config_application
      WHERE config_name = 'handicap_method'
  )
ORDER BY c.row_id
LIMIT 1;

INSERT INTO config_application (
    config_name,
    config_value_string,
    config_value_int,
    config_type,
    updated_by
)
SELECT
    'handicap_method',
    'modern',
    NULL,
    'string',
    'system'
WHERE NOT EXISTS (
    SELECT 1
    FROM config_application
    WHERE config_name = 'handicap_method'
);

UPDATE config_application
SET
    config_value_string = CASE
        WHEN LOWER(TRIM(COALESCE(config_value_string, ''))) IN ('m', 'modern') THEN 'modern'
        WHEN LOWER(TRIM(COALESCE(config_value_string, ''))) IN ('l', 'legacy') THEN 'legacy'
        WHEN LOWER(TRIM(COALESCE(config_value_string, ''))) IN ('n', 'none') THEN 'none'
        ELSE 'modern'
    END,
    config_type = 'string',
    config_value_int = NULL
WHERE config_name = 'handicap_method';

DELETE FROM config_application
WHERE config_name IN ('handicap_sytem', 'handicap_system');

COMMIT;
