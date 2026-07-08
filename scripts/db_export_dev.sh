    #!/usr/bin/env bash
    set -euo pipefail

    cd "$(dirname "$0")/.."

    DB_NAMES=(TW4_base TW4_live TW4_history TW4_holding)

    if [ -z "${DB_PASSWORD-}" ]; then
      DB_PASSWORD="$(awk -F= '/^DB_PASSWORD=/{print substr($0, index($0,"=")+1); exit}' .env)"
    fi
    : "${DB_PASSWORD:?DB_PASSWORD is required}"

    mkdir -p backup
    TS="$(date +%Y%m%d_%H%M%S)"
    BASE="dev_to_oracle_all_tw4_dbs_${TS}"
    OUT_SQL="backup/${BASE}.sql"
    OUT_GZ="${OUT_SQL}.gz"
    OUT_SHA="${OUT_GZ}.sha256"

    echo "[INFO] Ensuring local db container is up..."
    docker compose up -d db

    echo "[INFO] Waiting for MySQL readiness..."
    until docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
      mysqladmin -h 127.0.0.1 -P 3306 -u root ping --silent >/dev/null 2>&1; do
      sleep 2
    done

    echo "[INFO] Creating dump: ${OUT_SQL}"
    docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
      mysqldump -h 127.0.0.1 -P 3306 -u root \
      --single-transaction \
      --routines --triggers --events \
      --set-gtid-purged=OFF \
      --databases "${DB_NAMES[@]}" \
      > "${OUT_SQL}"

    gzip -f "${OUT_SQL}"

    # Write checksum using basename only so remote validation does not break on path differences.
    (
      cd backup
      sha256sum "${BASE}.sql.gz" > "${BASE}.sql.gz.sha256"
    )

    echo "${BASE}.sql.gz" > backup/latest_export.txt

    echo "[OK] Export ready:"
    echo "     backup/${BASE}.sql.gz"
    echo "     backup/${BASE}.sql.gz.sha256"
