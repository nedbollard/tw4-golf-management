#!/usr/bin/env bash
set -euo pipefail
umask 077

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
cd "$REPO_ROOT"

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose-development.yml}"
SCHEMA_PREFIX=""
APPLY=0
MAINTENANCE=0
TARGET_CHARSET="utf8mb4"
TARGET_COLLATION="utf8mb4_0900_ai_ci"

usage() {
  echo "Usage: $0 [--compose-file FILE] [--schema-prefix PREFIX] [--apply --maintenance-window]"
  echo "Default: read-only preflight and SQL plan for the four application schemas."
  echo "--apply: back up, convert and verify; stop all application writers first."
  echo "--maintenance-window: acknowledge writers are stopped and DDL is not transactional."
  echo "--schema-prefix: target isolated rehearsal copies rather than application schemas."
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --apply) APPLY=1; shift ;;
    --maintenance-window) MAINTENANCE=1; shift ;;
    --compose-file|--schema-prefix)
      [[ $# -ge 2 && -n "$2" ]] || { usage >&2; exit 1; }
      if [[ "$1" == "--compose-file" ]]; then COMPOSE_FILE="$2"; else SCHEMA_PREFIX="$2"; fi
      shift 2
      ;;
    --help) usage; exit 0 ;;
    *) usage >&2; exit 1 ;;
  esac
done

