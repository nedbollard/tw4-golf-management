#!/usr/bin/env bash
set -euo pipefail

usage() {
  echo "Usage: $0 {prod|systest} [dump.sql.gz]"
  echo "Uploads a dump and checksum only; does not restore a database."
}

if [[ $# -lt 1 || $# -gt 2 ]]; then
  usage >&2
  exit 1
fi
case "$1" in
  prod)
    ORACLE_SSH="${TW4_PROD_SSH:-tw4-oracle-prod}"
    REMOTE_PROJECT='~/TW4'
    ;;
  systest)
    ORACLE_SSH="${TW4_SYSTEST_SSH:-tw4-oracle-systest}"
    REMOTE_PROJECT='~/tw4-golf-management'
    ;;
  *) usage >&2; exit 1 ;;
esac
TARGET="$1"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
cd "$REPO_ROOT"
REMOTE_DROP_DIR="/tmp/tw4-import"

if [[ $# -eq 2 ]]; then
  DUMP="$2"
elif [[ -f backup/latest_export.txt ]]; then
  FILE_NAME="$(cat backup/latest_export.txt)"
  DUMP="backup/${FILE_NAME}"
else
  shopt -s nullglob
  EXPORTS=(backup/dev_to_oracle_all_tw4_dbs_*.sql.gz)
  [[ ${#EXPORTS[@]} -gt 0 ]] || { echo "[ERROR] No development export found." >&2; exit 1; }
  DUMP="${EXPORTS[0]}"
  for export in "${EXPORTS[@]:1}"; do
    if [[ "$export" -nt "$DUMP" ]]; then DUMP="$export"; fi
  done
fi

SHA="${DUMP}.sha256"
[[ -f "$DUMP" ]] || { echo "[ERROR] Dump not found: $DUMP" >&2; exit 1; }
[[ -f "$SHA" ]] || { echo "[ERROR] Checksum not found: $SHA" >&2; exit 1; }
FILE_NAME="$(basename "$DUMP")"
[[ "$FILE_NAME" =~ ^[a-zA-Z0-9_.-]+$ ]] || {
  echo "[ERROR] Dump filename must contain only letters, digits, dots, hyphens and underscores." >&2
  exit 1
}
gzip -t "$DUMP"
EXPECTED="$(awk 'NR == 1 {print $1}' "$SHA")"
ACTUAL="$(sha256sum "$DUMP" | awk '{print $1}')"
[[ "$EXPECTED" == "$ACTUAL" ]] || { echo "[ERROR] Checksum mismatch." >&2; exit 1; }

echo "[INFO] Upload target: $TARGET via $ORACLE_SSH ($REMOTE_PROJECT)"
ssh "$ORACLE_SSH" "mkdir -p ${REMOTE_DROP_DIR}"
scp "$DUMP" "$SHA" "${ORACLE_SSH}:${REMOTE_DROP_DIR}/"
echo "[OK] Uploaded dump and checksum; no database was changed."
echo "[NEXT] Review the target and stop writers before restoring on Oracle:"
printf 'ssh %q "cd %s && RESTORE_TARGET=%s ./scripts/db/db_import_oracle.sh %s/%s"\n' \
  "$ORACLE_SSH" "$REMOTE_PROJECT" "$TARGET" "$REMOTE_DROP_DIR" "$FILE_NAME"
