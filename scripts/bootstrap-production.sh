#!/bin/bash

set -euo pipefail

# TW4 Production Bootstrap
# Rebuilds TW4_base, TW4_live, and TW4_history from canonical baseline schema
# files, then applies controlled seed data for config_application and staff.

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

if [ ! -f "docker-compose.prod.yml" ]; then
    print_error "docker-compose.prod.yml not found. Run from the TW4 project root."
    exit 1
fi

BASE_SCHEMA_FILE="database/baseline/TW4_base_schema.sql"
LIVE_SCHEMA_FILE="database/baseline/TW4_live_schema.sql"
HISTORY_SCHEMA_FILE="database/baseline/TW4_history_schema.sql"
BASE_SEED_FILE="database/baseline/TW4_base_seed.sql"

for required_file in "$BASE_SCHEMA_FILE" "$LIVE_SCHEMA_FILE" "$HISTORY_SCHEMA_FILE" "$BASE_SEED_FILE"; do
    if [ ! -f "$required_file" ]; then
        print_error "Required file not found: $required_file"
        exit 1
    fi
done

if [ -z "${DB_PASSWORD-}" ] && [ -f ".env" ]; then
    DB_PASSWORD="$(awk -F= '/^DB_PASSWORD=/{print substr($0, index($0,"=")+1); exit}' .env)"
fi

: "${DB_PASSWORD:?DB_PASSWORD is required (set it in .env or export it)}"

print_status "Ensuring the database container is running..."
docker compose -f docker-compose.prod.yml up -d db

print_status "Waiting for MySQL readiness..."
until docker compose -f docker-compose.prod.yml exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -u root -e "SELECT 1" >/dev/null 2>&1; do
    echo "Waiting for MySQL..."
done

print_status "Dropping and recreating TW4_base, TW4_live, and TW4_history..."
docker compose -f docker-compose.prod.yml exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -u root -e "DROP DATABASE IF EXISTS TW4_base; DROP DATABASE IF EXISTS TW4_live; DROP DATABASE IF EXISTS TW4_history; CREATE DATABASE TW4_base; CREATE DATABASE TW4_live; CREATE DATABASE TW4_history;"

print_status "Importing TW4_base schema..."
docker compose -f docker-compose.prod.yml exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -u root < "$BASE_SCHEMA_FILE"

print_status "Importing TW4_live schema..."
docker compose -f docker-compose.prod.yml exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -u root < "$LIVE_SCHEMA_FILE"

print_status "Importing TW4_history schema..."
docker compose -f docker-compose.prod.yml exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -u root < "$HISTORY_SCHEMA_FILE"

print_status "Applying controlled TW4_base seed data..."
docker compose -f docker-compose.prod.yml exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -u root TW4_base < "$BASE_SEED_FILE"

print_status "Verifying required databases and tables..."
docker compose -f docker-compose.prod.yml exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SHOW DATABASES LIKE 'TW4_base'; SHOW DATABASES LIKE 'TW4_live'; SHOW DATABASES LIKE 'TW4_history'; SHOW TABLES IN TW4_base LIKE 'staff'; SHOW TABLES IN TW4_base LIKE 'config_application'; SHOW TABLES IN TW4_live LIKE 'round'; SHOW TABLES IN TW4_live LIKE 'card'; SHOW TABLES IN TW4_live LIKE 'card_by_hole'; SHOW TABLES IN TW4_live LIKE 'results'; SHOW TABLES IN TW4_history LIKE 'round'; SHOW TABLES IN TW4_history LIKE 'card'; SHOW TABLES IN TW4_history LIKE 'card_by_hole'; SHOW TABLES IN TW4_history LIKE 'results';"

ADMIN_ROW="$(docker compose -f docker-compose.prod.yml exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SELECT username, role, is_active FROM TW4_base.staff WHERE username='admin' LIMIT 1;")"

STAFF_COUNT="$(docker compose -f docker-compose.prod.yml exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SELECT COUNT(*) FROM TW4_base.staff;")"

CONFIG_STATUS="$(docker compose -f docker-compose.prod.yml exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -N -s -u root -e "SELECT config_value_string FROM TW4_base.config_application WHERE config_name='config_status' LIMIT 1;")"

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

print_status "Production bootstrap completed successfully."