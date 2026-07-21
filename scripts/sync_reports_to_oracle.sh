#!/usr/bin/env bash
set -Eeuo pipefail

# Sync dev public/reports -> Oracle system-test app container.
#
# Dev (this laptop) bind-mounts the workspace, so reports are plain host files.
# Oracle does NOT bind-mount the app code, so reports live inside the running
# container. We therefore: rsync host->host, then docker cp host->container.
#
# Usage:
#   scripts/sync_reports_to_oracle.sh              # overlay (add/update, no deletes)
#   scripts/sync_reports_to_oracle.sh --mirror     # exact mirror (deletes extras)
#
# Optional verification:
#   VERIFY_SAMPLE_REL="24_25/022_Apr_02/10_Results.html" scripts/sync_reports_to_oracle.sh
#   This compares SHA256 of one known report file between local source and container.
#
# Override any of these via environment if needed.
LOCAL_REPORTS="${LOCAL_REPORTS:-$HOME/TW4/public/reports}"
SSH_KEY="${SSH_KEY:-$HOME/keys/ssh-key-2026-05-11.key}"
ORACLE_USER="${ORACLE_USER:-ubuntu}"
ORACLE_HOST="${ORACLE_HOST:-140.238.200.204}"
REMOTE_PROJECT="${REMOTE_PROJECT:-/home/ubuntu/tw4-golf-management}"
PREFERRED_COMPOSE_FILE="docker-compose.systest.yml"
LEGACY_COMPOSE_FILE="docker-compose.prod.yml"
COMPOSE_FILE="${COMPOSE_FILE:-}"
REMOTE_STAGE="${REMOTE_STAGE:-/tmp/tw4-reports-sync}"
CONTAINER_REPORTS="/var/www/html/public/reports"
VERIFY_SAMPLE_REL="${VERIFY_SAMPLE_REL:-}"

MIRROR=0
if [ "${1:-}" = "--mirror" ]; then
  MIRROR=1
fi

if [ -z "$COMPOSE_FILE" ]; then
  COMPOSE_FILE="$PREFERRED_COMPOSE_FILE"
fi

[ -d "$LOCAL_REPORTS" ] || { echo "[ERROR] Local reports dir not found: $LOCAL_REPORTS" >&2; exit 1; }
[ -f "$SSH_KEY" ]       || { echo "[ERROR] SSH key not found: $SSH_KEY" >&2; exit 1; }

SSH=(ssh -i "$SSH_KEY" -o StrictHostKeyChecking=accept-new)

LOCAL_SAMPLE_HASH=""
if [ -n "$VERIFY_SAMPLE_REL" ]; then
  SAMPLE_PATH="$LOCAL_REPORTS/$VERIFY_SAMPLE_REL"
  [ -f "$SAMPLE_PATH" ] || { echo "[ERROR] VERIFY_SAMPLE_REL not found in local reports: $SAMPLE_PATH" >&2; exit 1; }
  LOCAL_SAMPLE_HASH="$(sha256sum "$SAMPLE_PATH" | awk '{print $1}')"
fi

echo "[1/5] Staging reports to Oracle host: ${ORACLE_HOST}:${REMOTE_STAGE}"
"${SSH[@]}" "${ORACLE_USER}@${ORACLE_HOST}" "rm -rf '${REMOTE_STAGE}' && mkdir -p '${REMOTE_STAGE}'"
if [ "$MIRROR" = "1" ]; then
  rsync -avz --delete -e "ssh -i ${SSH_KEY} -o StrictHostKeyChecking=accept-new" \
    "${LOCAL_REPORTS}/" "${ORACLE_USER}@${ORACLE_HOST}:${REMOTE_STAGE}/"
else
  rsync -avz -e "ssh -i ${SSH_KEY} -o StrictHostKeyChecking=accept-new" \
    "${LOCAL_REPORTS}/" "${ORACLE_USER}@${ORACLE_HOST}:${REMOTE_STAGE}/"
fi

echo "[2/5] Syncing staged reports into Oracle project tree"
"${SSH[@]}" "${ORACLE_USER}@${ORACLE_HOST}" bash -s -- "$REMOTE_PROJECT" "$REMOTE_STAGE" "$MIRROR" <<'REMOTE'
set -Eeuo pipefail
PROJECT="$1"; STAGE="$2"; MIRROR="$3"
PROJECT_REPORTS="${PROJECT}/public/reports"

mkdir -p "$PROJECT_REPORTS"

