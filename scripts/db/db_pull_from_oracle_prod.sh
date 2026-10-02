#!/usr/bin/env bash
set -euo pipefail
umask 077

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
cd "$REPO_ROOT"

usage() {
  echo "Usage: $0 {development|systest|both} --yes"
  echo "Replaces the selected databases with a fresh production snapshot."
}

if [ "$#" -ne 2 ] || [ "$2" != "--yes" ]; then
  usage
  exit 1
fi

case "$1" in
  development|systest|both) TARGET="$1" ;;
  *) usage; exit 1 ;;
esac

PROD_SSH="${TW4_PROD_SSH:-tw4-oracle-prod}"
SYSTEST_SSH="${TW4_SYSTEST_SSH:-tw4-oracle-systest}"
REMOTE_DROP_DIR="/tmp/tw4-import"

for command in ssh scp gzip sha256sum; do
  command -v "$command" >/dev/null || { echo "[ERROR] Missing command: $command" >&2; exit 1; }
done
if [ "$TARGET" != systest ]; then
  command -v docker >/dev/null || { echo "[ERROR] Missing command: docker" >&2; exit 1; }
fi

mkdir -p backup
BASE="prod_to_lower_all_tw4_dbs_$(date +%Y%m%d_%H%M%S)"
DUMP="backup/${BASE}.sql.gz"
PARTIAL="$(mktemp "backup/.${BASE}.XXXXXX")"
trap 'rm -f "$PARTIAL"' EXIT

echo "[INFO] Exporting production databases via ${PROD_SSH}..."
ssh "$PROD_SSH" 'bash -s' > "$PARTIAL" <<'REMOTE'
set -euo pipefail
cd ~/TW4
DB_PASSWORD="$(awk -F= '/^DB_PASSWORD=/{print substr($0, index($0,"=")+1); exit}' .env)"
: "${DB_PASSWORD:?DB_PASSWORD is required on production}"
export DB_PASSWORD
docker compose -f docker-compose.prod.yml exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
  mysqldump -h 127.0.0.1 -P 3306 -u root --single-transaction \
  --routines --triggers --events --set-gtid-purged=OFF \
  --databases TW4_base TW4_live TW4_history TW4_holding | gzip -c
REMOTE

gzip -t "$PARTIAL"
mv "$PARTIAL" "$DUMP"
(cd backup && sha256sum "${BASE}.sql.gz" > "${BASE}.sql.gz.sha256")
echo "[OK] Production snapshot: $DUMP"

if [ "$TARGET" = systest ] || [ "$TARGET" = both ]; then
  echo "[INFO] Copying snapshot to system test..."
  ssh "$SYSTEST_SSH" "mkdir -p ${REMOTE_DROP_DIR}"
  scp "$DUMP" "${DUMP}.sha256" "${SYSTEST_SSH}:${REMOTE_DROP_DIR}/"
  echo "[INFO] Restoring system test..."
  ssh "$SYSTEST_SSH" "cd ~/tw4-golf-management && ./scripts/db/db_import_systest.sh ${REMOTE_DROP_DIR}/${BASE}.sql.gz"
  echo "[OK] System test restored."
fi

if [ "$TARGET" = development ] || [ "$TARGET" = both ]; then
  echo "[INFO] Restoring local development..."
  COMPOSE_FILE=docker-compose.yml RESTORE_TARGET=development "${SCRIPT_DIR}/db_import_systest.sh" "$DUMP"
  echo "[OK] Development restored."
fi