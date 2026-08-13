#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
cd "${REPO_ROOT}"

TARGET_CHARSET="${TARGET_CHARSET:-utf8mb4}"
TARGET_COLLATION="${TARGET_COLLATION:-utf8mb4_0900_ai_ci}"
APPLY=0

if [[ "${1:-}" == "--apply" ]]; then
  APPLY=1
elif [[ -n "${1:-}" ]]; then
  echo "Usage: $0 [--apply]"
  echo "  default: dry-run (show SQL only)"
  echo "  --apply: execute ALTER statements"
  exit 1
fi

if [[ -z "${DB_PASSWORD-}" ]]; then
  DB_PASSWORD="$(awk -F= '/^DB_PASSWORD=/{print substr($0, index($0,"=")+1); exit}' .env)"
fi
: "${DB_PASSWORD:?DB_PASSWORD is required}"

MYSQL_BASE=(docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root -N)

echo "[INFO] Target charset:   ${TARGET_CHARSET}"
echo "[INFO] Target collation: ${TARGET_COLLATION}"
if [[ "$APPLY" -eq 1 ]]; then
  echo "[INFO] Mode: APPLY"
else
  echo "[INFO] Mode: DRY-RUN"
fi

SCHEMAS="$(${MYSQL_BASE[@]} -e "
SELECT SCHEMA_NAME
FROM information_schema.SCHEMATA
WHERE SCHEMA_NAME IN ('TW4_base','TW4_live','TW4_history','TW4_holding','tw4_test')
ORDER BY SCHEMA_NAME;" < /dev/null)"

if [[ -z "$SCHEMAS" ]]; then
  echo "[ERROR] No TW4 schemas found."
  exit 1
fi

while IFS= read -r SCHEMA; do
  [[ -z "$SCHEMA" ]] && continue

  echo
  echo "[INFO] Schema: ${SCHEMA}"

  DB_SQL="ALTER DATABASE \`${SCHEMA}\` CHARACTER SET ${TARGET_CHARSET} COLLATE ${TARGET_COLLATION};"

  TABLE_SQL="$(${MYSQL_BASE[@]} -e "
SELECT CONCAT(
  'ALTER TABLE ', CHAR(96), TABLE_SCHEMA, CHAR(96), '.', CHAR(96), TABLE_NAME, CHAR(96),
  ' CONVERT TO CHARACTER SET ${TARGET_CHARSET} COLLATE ${TARGET_COLLATION};'
)
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = '${SCHEMA}'
  AND TABLE_TYPE = 'BASE TABLE'
  AND TABLE_COLLATION <> '${TARGET_COLLATION}'
ORDER BY TABLE_NAME;" < /dev/null)"

  if [[ "$APPLY" -eq 1 ]]; then
    echo "  [APPLY] ${DB_SQL}"
    ${MYSQL_BASE[@]} -e "$DB_SQL" < /dev/null

    if [[ -n "$TABLE_SQL" ]]; then
      while IFS= read -r STMT; do
        [[ -z "$STMT" ]] && continue
        echo "  [APPLY] ${STMT}"
        ${MYSQL_BASE[@]} -e "$STMT" < /dev/null
      done <<< "$TABLE_SQL"
    else
      echo "  [OK] No table conversions required."
    fi
  else
    echo "  [DRY] ${DB_SQL}"
    if [[ -n "$TABLE_SQL" ]]; then
      while IFS= read -r STMT; do
        [[ -z "$STMT" ]] && continue
        echo "  [DRY] ${STMT}"
      done <<< "$TABLE_SQL"
    else
      echo "  [OK] No table conversions required."
    fi
  fi

  REMAINING_TABLES="$(${MYSQL_BASE[@]} -e "
SELECT COUNT(*)
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = '${SCHEMA}'
  AND TABLE_TYPE = 'BASE TABLE'
  AND TABLE_COLLATION <> '${TARGET_COLLATION}';" < /dev/null)"

  REMAINING_COLUMNS="$(${MYSQL_BASE[@]} -e "
SELECT COUNT(*)
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = '${SCHEMA}'
  AND DATA_TYPE IN ('char','varchar','text','tinytext','mediumtext','longtext','enum','set')
  AND COLLATION_NAME <> '${TARGET_COLLATION}';" < /dev/null)"

  echo "  [CHECK] Remaining non-standard tables:  ${REMAINING_TABLES}"
  echo "  [CHECK] Remaining non-standard columns: ${REMAINING_COLUMNS}"
done <<< "$SCHEMAS"

echo
if [[ "$APPLY" -eq 1 ]]; then
  echo "[DONE] Collation normalization applied."
else
  echo "[DONE] Dry-run complete. Re-run with --apply to execute."
fi