if [ "$MIRROR" = "1" ]; then
  echo "  --mirror: clearing existing project reports"
  rm -rf "${PROJECT_REPORTS}"/* "${PROJECT_REPORTS}"/.[!.]* 2>/dev/null || true
fi

cp -a "${STAGE}/." "$PROJECT_REPORTS/"
REMOTE

echo "[3/5] Copying project reports into the app container and fixing permissions"
"${SSH[@]}" "${ORACLE_USER}@${ORACLE_HOST}" bash -s -- "$REMOTE_PROJECT" "$COMPOSE_FILE" "$PREFERRED_COMPOSE_FILE" "$LEGACY_COMPOSE_FILE" "$CONTAINER_REPORTS" "$MIRROR" <<'REMOTE'
set -Eeuo pipefail
PROJECT="$1"; COMPOSE="$2"; PREFERRED_COMPOSE="$3"; LEGACY_COMPOSE="$4"; DEST="$5"; MIRROR="$6"
cd "$PROJECT"
PROJECT_REPORTS="${PROJECT}/public/reports"

if [ ! -f "$COMPOSE" ]; then
  if [ -f "$PREFERRED_COMPOSE" ]; then
    COMPOSE="$PREFERRED_COMPOSE"
  elif [ -f "$LEGACY_COMPOSE" ]; then
    COMPOSE="$LEGACY_COMPOSE"
    echo "  [WARN] Using legacy compose file: $LEGACY_COMPOSE"
  else
    echo "[ERROR] Compose file not found: $COMPOSE (preferred: $PREFERRED_COMPOSE; legacy: $LEGACY_COMPOSE)" >&2
    exit 1
  fi
fi

CID="$(docker compose -f "$COMPOSE" ps -q app)"
[ -n "$CID" ] || { echo "[ERROR] app container not running" >&2; exit 1; }

if [ "$MIRROR" = "1" ]; then
  echo "  --mirror: clearing existing container reports"
  docker exec "$CID" sh -c "rm -rf ${DEST}/* ${DEST}/.[!.]* 2>/dev/null || true"
fi

docker exec "$CID" mkdir -p "$DEST"
docker cp "${PROJECT_REPORTS}/." "${CID}:${DEST}/"
docker exec "$CID" chown -R www-data:www-data "$DEST"
docker exec "$CID" find "$DEST" -type d -exec chmod 775 {} \;
docker exec "$CID" find "$DEST" -type f -exec chmod 664 {} \;
REMOTE

echo "[4/5] Verifying HTML count in container"
"${SSH[@]}" "${ORACLE_USER}@${ORACLE_HOST}" bash -s -- "$REMOTE_PROJECT" "$COMPOSE_FILE" "$PREFERRED_COMPOSE_FILE" "$LEGACY_COMPOSE_FILE" "$CONTAINER_REPORTS" "$VERIFY_SAMPLE_REL" "$LOCAL_SAMPLE_HASH" <<'REMOTE'
set -Eeuo pipefail
PROJECT="$1"; COMPOSE="$2"; PREFERRED_COMPOSE="$3"; LEGACY_COMPOSE="$4"; DEST="$5"; SAMPLE_REL="$6"; LOCAL_HASH="$7"
cd "$PROJECT"

if [ ! -f "$COMPOSE" ]; then
  if [ -f "$PREFERRED_COMPOSE" ]; then
    COMPOSE="$PREFERRED_COMPOSE"
  elif [ -f "$LEGACY_COMPOSE" ]; then
    COMPOSE="$LEGACY_COMPOSE"
  else
    echo "[ERROR] Compose file not found for verification." >&2
    exit 1
  fi
fi

CID="$(docker compose -f "$COMPOSE" ps -q app)"
COUNT="$(docker exec "$CID" sh -c "find ${DEST} -type f -name '*.html' | wc -l" | tr -d '[:space:]')"
echo "  HTML files in container: ${COUNT}"

if [ -n "$SAMPLE_REL" ]; then
  SAMPLE_PATH="${DEST}/${SAMPLE_REL}"
  CONTAINER_HASH="$(docker exec "$CID" sh -c "sha256sum '${SAMPLE_PATH}' 2>/dev/null | cut -d' ' -f1")"
  if [ -z "$CONTAINER_HASH" ]; then
    echo "[ERROR] Verification sample not found in container: ${SAMPLE_PATH}" >&2
    exit 1
  fi
  if [ "$CONTAINER_HASH" != "$LOCAL_HASH" ]; then
    echo "[ERROR] Verification hash mismatch for ${SAMPLE_REL}" >&2
    echo "        local:     ${LOCAL_HASH}" >&2
    echo "        container: ${CONTAINER_HASH}" >&2
    exit 1
  fi
  echo "  Verification sample hash matched: ${SAMPLE_REL}"
fi
REMOTE

echo "[5/5] Cleaning up host stage dir"
"${SSH[@]}" "${ORACLE_USER}@${ORACLE_HOST}" "rm -rf '${REMOTE_STAGE}'"

echo "[DONE] Reports synced to ${ORACLE_HOST} app container."