fail() { echo "[ERROR] $*" >&2; exit 1; }
[[ -f "$COMPOSE_FILE" ]] || fail "Compose file not found: $COMPOSE_FILE"
[[ -z "$SCHEMA_PREFIX" || "$SCHEMA_PREFIX" =~ ^[a-zA-Z0-9_]+$ ]] || fail "Invalid schema prefix."
[[ ${#SCHEMA_PREFIX} -le 40 ]] || fail "Schema prefix is too long."
[[ "$APPLY" -eq 0 || "$MAINTENANCE" -eq 1 ]] || fail "--apply requires --maintenance-window."

if [[ -z "${DB_PASSWORD-}" && -f .env ]]; then
  DB_PASSWORD="$(awk -F= '/^DB_PASSWORD=/{print substr($0, index($0,"=")+1); exit}' .env)"
fi
: "${DB_PASSWORD:?DB_PASSWORD is required}"

DB_NAMES=("${SCHEMA_PREFIX}TW4_base" "${SCHEMA_PREFIX}TW4_live" "${SCHEMA_PREFIX}TW4_history" "${SCHEMA_PREFIX}TW4_holding")
SCHEMA_LIST="$(printf "'%s'," "${DB_NAMES[@]}")"
SCHEMA_LIST="${SCHEMA_LIST%,}"
MYSQL=(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql
  --default-character-set=utf8mb4 -u root --batch --skip-column-names)
DUMP=(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysqldump
  -u root --default-character-set=utf8mb4 --single-transaction --set-gtid-purged=OFF)
sql() { "${MYSQL[@]}" -e "$1" < /dev/null; }

echo "[INFO] Compose: $COMPOSE_FILE"
echo "[INFO] Schemas: ${DB_NAMES[*]}"
echo "[INFO] Target: $TARGET_CHARSET / $TARGET_COLLATION"
VERSION="$(sql 'SELECT VERSION();')"
[[ "$VERSION" =~ ^8\. && "$VERSION" != *MariaDB* ]] || fail "MySQL 8 required; found $VERSION."
[[ "$(sql "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME IN ($SCHEMA_LIST);")" == 4 ]] ||
  fail "All four target schemas must exist."

# Converting a text foreign key requires a coordinated constraint migration.
TEXT_FKS="$(sql "
SELECT CONCAT(k.TABLE_SCHEMA, '.', k.TABLE_NAME, '.', k.CONSTRAINT_NAME)
FROM information_schema.KEY_COLUMN_USAGE k
JOIN information_schema.COLUMNS c
  ON c.TABLE_SCHEMA = k.TABLE_SCHEMA AND c.TABLE_NAME = k.TABLE_NAME AND c.COLUMN_NAME = k.COLUMN_NAME
JOIN information_schema.COLUMNS rc
  ON rc.TABLE_SCHEMA = k.REFERENCED_TABLE_SCHEMA AND rc.TABLE_NAME = k.REFERENCED_TABLE_NAME
 AND rc.COLUMN_NAME = k.REFERENCED_COLUMN_NAME
WHERE (k.TABLE_SCHEMA IN ($SCHEMA_LIST) OR k.REFERENCED_TABLE_SCHEMA IN ($SCHEMA_LIST))
  AND (c.COLLATION_NAME IS NOT NULL OR rc.COLLATION_NAME IS NOT NULL)
  AND (c.COLLATION_NAME <> '$TARGET_COLLATION' OR rc.COLLATION_NAME <> '$TARGET_COLLATION'
       OR c.CHARACTER_SET_NAME <> '$TARGET_CHARSET' OR rc.CHARACTER_SET_NAME <> '$TARGET_CHARSET');")"
[[ -z "$TEXT_FKS" ]] || fail "Text foreign keys need a reviewed migration before conversion: $TEXT_FKS"

UNSUPPORTED="$(sql "
SELECT CONCAT(t.TABLE_SCHEMA, '.', t.TABLE_NAME)
FROM information_schema.TABLES t
WHERE t.TABLE_SCHEMA IN ($SCHEMA_LIST) AND t.TABLE_TYPE = 'BASE TABLE'
  AND t.ENGINE <> 'InnoDB';")"
[[ -z "$UNSUPPORTED" ]] || fail "Transactional backup requires InnoDB tables: $UNSUPPORTED"

FUNCTIONAL_INDEXES="$(sql "
SELECT CONCAT(s.TABLE_SCHEMA, '.', s.TABLE_NAME, '.', s.INDEX_NAME)
FROM information_schema.STATISTICS s
WHERE s.TABLE_SCHEMA IN ($SCHEMA_LIST) AND s.NON_UNIQUE = 0 AND s.COLUMN_NAME IS NULL;")"
[[ -z "$FUNCTIONAL_INDEXES" ]] || fail "Functional unique indexes require manual review: $FUNCTIONAL_INDEXES"

# Check compound and prefix unique indexes under the new comparison rules.
UNIQUE_CHECKS="$(sql "
SET SESSION group_concat_max_len = 65536;
SELECT CONCAT('SELECT COUNT(*) FROM (SELECT 1 FROM \`', REPLACE(s.TABLE_SCHEMA, '\`', '\`\`'),
  '\`.\`', REPLACE(s.TABLE_NAME, '\`', '\`\`'), '\` WHERE ',
  GROUP_CONCAT(CONCAT('\`', REPLACE(s.COLUMN_NAME, '\`', '\`\`'), '\` IS NOT NULL')
    ORDER BY s.SEQ_IN_INDEX SEPARATOR ' AND '),
  ' GROUP BY ',
  GROUP_CONCAT(CONCAT(
    IF(c.COLLATION_NAME IS NOT NULL, 'CONVERT(', ''),
    IF(s.SUB_PART IS NOT NULL, 'LEFT(', ''), '\`', REPLACE(s.COLUMN_NAME, '\`', '\`\`'), '\`',
    IF(s.SUB_PART IS NOT NULL, CONCAT(', ', s.SUB_PART, ')'), ''),
    IF(c.COLLATION_NAME IS NOT NULL, ' USING utf8mb4) COLLATE utf8mb4_0900_ai_ci', ''))
    ORDER BY s.SEQ_IN_INDEX SEPARATOR ', '),
  ' HAVING COUNT(*) > 1) AS duplicate_keys;')
FROM information_schema.STATISTICS s
JOIN information_schema.COLUMNS c
  ON c.TABLE_SCHEMA = s.TABLE_SCHEMA AND c.TABLE_NAME = s.TABLE_NAME AND c.COLUMN_NAME = s.COLUMN_NAME
WHERE s.TABLE_SCHEMA IN ($SCHEMA_LIST) AND s.NON_UNIQUE = 0
GROUP BY s.TABLE_SCHEMA, s.TABLE_NAME, s.INDEX_NAME
HAVING SUM(c.COLLATION_NAME IS NOT NULL) > 0
ORDER BY s.TABLE_SCHEMA, s.TABLE_NAME, s.INDEX_NAME;")"
while IFS= read -r check; do
  [[ -z "$check" ]] && continue
  [[ "$(sql "$check")" == 0 ]] || fail "Unique-key collision under target collation: $check"
done <<< "$UNIQUE_CHECKS"

PLAN="$(sql "
SELECT CONCAT('ALTER DATABASE \`', REPLACE(SCHEMA_NAME, '\`', '\`\`'),
  '\` CHARACTER SET $TARGET_CHARSET COLLATE $TARGET_COLLATION;')
FROM information_schema.SCHEMATA
WHERE SCHEMA_NAME IN ($SCHEMA_LIST)
  AND (DEFAULT_CHARACTER_SET_NAME <> '$TARGET_CHARSET' OR DEFAULT_COLLATION_NAME <> '$TARGET_COLLATION')
ORDER BY SCHEMA_NAME;
SELECT CONCAT('ALTER TABLE \`', REPLACE(t.TABLE_SCHEMA, '\`', '\`\`'), '\`.\`',
  REPLACE(t.TABLE_NAME, '\`', '\`\`'), '\` CONVERT TO CHARACTER SET $TARGET_CHARSET COLLATE $TARGET_COLLATION;')
FROM information_schema.TABLES t
WHERE t.TABLE_SCHEMA IN ($SCHEMA_LIST) AND t.TABLE_TYPE = 'BASE TABLE'
  AND (t.TABLE_COLLATION <> '$TARGET_COLLATION' OR EXISTS (
    SELECT 1 FROM information_schema.COLUMNS c
    WHERE c.TABLE_SCHEMA = t.TABLE_SCHEMA AND c.TABLE_NAME = t.TABLE_NAME
      AND c.COLLATION_NAME IS NOT NULL
      AND (c.COLLATION_NAME <> '$TARGET_COLLATION' OR c.CHARACTER_SET_NAME <> '$TARGET_CHARSET')))
ORDER BY t.TABLE_SCHEMA, t.TABLE_NAME;")"

echo "[OK] Preflight: MySQL 8, four schemas, InnoDB, foreign keys and unique keys checked."
if [[ -z "$PLAN" ]]; then
  echo "[OK] All database defaults, table defaults and text columns already match; no changes."
  exit 0
fi
printf '%s\n' "$PLAN"
if [[ "$APPLY" -eq 0 ]]; then
  echo "[DONE] Read-only dry-run. Stop writers before --apply --maintenance-window."
  exit 0
fi

mkdir -p backup
BACKUP_DIR="$(mktemp -d "backup/collation_$(date +%Y%m%d_%H%M%S)_XXXXXX")"
printf '%s\n' "$PLAN" > "$BACKUP_DIR/plan.sql"
echo "[INFO] Taking full backup: $BACKUP_DIR/before.sql.gz"
"${DUMP[@]}" --routines --triggers --events --hex-blob --databases "${DB_NAMES[@]}" |
  gzip -c > "$BACKUP_DIR/before.sql.gz"
gzip -t "$BACKUP_DIR/before.sql.gz"
(cd "$BACKUP_DIR" && sha256sum before.sql.gz > before.sql.gz.sha256)

data_hash() {
  {
    for schema in "${DB_NAMES[@]}"; do
      printf '%s\n' "$schema"
      # Byte-sort individual INSERTs so changed text-index ordering is not a data change.
      "${DUMP[@]}" --no-create-info --skip-triggers --skip-comments --compact --hex-blob \
        --skip-extended-insert --skip-add-locks --skip-disable-keys "$schema" |
        LC_ALL=C sort || return
    done
  } | sha256sum | awk '{print $1}'
}
BEFORE_HASH="$(data_hash)"
printf '%s\n' "$BEFORE_HASH" > "$BACKUP_DIR/data-before.sha256"
echo "[INFO] Applying conversions (DDL auto-commits; backup is the rollback path)."
sql "SET SESSION lock_wait_timeout = 30; $PLAN"

REMAINING="$(sql "
SELECT
 (SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME IN ($SCHEMA_LIST)
    AND (DEFAULT_COLLATION_NAME <> '$TARGET_COLLATION' OR DEFAULT_CHARACTER_SET_NAME <> '$TARGET_CHARSET')) +
 (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA IN ($SCHEMA_LIST)
    AND TABLE_TYPE = 'BASE TABLE' AND TABLE_COLLATION <> '$TARGET_COLLATION') +
 (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA IN ($SCHEMA_LIST)
    AND COLLATION_NAME IS NOT NULL
    AND (COLLATION_NAME <> '$TARGET_COLLATION' OR CHARACTER_SET_NAME <> '$TARGET_CHARSET'));")"
[[ "$REMAINING" == 0 ]] || fail "Conversion incomplete; inspect $BACKUP_DIR before restarting writers."
AFTER_HASH="$(data_hash)"
printf '%s\n' "$AFTER_HASH" > "$BACKUP_DIR/data-after.sha256"
[[ "$BEFORE_HASH" == "$AFTER_HASH" ]] || fail "Data dump changed; keep writers stopped and inspect $BACKUP_DIR."
echo "[DONE] All target collations verified; data dump is byte-for-byte unchanged."
echo "[INFO] Backup, plan and data hashes: $BACKUP_DIR"
