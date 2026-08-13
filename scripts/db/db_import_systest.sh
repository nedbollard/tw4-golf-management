#!/usr/bin/env bash
set -euo pipefail

PREFERRED_COMPOSE_FILE="docker-compose.systest.yml"
LEGACY_COMPOSE_FILE="docker-compose.prod.yml"
FALLBACK_COMPOSE_FILE="docker-compose.yml"
COMPOSE_FILE="${COMPOSE_FILE-}"
DB_NAMES=(TW4_base TW4_live TW4_history TW4_holding)
MYSQL_WAIT_TIMEOUT="${MYSQL_WAIT_TIMEOUT:-180}"

if [ $# -ne 1 ]; then
  echo "Usage: $0 /path/to/dev_to_oracle_all_tw4_dbs_YYYYmmdd_HHMMSS.sql.gz"
  exit 1
fi

  DUMP_GZ="$1"
  SUM_FILE="${DUMP_GZ}.sha256"

  [ -f "$DUMP_GZ" ] || { echo "[ERROR] Dump file not found: $DUMP_GZ"; exit 1; }

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
cd "${REPO_ROOT}"

if [ -z "$COMPOSE_FILE" ]; then
  if [ -f "$PREFERRED_COMPOSE_FILE" ]; then
    COMPOSE_FILE="$PREFERRED_COMPOSE_FILE"
  elif [ -f "$LEGACY_COMPOSE_FILE" ]; then
    COMPOSE_FILE="$LEGACY_COMPOSE_FILE"
    echo "[WARN] Using legacy compose file '$LEGACY_COMPOSE_FILE'."
  elif [ -f "$FALLBACK_COMPOSE_FILE" ]; then
    COMPOSE_FILE="$FALLBACK_COMPOSE_FILE"
    echo "[WARN] Using fallback compose file '$FALLBACK_COMPOSE_FILE'."
  else
    echo "[ERROR] No compose file found: ${PREFERRED_COMPOSE_FILE}, ${LEGACY_COMPOSE_FILE}, or ${FALLBACK_COMPOSE_FILE}"
    exit 1
  fi
fi

[ -f "$COMPOSE_FILE" ] || { echo "[ERROR] Compose file not found: $COMPOSE_FILE"; exit 1; }

if [ -z "${DB_PASSWORD-}" ]; then
  DB_PASSWORD="$(awk -F= '/^DB_PASSWORD=/{print substr($0, index($0,"=")+1); exit}' .env)"
fi
: "${DB_PASSWORD:?DB_PASSWORD is required}"

    echo "[INFO] Ensuring systest db container is up..."
    docker compose -f "$COMPOSE_FILE" up -d db

    echo "[INFO] Waiting for MySQL readiness (timeout: ${MYSQL_WAIT_TIMEOUT}s)..."
    START_TS="$(date +%s)"
    until docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
      mysqladmin -h 127.0.0.1 -P 3306 -u root ping --silent >/dev/null 2>&1; do
      NOW_TS="$(date +%s)"
      ELAPSED="$((NOW_TS - START_TS))"
      if [ "$ELAPSED" -ge "$MYSQL_WAIT_TIMEOUT" ]; then
        echo "[ERROR] Timed out waiting for MySQL after ${MYSQL_WAIT_TIMEOUT}s"
        docker compose -f "$COMPOSE_FILE" ps db || true
        docker compose -f "$COMPOSE_FILE" logs --tail=50 db || true
        exit 1
      fi
      sleep 2
    done

    echo "[INFO] Validating gzip..."
    gzip -t "$DUMP_GZ"

    if [ -f "$SUM_FILE" ]; then
      echo "[INFO] Verifying checksum..."
      EXPECTED="$(awk '{print $1}' "$SUM_FILE")"
      ACTUAL="$(sha256sum "$DUMP_GZ" | awk '{print $1}')"
      [ "$EXPECTED" = "$ACTUAL" ] || { echo "[ERROR] Checksum mismatch"; exit 1; }
    else
      echo "[WARN] No checksum file found: $SUM_FILE (continuing)"
    fi

    TS="$(date +%Y%m%d_%H%M%S)"
    mkdir -p backup
    PRE_BACKUP="backup/oracle_pre_restore_${TS}.sql.gz"

    echo "[INFO] Taking Oracle safety backup: ${PRE_BACKUP}"
    docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
      mysqldump -h 127.0.0.1 -P 3306 -u root \
      --single-transaction \
      --routines --triggers --events \
      --set-gtid-purged=OFF \
      --databases "${DB_NAMES[@]}" \
      | gzip -c > "$PRE_BACKUP"

    echo "[INFO] Dropping target databases..."
    docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
      mysql -h 127.0.0.1 -P 3306 -u root -e \
      "DROP DATABASE IF EXISTS TW4_base; DROP DATABASE IF EXISTS TW4_live; DROP DATABASE IF EXISTS TW4_history; DROP DATABASE IF EXISTS TW4_holding;"

    echo "[INFO] Restoring dump..."
    gunzip -c "$DUMP_GZ" | \
      docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
      mysql -h 127.0.0.1 -P 3306 -u root

    echo "[INFO] Verifying databases..."
    docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
      mysql -h 127.0.0.1 -P 3306 -u root -N -s -e \
      "SHOW DATABASES LIKE 'TW4_base'; SHOW DATABASES LIKE 'TW4_live'; SHOW DATABASES LIKE 'TW4_history'; SHOW DATABASES LIKE 'TW4_holding';"

    echo "[INFO] Row-count smoke check..."
    docker compose -f "$COMPOSE_FILE" exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
      mysql -h 127.0.0.1 -P 3306 -u root -N -s -e \
      "SELECT 'TW4_base.staff', COUNT(*) FROM TW4_base.staff; SELECT 'TW4_live.round', COUNT(*) FROM TW4_live.round;"

    echo "[OK] Restore complete."
    echo "[INFO] Pre-restore backup: ${PRE_BACKUP}"
