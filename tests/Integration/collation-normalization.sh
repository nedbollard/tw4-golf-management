#!/usr/bin/env bash
set -euo pipefail

[[ $# -eq 2 && "$1" == "--compose-file" ]] || {
  echo "Usage: DB_PASSWORD=... bash $0 --compose-file /path/to/isolated-mysql-compose.yml" >&2
  exit 1
}
COMPOSE_FILE="$2"
: "${DB_PASSWORD:?DB_PASSWORD is required}"
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
PREFIX="collation_test_${BASHPID}_"
SCHEMAS=("${PREFIX}TW4_base" "${PREFIX}TW4_live" "${PREFIX}TW4_history" "${PREFIX}TW4_holding")
MYSQL=(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root -N -B)
sql() { "${MYSQL[@]}" -e "$1" < /dev/null; }
NORMALIZE=(bash "$ROOT/scripts/db/db_normalize_collations.sh" --compose-file "$COMPOSE_FILE" --schema-prefix "$PREFIX")

for schema in "${SCHEMAS[@]}"; do
  [[ "$(sql "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='$schema';")" == 0 ]] ||
    { echo "Fixture schema already exists: $schema" >&2; exit 1; }
done
cleanup() {
  for schema in "${SCHEMAS[@]}"; do sql "DROP DATABASE IF EXISTS \`$schema\`;"; done
}
trap cleanup EXIT
for schema in "${SCHEMAS[@]}"; do
  sql "CREATE DATABASE \`$schema\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
done
BASE="${SCHEMAS[0]}"
sql "
CREATE TABLE \`$BASE\`.ordinary (id INT PRIMARY KEY, label VARCHAR(20) UNIQUE)
  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO \`$BASE\`.ordinary VALUES (1,'first'),(2,'second');
CREATE TABLE \`$BASE\`.column_override (id INT PRIMARY KEY,
  label VARCHAR(20) COLLATE utf8mb4_general_ci)
  ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO \`$BASE\`.column_override VALUES (1,'preserved');"
sql "
CREATE TABLE \`$BASE\`.text_primary (label VARCHAR(20) PRIMARY KEY)
 ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO \`$BASE\`.text_primary VALUES ('sa'),(CONVERT(0xC39F USING utf8mb4)),('st');"

DRY="$("${NORMALIZE[@]}")"
[[ "$DRY" == *'column_override` CONVERT'* ]] || { echo "Missed column override" >&2; exit 1; }
[[ "$(sql "SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA='$BASE' AND TABLE_NAME='ordinary';")" == utf8mb4_general_ci ]]
if "${NORMALIZE[@]}" --apply > /dev/null 2>&1; then
  echo "Apply succeeded without maintenance acknowledgement" >&2; exit 1
fi

sql "
CREATE TABLE \`$BASE\`.collision (id INT PRIMARY KEY, label VARCHAR(20) UNIQUE)
 ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO \`$BASE\`.collision VALUES (1,CONVERT(0xC39F USING utf8mb4)),(2,'ss');"
if OUTPUT="$("${NORMALIZE[@]}" --apply --maintenance-window 2>&1)"; then
  echo "Collision was not rejected" >&2; exit 1
fi
[[ "$OUTPUT" == *'Unique-key collision'* ]]
[[ "$(sql "SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA='$BASE' AND TABLE_NAME='ordinary';")" == utf8mb4_general_ci ]]
sql "DROP TABLE \`$BASE\`.collision;"

sql "
CREATE TABLE \`$BASE\`.text_parent (label VARCHAR(20) PRIMARY KEY)
 ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE \`$BASE\`.text_child (label VARCHAR(20), FOREIGN KEY(label) REFERENCES \`$BASE\`.text_parent(label))
 ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
if OUTPUT="$("${NORMALIZE[@]}" --apply --maintenance-window 2>&1)"; then
  echo "Text foreign key was not rejected" >&2; exit 1
fi
[[ "$OUTPUT" == *'Text foreign keys'* ]]
sql "DROP TABLE \`$BASE\`.text_child; DROP TABLE \`$BASE\`.text_parent;"

"${NORMALIZE[@]}" --apply --maintenance-window
[[ "$(sql "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$BASE' AND COLLATION_NAME IS NOT NULL AND COLLATION_NAME <> 'utf8mb4_0900_ai_ci';")" == 0 ]]
[[ "$(sql "SELECT COUNT(*) FROM \`$BASE\`.ordinary;")" == 2 ]]
[[ "$(sql "SELECT label FROM \`$BASE\`.column_override;")" == preserved ]]
[[ "$(sql "SELECT COUNT(*) FROM \`$BASE\`.text_primary;")" == 3 ]]
AGAIN="$("${NORMALIZE[@]}" --apply --maintenance-window)"
[[ "$AGAIN" == *'already match; no changes'* ]]
echo "[OK] Dry-run, maintenance guard, column overrides, unique collisions, text foreign keys, text-index ordering, data preservation and idempotence."
