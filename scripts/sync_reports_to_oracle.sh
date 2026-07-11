#!/usr/bin/env bash
set -Eeuo pipefail

# Sync dev public/reports -> Oracle (systest/prod) app container.
#
# Dev (this laptop) bind-mounts the workspace, so reports are plain host files.
# Oracle does NOT bind-mount the app code, so reports live inside the running
# container. We therefore: rsync host->host, then docker cp host->container.
#
# Usage:
#   scripts/sync_reports_to_oracle.sh              # overlay (add/update, no deletes)
#   scripts/sync_reports_to_oracle.sh --mirror     # exact mirror (deletes extras)
#
# Override any of these via environment if needed.
LOCAL_REPORTS="${LOCAL_REPORTS:-$HOME/TW4/public/reports}"
SSH_KEY="${SSH_KEY:-$HOME/keys/ssh-key-2026-05-11.key}"
ORACLE_USER="${ORACLE_USER:-ubuntu}"
ORACLE_HOST="${ORACLE_HOST:-140.238.200.204}"
REMOTE_PROJECT="${REMOTE_PROJECT:-/home/ubuntu/tw4-golf-management}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
REMOTE_STAGE="${REMOTE_STAGE:-/tmp/tw4-reports-sync}"
CONTAINER_REPORTS="/var/www/html/public/reports"

MIRROR=0
if [ "${1:-}" = "--mirror" ]; then
  MIRROR=1
fi

[ -d "$LOCAL_REPORTS" ] || { echo "[ERROR] Local reports dir not found: $LOCAL_REPORTS" >&2; exit 1; }
[ -f "$SSH_KEY" ]       || { echo "[ERROR] SSH key not found: $SSH_KEY" >&2; exit 1; }

SSH=(ssh -i "$SSH_KEY" -o StrictHostKeyChecking=accept-new)

echo "[1/4] Staging reports to Oracle host: ${ORACLE_HOST}:${REMOTE_STAGE}"
"${SSH[@]}" "${ORACLE_USER}@${ORACLE_HOST}" "rm -rf '${REMOTE_STAGE}' && mkdir -p '${REMOTE_STAGE}'"
rsync -avz --delete -e "ssh -i ${SSH_KEY} -o StrictHostKeyChecking=accept-new" \
  "${LOCAL_REPORTS}/" "${ORACLE_USER}@${ORACLE_HOST}:${REMOTE_STAGE}/"

echo "[2/4] Copying into the app container and fixing permissions"
"${SSH[@]}" "${ORACLE_USER}@${ORACLE_HOST}" bash -s -- "$REMOTE_PROJECT" "$COMPOSE_FILE" "$REMOTE_STAGE" "$CONTAINER_REPORTS" "$MIRROR" <<'REMOTE'
set -Eeuo pipefail
PROJECT="$1"; COMPOSE="$2"; STAGE="$3"; DEST="$4"; MIRROR="$5"
cd "$PROJECT"
CID="$(docker compose -f "$COMPOSE" ps -q app)"
[ -n "$CID" ] || { echo "[ERROR] app container not running" >&2; exit 1; }

if [ "$MIRROR" = "1" ]; then
  echo "  --mirror: clearing existing container reports"
  docker exec "$CID" sh -c "rm -rf ${DEST}/* ${DEST}/.[!.]* 2>/dev/null || true"
fi

docker exec "$CID" mkdir -p "$DEST"
docker cp "${STAGE}/." "${CID}:${DEST}/"
docker exec "$CID" chown -R www-data:www-data "$DEST"
docker exec "$CID" find "$DEST" -type d -exec chmod 775 {} \;
docker exec "$CID" find "$DEST" -type f -exec chmod 664 {} \;
REMOTE

echo "[3/4] Verifying HTML count in container"
"${SSH[@]}" "${ORACLE_USER}@${ORACLE_HOST}" bash -s -- "$REMOTE_PROJECT" "$COMPOSE_FILE" "$CONTAINER_REPORTS" <<'REMOTE'
set -Eeuo pipefail
PROJECT="$1"; COMPOSE="$2"; DEST="$3"
cd "$PROJECT"
CID="$(docker compose -f "$COMPOSE" ps -q app)"
COUNT="$(docker exec "$CID" sh -c "find ${DEST} -type f -name '*.html' | wc -l" | tr -d '[:space:]')"
echo "  HTML files in container: ${COUNT}"
REMOTE

echo "[4/4] Cleaning up host stage dir"
"${SSH[@]}" "${ORACLE_USER}@${ORACLE_HOST}" "rm -rf '${REMOTE_STAGE}'"

echo "[DONE] Reports synced to ${ORACLE_HOST} app container."
