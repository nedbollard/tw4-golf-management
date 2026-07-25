#!/bin/bash

set -euo pipefail

# TW4 System Test Bootstrap (ground zero)
# Rebuilds TW4_base, TW4_live, TW4_history, and TW4_holding from canonical baseline schema
# files, then applies controlled seed data for config_application and staff.
#
# This script intentionally does NOT apply incremental migrations.
# Baseline files are treated as the new source of truth for a virgin environment.

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

PREFERRED_COMPOSE_FILE="docker-compose.systest.yml"
LEGACY_COMPOSE_FILE="docker-compose.prod.yml"

if [ -n "${COMPOSE_FILE-}" ]; then
    SELECTED_COMPOSE_FILE="$COMPOSE_FILE"
elif [ -f "$PREFERRED_COMPOSE_FILE" ]; then
    SELECTED_COMPOSE_FILE="$PREFERRED_COMPOSE_FILE"
elif [ -f "$LEGACY_COMPOSE_FILE" ]; then
    SELECTED_COMPOSE_FILE="$LEGACY_COMPOSE_FILE"
    print_warn "Using legacy compose file '$LEGACY_COMPOSE_FILE'. Rename path to '$PREFERRED_COMPOSE_FILE' when convenient."
else
    SELECTED_COMPOSE_FILE="$PREFERRED_COMPOSE_FILE"
fi

COMPOSE_FILE="$SELECTED_COMPOSE_FILE"

if [ ! -f "$COMPOSE_FILE" ]; then
    print_error "$PREFERRED_COMPOSE_FILE (or legacy $LEGACY_COMPOSE_FILE) not found. Run from the TW4 project root."
    exit 1
fi

print_status "Using compose file: $COMPOSE_FILE"

BASE_SCHEMA_FILE="database/baseline/TW4_base_schema.sql"
LIVE_SCHEMA_FILE="database/baseline/TW4_live_schema.sql"
HISTORY_SCHEMA_FILE="database/baseline/TW4_history_schema.sql"
HOLDING_SCHEMA_FILE="database/baseline/TW4_holding_schema.sql"
BASE_SEED_FILE="database/baseline/TW4_base_seed.sql"
POST_BOOTSTRAP_MIGRATIONS=(
    "src/migrations/036_eclectic_movement_only_and_ident_order.sql"
    "src/migrations/018_seed_live_round.sql"
    "src/migrations/037_between_rounds_workflow_state.sql"
    "src/migrations/038_eclectic_config_and_round_context.sql"
    "src/migrations/039_team_haggle_floating_setup.sql"
    "src/migrations/040_team_haggle_serious_audit.sql"
    "src/migrations/041_handicap_reference_tees.sql"
)

ensure_application_log_table() {
    print_status "Ensuring TW4_base.application_log exists..."
    docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
        mysql -u root TW4_base <<'SQL'
CREATE TABLE IF NOT EXISTS application_log (
    row_id INT NOT NULL AUTO_INCREMENT,
    timestamp DATETIME NOT NULL,
    level ENUM('DEBUG','INFO','WARNING','ERROR','CRITICAL') NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    context JSON DEFAULT NULL,
    username VARCHAR(100) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT,
    updated_by VARCHAR(100) DEFAULT NULL,
    updated_ts TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (row_id),
    KEY idx_timestamp (timestamp),
    KEY idx_level (level),
    KEY idx_event_type (event_type),
    KEY idx_username (username)
);
SQL
}

for required_file in "$BASE_SCHEMA_FILE" "$LIVE_SCHEMA_FILE" "$HISTORY_SCHEMA_FILE" "$HOLDING_SCHEMA_FILE" "$BASE_SEED_FILE"; do
    if [ ! -f "$required_file" ]; then
        print_error "Required file not found: $required_file"
        exit 1
    fi
done

for migration_file in "${POST_BOOTSTRAP_MIGRATIONS[@]}"; do
    if [ ! -f "$migration_file" ]; then
        print_error "Post-bootstrap migration not found: $migration_file"
        exit 1
    fi
done

if [ -z "${DB_PASSWORD-}" ] && [ -f ".env" ]; then
    DB_PASSWORD="$(awk -F= '/^DB_PASSWORD=/{print substr($0, index($0,"=")+1); exit}' .env)"
fi

: "${DB_PASSWORD:?DB_PASSWORD is required (set it in .env or export it)}"

print_status "Ensuring the database container is running..."
docker compose -f "$COMPOSE_FILE" up -d db

print_status "Waiting for MySQL readiness..."
until docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -u root -e "SELECT 1" >/dev/null 2>&1; do
    echo "Waiting for MySQL..."
done

