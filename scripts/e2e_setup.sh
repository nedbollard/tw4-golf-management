#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "[1/7] Starting containers..."
docker compose up -d

echo "[2/7] Loading DB password from .env..."
if [[ ! -f .env ]]; then
  echo "Error: .env not found in $ROOT_DIR" >&2
  exit 1
fi

DB_PASSWORD="$(awk -F= '/^DB_PASSWORD=/{print substr($0,index($0,"=")+1); exit}' .env)"
if [[ -z "$DB_PASSWORD" ]]; then
  echo "Error: DB_PASSWORD not found or empty in .env" >&2
  exit 1
fi

echo "[3/7] Recreating databases TW4_base and TW4_live..."
docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root -e "DROP DATABASE IF EXISTS TW4_base; DROP DATABASE IF EXISTS TW4_live; CREATE DATABASE TW4_base; CREATE DATABASE TW4_live;"

echo "[4/7] Applying TW4_base migrations (excluding live-only + snapshot)..."
for m in $(ls src/migrations/*.sql | grep -v '017_create_live_database_schema.sql' | grep -v '018_seed_live_round.sql' | grep -v '019_round_workflow_and_lock.sql' | grep -v '021_live_round_start_defaults.sql' | grep -v '022_live_card_tables.sql' | grep -v '024_live_results_table.sql' | grep -v '025_round_season_year.sql' | grep -v '026_card_handicap_audit.sql' | grep -v '999_current_schema.sql' | sort); do
  docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root TW4_base < "$m"
done

echo "[5/7] Creating TW4_live.round table (if missing)..."
docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root -e "USE TW4_live; CREATE TABLE IF NOT EXISTS round ( row_id int NOT NULL AUTO_INCREMENT, season_year char(5) DEFAULT NULL, number_round int NOT NULL, round_date date DEFAULT NULL, course_played_id int DEFAULT NULL, workflow_step enum('not_started','card_entry_open','results_presented','finished','cancelled') NOT NULL DEFAULT 'not_started', card_count int NOT NULL DEFAULT '0', results_presented_at timestamp NULL DEFAULT NULL, finished_at timestamp NULL DEFAULT NULL, locked_by_staff_id int DEFAULT NULL, lock_acquired_at timestamp NULL DEFAULT NULL, lock_expires_at timestamp NULL DEFAULT NULL, lock_released_by_staff_id int DEFAULT NULL, lock_released_at timestamp NULL DEFAULT NULL, lock_release_reason enum('logout','session_expired','admin_forced','finished') DEFAULT NULL, updated_by varchar(100) DEFAULT NULL, updated_ts timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (row_id), UNIQUE KEY uk_round_season_number (season_year,number_round), KEY idx_round_workflow_step (workflow_step), KEY idx_round_locked_by_staff_id (locked_by_staff_id), KEY idx_round_lock_expires_at (lock_expires_at), KEY idx_round_course_played_id (course_played_id) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;"

echo "[6/7] Applying TW4_live migrations..."
for m in src/migrations/018_seed_live_round.sql src/migrations/019_round_workflow_and_lock.sql src/migrations/021_live_round_start_defaults.sql src/migrations/022_live_card_tables.sql src/migrations/024_live_results_table.sql src/migrations/025_round_season_year.sql src/migrations/026_card_handicap_audit.sql; do
  docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -u root TW4_live < "$m"
done

echo "[7/7] Verifying seeded admin exists..."
ADMIN_ROW="$(docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mysql -N -s -u root -e "SELECT username, role, is_active FROM TW4_base.staff WHERE username='admin';")"
echo "$ADMIN_ROW"

if [[ "$ADMIN_ROW" == $'admin\tadmin\t1' ]]; then
  echo "Success: seeded admin check passed."
else
  echo "Warning: seeded admin check did not match expected 'admin\tadmin\t1'." >&2
  exit 1
fi

echo "E2E setup complete."
