#!/bin/bash

set -uo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
PREFERRED_COMPOSE_FILE="$REPO_ROOT/docker-compose.systest.yml"
LEGACY_COMPOSE_FILE="$REPO_ROOT/docker-compose.prod.yml"
COMPOSE_FILE="${COMPOSE_FILE-}"

if [ -z "$COMPOSE_FILE" ]; then
    if [ -f "$PREFERRED_COMPOSE_FILE" ]; then
        COMPOSE_FILE="$PREFERRED_COMPOSE_FILE"
    elif [ -f "$LEGACY_COMPOSE_FILE" ]; then
        COMPOSE_FILE="$LEGACY_COMPOSE_FILE"
    else
        COMPOSE_FILE="$PREFERRED_COMPOSE_FILE"
    fi
fi

PASS_COUNT=0
FAIL_COUNT=0
WARN_COUNT=0

print_ok() {
    echo -e "${GREEN}[PASS]${NC} $1"
    PASS_COUNT=$((PASS_COUNT + 1))
}

print_fail() {
    echo -e "${RED}[FAIL]${NC} $1"
    FAIL_COUNT=$((FAIL_COUNT + 1))
}

print_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
    WARN_COUNT=$((WARN_COUNT + 1))
}

load_db_password() {
    if [ -n "${DB_PASSWORD-}" ]; then
        return 0
    fi

    if [ -f "$REPO_ROOT/.env" ]; then
        DB_PASSWORD="$(awk -F= '/^DB_PASSWORD=/{print substr($0, index($0,"=")+1); exit}' "$REPO_ROOT/.env")"
        export DB_PASSWORD
    fi

    if [ -z "${DB_PASSWORD-}" ]; then
        print_fail "DB_PASSWORD is not set and could not be loaded from $REPO_ROOT/.env"
        return 1
    fi

    return 0
}

check_compose_file() {
    if [ -f "$COMPOSE_FILE" ]; then
        print_ok "Found system-test compose file at $COMPOSE_FILE"
        if [ "$COMPOSE_FILE" = "$LEGACY_COMPOSE_FILE" ]; then
            print_warn "Using legacy compose filename. Prefer docker-compose.systest.yml."
        fi
    else
        print_fail "Missing system-test compose file: $COMPOSE_FILE"
    fi
}

check_required_services_running() {
    local required_services=(db app caddy)
    local running
    running="$(docker compose -f "$COMPOSE_FILE" ps --services --status running 2>/dev/null || true)"

    if [ -z "$running" ]; then
        print_fail "No running services found in system-test compose stack"
        return
    fi

    for service in "${required_services[@]}"; do
        if echo "$running" | grep -Fxq "$service"; then
            print_ok "Service running: $service"
        else
            print_fail "Service not running: $service"
        fi
    done

    if echo "$running" | grep -Fxq "phpmyadmin"; then
        print_ok "Service running: phpmyadmin"
    else
        print_warn "Service not running: phpmyadmin (optional for normal system-test runtime)"
    fi
}

check_mysql_data_mount() {
    local db_container
    local mount_info

    db_container="$(docker compose -f "$COMPOSE_FILE" ps -q db 2>/dev/null || true)"
    if [ -z "$db_container" ]; then
        print_fail "Database container not found"
        return
    fi

    mount_info="$(docker inspect -f '{{range .Mounts}}{{if eq .Destination "/var/lib/mysql"}}{{.Type}} {{.Name}}{{end}}{{end}}' "$db_container" 2>/dev/null || true)"

    if [ -z "$mount_info" ]; then
        print_fail "MySQL data mount /var/lib/mysql not found"
        return
    fi

    if echo "$mount_info" | grep -q '^volume '; then
        print_ok "MySQL data is persisted on named volume: $mount_info"
    else
        print_fail "MySQL data mount is not a named volume: $mount_info"
    fi
}

check_application_log_table() {
    if ! load_db_password; then
        return
    fi

    local table_name
    table_name="$(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
        mysql -u root -N -s -e "SHOW TABLES IN TW4_base LIKE 'application_log';" 2>/dev/null || true)"

    if [ "$table_name" = "application_log" ]; then
        print_ok "Table exists: TW4_base.application_log"
    else
        print_fail "Missing table: TW4_base.application_log"
    fi
}

check_application_log_read() {
    if [ -z "${DB_PASSWORD-}" ]; then
        return
    fi

    local row_count
    row_count="$(docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
        mysql -u root -N -s -e "SELECT COUNT(*) FROM TW4_base.application_log;" 2>/dev/null || true)"

    if [[ "$row_count" =~ ^[0-9]+$ ]]; then
        print_ok "Read test passed: TW4_base.application_log row count = $row_count"
    else
        print_fail "Read test failed for TW4_base.application_log"
    fi
}

main() {
    echo "TW4 System-Test Health Check"
    echo "Repository: $REPO_ROOT"
    echo

    if ! command -v docker >/dev/null 2>&1; then
        print_fail "docker command not found"
        echo
        echo "Summary: ${PASS_COUNT} pass, ${WARN_COUNT} warn, ${FAIL_COUNT} fail"
        exit 1
    fi

    if ! docker compose version >/dev/null 2>&1; then
        print_fail "docker compose plugin not available"
        echo
        echo "Summary: ${PASS_COUNT} pass, ${WARN_COUNT} warn, ${FAIL_COUNT} fail"
        exit 1
    fi

    check_compose_file

    if [ ! -f "$COMPOSE_FILE" ]; then
        echo
        echo "Summary: ${PASS_COUNT} pass, ${WARN_COUNT} warn, ${FAIL_COUNT} fail"
        exit 1
    fi

    check_required_services_running
    check_mysql_data_mount
    check_application_log_table
    check_application_log_read

    echo
    echo "Summary: ${PASS_COUNT} pass, ${WARN_COUNT} warn, ${FAIL_COUNT} fail"

    if [ "$FAIL_COUNT" -gt 0 ]; then
        exit 1
    fi

    exit 0
}

main "$@"