print_status "Dropping and recreating TW4_base, TW4_live, TW4_history, and TW4_holding..."
docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -u root -e "DROP DATABASE IF EXISTS TW4_base; DROP DATABASE IF EXISTS TW4_live; DROP DATABASE IF EXISTS TW4_history; DROP DATABASE IF EXISTS TW4_holding; CREATE DATABASE TW4_base; CREATE DATABASE TW4_live; CREATE DATABASE TW4_history; CREATE DATABASE TW4_holding;"

print_status "Importing TW4_base schema..."
docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -u root < "$BASE_SCHEMA_FILE"

print_status "Importing TW4_live schema..."
docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -u root < "$LIVE_SCHEMA_FILE"

print_status "Importing TW4_history schema..."
docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -u root < "$HISTORY_SCHEMA_FILE"

print_status "Importing TW4_holding schema..."
docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -u root < "$HOLDING_SCHEMA_FILE"

print_status "Applying controlled TW4_base seed data..."
docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -u root TW4_base < "$BASE_SEED_FILE"

print_status "Applying post-bootstrap compatibility migrations..."
for migration_file in "${POST_BOOTSTRAP_MIGRATIONS[@]}"; do
    print_status "Applying ${migration_file}..."
    docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
        mysql -u root TW4_base < "$migration_file"
done

ensure_application_log_table

print_status "Ensuring TW4_live.round has a baseline between_rounds row..."
docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "INSERT INTO TW4_live.round (number_round, workflow_step, updated_by) SELECT 0, 'between_rounds', 'system' WHERE NOT EXISTS (SELECT 1 FROM TW4_live.round); UPDATE TW4_live.round SET workflow_step='between_rounds' WHERE workflow_step='not_started';"

print_status "Verifying required databases and tables..."
docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SHOW DATABASES LIKE 'TW4_base'; SHOW DATABASES LIKE 'TW4_live'; SHOW DATABASES LIKE 'TW4_history'; SHOW DATABASES LIKE 'TW4_holding'; SHOW TABLES IN TW4_base LIKE 'staff'; SHOW TABLES IN TW4_base LIKE 'config_application'; SHOW TABLES IN TW4_base LIKE 'application_log'; SHOW TABLES IN TW4_base LIKE 'handicap_audit'; SHOW TABLES IN TW4_live LIKE 'round'; SHOW TABLES IN TW4_live LIKE 'card'; SHOW TABLES IN TW4_live LIKE 'card_by_hole'; SHOW TABLES IN TW4_live LIKE 'results'; SHOW TABLES IN TW4_live LIKE 'best_five_scores'; SHOW TABLES IN TW4_history LIKE 'round'; SHOW TABLES IN TW4_history LIKE 'card'; SHOW TABLES IN TW4_history LIKE 'card_by_hole'; SHOW TABLES IN TW4_history LIKE 'results'; SHOW TABLES IN TW4_history LIKE 'best_five_scores'; SHOW TABLES IN TW4_history LIKE 'round_eclectic_context'; SHOW TABLES IN TW4_holding LIKE 'best_five_scores';"

ADMIN_ROW="$(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SELECT username, role, is_active FROM TW4_base.staff WHERE username='admin' LIMIT 1;")"

STAFF_COUNT="$(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SELECT COUNT(*) FROM TW4_base.staff;")"

CONFIG_STATUS="$(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SELECT config_value_string FROM TW4_base.config_application WHERE config_name='config_status' LIMIT 1;")"

ROUND_ECLECTIC_CONTEXT_TABLE="$(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SHOW TABLES IN TW4_history LIKE 'round_eclectic_context';")"

BETWEEN_ROUNDS_COUNT="$(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SELECT COUNT(*) FROM TW4_live.round WHERE workflow_step='between_rounds';")"

ROUND_WORKFLOW_ENUM_HAS_BETWEEN_ROUNDS="$(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SELECT CASE WHEN COLUMN_TYPE LIKE '%between_rounds%' THEN 1 ELSE 0 END FROM information_schema.columns WHERE table_schema='TW4_live' AND table_name='round' AND column_name='workflow_step' LIMIT 1;")"

APPLICATION_LOG_TABLE="$(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SHOW TABLES IN TW4_base LIKE 'application_log';")"

HANDICAP_POINTS_SCORED_COLUMN_COUNT="$(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='TW4_base' AND table_name='handicap_audit' AND column_name='points_scored';")"

HANDICAP_POINTS_EFFECTIVE_COLUMN_COUNT="$(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='TW4_base' AND table_name='handicap_audit' AND column_name='points_effective';")"

HANDICAP_CHANGED_BY_COLUMN_COUNT="$(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='TW4_base' AND table_name='handicap_audit' AND column_name='changed_by';")"

