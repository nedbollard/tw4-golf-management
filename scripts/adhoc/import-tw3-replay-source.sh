#!/usr/bin/env bash

set -Eeuo pipefail

SOURCE_DATABASE="${TW3_SOURCE_DATABASE:-tw3_25_26}"
TARGET_DATABASE="${TW3_REPLAY_DATABASE:-TW3_replay_25_26}"
TW3_HOST="${TW3_DB_HOST:-127.0.0.1}"
TW3_PORT="${TW3_DB_PORT:-3306}"
TW3_USER="${TW3_DB_USER:-}"
REPLAY_TABLES=(
    config_basic
    haggle_best_5
    haggle_eclectic
    hist_card
    hist_card_byhole
    hist_handicap
    hist_round
    player
)

if [[ -z "$TW3_USER" ]]; then
    read -rp "TW3 MariaDB username: " TW3_USER
fi

if [[ -z "${TW3_DB_PASSWORD:-}" ]]; then
    read -rsp "TW3 MariaDB password: " TW3_DB_PASSWORD
    echo
fi

export MYSQL_PWD="$TW3_DB_PASSWORD"
trap 'unset MYSQL_PWD TW3_DB_PASSWORD' EXIT

if ! mariadb \
    --host="$TW3_HOST" \
    --port="$TW3_PORT" \
    --user="$TW3_USER" \
    --batch \
    --skip-column-names \
    -e "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '$SOURCE_DATABASE'" \
    | grep -Fxq "$SOURCE_DATABASE"; then
    echo "Unable to read source database $SOURCE_DATABASE." >&2
    exit 1
fi

printf 'Refreshing Docker replay source %s from %s...\n' "$TARGET_DATABASE" "$SOURCE_DATABASE"

docker compose exec -T db sh -lc \
    'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS \`$1\`; CREATE DATABASE \`$1\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"' \
    -- "$TARGET_DATABASE"

mariadb-dump \
    --host="$TW3_HOST" \
    --port="$TW3_PORT" \
    --user="$TW3_USER" \
    --single-transaction \
    --skip-lock-tables \
    --no-tablespaces \
    "$SOURCE_DATABASE" \
    "${REPLAY_TABLES[@]}" \
| docker compose exec -T db sh -lc \
    'mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$1"' \
    -- "$TARGET_DATABASE"

docker compose exec -T db sh -lc \
    'mysql -u root -p"$MYSQL_ROOT_PASSWORD" --batch --skip-column-names "$1" -e "SELECT CONCAT(COUNT(*), '\'' tables imported'\'') FROM information_schema.TABLES WHERE TABLE_SCHEMA = '\''$1'\''"' \
    -- "$TARGET_DATABASE"