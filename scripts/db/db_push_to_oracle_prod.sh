    #!/usr/bin/env bash
    set -euo pipefail

    SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
    REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
    cd "${REPO_ROOT}"

    ORACLE_USER="ubuntu"
    ORACLE_HOST="159.13.35.84"
    SSH_KEY="/home/ned-bollard/keys/ssh-key-2026-08-10.key"
    REMOTE_DROP_DIR="/tmp/tw4-import"

    if [ -f backup/latest_export.txt ]; then
      FILE_NAME="$(cat backup/latest_export.txt)"
      DUMP="backup/${FILE_NAME}"
    else
      DUMP="$(ls -1t backup/dev_to_oracle_all_tw4_dbs_*.sql.gz | head -n1)"
      FILE_NAME="$(basename "$DUMP")"
    fi

    SHA="${DUMP}.sha256"

    [ -f "$DUMP" ] || { echo "[ERROR] Dump not found: $DUMP"; exit 1; }
    [ -f "$SHA" ] || { echo "[ERROR] Checksum not found: $SHA"; exit 1; }

    echo "[INFO] Ensuring remote drop folder exists..."
    ssh -i "$SSH_KEY" "${ORACLE_USER}@${ORACLE_HOST}" "mkdir -p ${REMOTE_DROP_DIR}"

    echo "[INFO] Copying dump and checksum..."
    scp -i "$SSH_KEY" "$DUMP" "$SHA" "${ORACLE_USER}@${ORACLE_HOST}:${REMOTE_DROP_DIR}/"

    echo "[OK] Uploaded:"
    echo "     ${REMOTE_DROP_DIR}/${FILE_NAME}"
    echo "     ${REMOTE_DROP_DIR}/${FILE_NAME}.sha256"
    echo
    echo "[NEXT] Restore on Oracle with:"
    echo "ssh -i ${SSH_KEY} ${ORACLE_USER}@${ORACLE_HOST} \"cd ~/TW4 && ./scripts/db/db_import_prod.sh ${REMOTE_DROP_DIR}/${FILE_NAME}\""