HANDICAP_CHANGED_AT_COLUMN_COUNT="$(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='TW4_base' AND table_name='handicap_audit' AND column_name='changed_at';")"

HANDICAP_UPDATED_TS_INDEX_COUNT="$(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema='TW4_base' AND table_name='handicap_audit' AND index_name='idx_updated_ts';")"

HANDICAP_PLAYER_BEFORE_POINTS="$(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SELECT CASE WHEN \
        (SELECT ordinal_position FROM information_schema.columns WHERE table_schema='TW4_base' AND table_name='handicap_audit' AND column_name='row_id_player') \
        < (SELECT ordinal_position FROM information_schema.columns WHERE table_schema='TW4_base' AND table_name='handicap_audit' AND column_name='points_scored') \
        THEN 1 ELSE 0 END;")"

if [ "$ADMIN_ROW" != $'admin\tadmin\t1' ]; then
    print_error "Admin seed verification failed. Expected admin\tadmin\t1, got: ${ADMIN_ROW:-<empty>}"
    exit 1
fi

if [ "$STAFF_COUNT" != "1" ]; then
    print_error "Staff seed verification failed. Expected 1 row in TW4_base.staff, got: ${STAFF_COUNT:-<empty>}"
    exit 1
fi

if [ "$CONFIG_STATUS" != "waiting" ]; then
    print_error "Config seed verification failed. Expected config_status=waiting, got: ${CONFIG_STATUS:-<empty>}"
    exit 1
fi

if [ "$ROUND_ECLECTIC_CONTEXT_TABLE" != "round_eclectic_context" ]; then
    print_error "Schema verification failed. Expected TW4_history.round_eclectic_context to exist."
    exit 1
fi

if [ "$BETWEEN_ROUNDS_COUNT" = "0" ]; then
    print_error "Workflow verification failed. Expected TW4_live.round to include workflow_step=between_rounds."
    exit 1
fi

if [ "$ROUND_WORKFLOW_ENUM_HAS_BETWEEN_ROUNDS" != "1" ]; then
    print_error "Workflow verification failed. TW4_live.round.workflow_step enum does not include between_rounds."
    exit 1
fi

if [ "$APPLICATION_LOG_TABLE" != "application_log" ]; then
    print_error "Log table verification failed. Expected TW4_base.application_log to exist."
    exit 1
fi

if [ "$HANDICAP_POINTS_SCORED_COLUMN_COUNT" != "1" ]; then
    print_error "handicap_audit verification failed. points_scored column is missing in TW4_base.handicap_audit."
    exit 1
fi

if [ "$HANDICAP_POINTS_EFFECTIVE_COLUMN_COUNT" != "1" ]; then
    print_error "handicap_audit verification failed. points_effective column is missing in TW4_base.handicap_audit."
    exit 1
fi

if [ "$HANDICAP_CHANGED_BY_COLUMN_COUNT" != "0" ] || [ "$HANDICAP_CHANGED_AT_COLUMN_COUNT" != "0" ]; then
    print_error "handicap_audit verification failed. Legacy changed_* columns are still present."
    exit 1
fi

if [ "$HANDICAP_UPDATED_TS_INDEX_COUNT" = "0" ]; then
    print_error "handicap_audit verification failed. idx_updated_ts index is missing in TW4_base.handicap_audit."
    exit 1
fi

if [ "$HANDICAP_PLAYER_BEFORE_POINTS" != "1" ]; then
    print_error "handicap_audit verification failed. row_id_player is not positioned before points_scored."
    exit 1
fi

print_status "handicap_audit verification passed: points columns present, changed_* removed, idx_updated_ts present, row_id_player promoted."

# Clean up report files and sessions to start fresh
print_status "Cleaning up report files and session data..."
docker compose -f "$COMPOSE_FILE" exec -T app bash -c 'rm -rf /var/www/html/public/reports/* && echo "Reports directory cleared"' || true
docker compose -f "$COMPOSE_FILE" exec -T app bash -c 'rm -rf /var/www/html/logs/*.log && echo "Old logs cleared"' || true
docker compose -f "$COMPOSE_FILE" exec -T app bash -c 'rm -f /tmp/sess_* && echo "Session files cleared"' || true

print_status "System test bootstrap completed successfully (virgin baseline state)."
print_status ""
print_status "Next steps:"
print_status "  1. Access the application at: http://localhost:8084 (dev) or https://tw4syst.duckdns.org (systest)"
print_status "  2. Log in as: admin / admin"
print_status "  3. Start Round will default to Round 1 for season 25/26"
print_status "  4. Create a new round and proceed with card entry"

