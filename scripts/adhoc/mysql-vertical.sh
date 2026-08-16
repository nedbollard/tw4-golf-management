#!/usr/bin/env bash

set -euo pipefail

if [[ $# -lt 2 ]]; then
    echo "Usage: $0 <database> <sql query>"
    echo "Example: $0 TW4_live \"SELECT * FROM round WHERE row_id = 1\""
    exit 1
fi

database="$1"
shift
query="$*"

docker compose exec -T db sh -lc 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -E "$1" -e "$2"' -- "$database" "$query"
