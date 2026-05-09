#!/bin/bash

set -euo pipefail

# TW4 Production Bootstrap
# Creates the base and live databases, then replays the repository migrations
# in sorted order so a fresh VPS install can be prepared for a remote tester.

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

if [ -z "${DB_PASSWORD-}" ] && [ -f ".env" ]; then
    DB_PASSWORD="$(awk -F= '/^DB_PASSWORD=/{print substr($0, index($0,"=")+1); exit}' .env)"
fi

: "${DB_PASSWORD:?DB_PASSWORD is required (set it in .env or export it)}"

print_status "Ensuring the database container is running..."
docker compose -f docker-compose.prod.yml up -d db

print_status "Creating TW4_base and TW4_live databases if needed..."
docker compose -f docker-compose.prod.yml exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS TW4_base; CREATE DATABASE IF NOT EXISTS TW4_live;"

print_status "Applying migrations in sorted order..."
while IFS= read -r migration; do
    migration_name="$(basename "$migration")"
    if [ "$migration_name" = "999_current_schema.sql" ]; then
        continue
    fi

    print_status "Applying $migration_name"
    docker compose -f docker-compose.prod.yml exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
        mysql -u root TW4_base < "$migration"
done < <(find src/migrations -maxdepth 1 -name '*.sql' | sort)

print_status "Production bootstrap completed successfully."