#!/usr/bin/env bash

set -euo pipefail

# Local-dev smoke test for new eclectic behavior.
#
# Usage examples:
#   scripts/dev-eclectic-smoke.sh --season 25_26 --round 2 --expect include
#   scripts/dev-eclectic-smoke.sh --season 25_26 --round 3 --expect bypass
#
# Expectations:
# - Run this AFTER you finish a round in the app.
# - Works against local dev docker-compose stack.
# - Course eclectic report(s) are always expected.
# - Combined eclectic report is expected for include, omitted for bypass.

SEASON=""
ROUND=""
EXPECT=""
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

usage() {
  cat <<'EOF'
Usage:
  scripts/dev-eclectic-smoke.sh --season NN_NN --round N --expect include|bypass

Options:
  --season   Season year, for example 25_26
  --round    Round number, for example 2
  --expect   include or bypass
  -h, --help Show this help

Notes:
  - Script uses local dev compose service: db
  - DB_PASSWORD is taken from environment, or from .env in repo root
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --season)
      SEASON="${2:-}"
      shift 2
      ;;
    --round)
      ROUND="${2:-}"
      shift 2
      ;;
    --expect)
      EXPECT="${2:-}"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown argument: $1" >&2
      usage
      exit 1
      ;;
  esac
done

if [[ -z "$SEASON" || -z "$ROUND" || -z "$EXPECT" ]]; then
  usage
  exit 1
fi

if [[ "$EXPECT" != "include" && "$EXPECT" != "bypass" ]]; then
  echo "--expect must be include or bypass" >&2
  exit 1
fi

if [[ -z "${DB_PASSWORD:-}" && -f "$ROOT_DIR/.env" ]]; then
  set -a
  # shellcheck disable=SC1090
  source "$ROOT_DIR/.env"
  set +a
fi

if [[ -z "${DB_PASSWORD:-}" ]]; then
  echo "DB_PASSWORD is not set (and not found in .env)." >&2
  exit 1
fi

run_mysql_scalar() {
  local sql="$1"
  docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
    mysql -u root -N -B -e "$sql"
}

echo "Checking history round row..."
ROUND_ROW="$(run_mysql_scalar "SELECT CONCAT(COALESCE(DATE_FORMAT(round_date,'%Y-%m-%d'),''),'|',COALESCE(CAST(course_played_id AS CHAR),'')) FROM TW4_history.round WHERE season_year='$SEASON' AND number_round=$ROUND LIMIT 1;")"
if [[ -z "$ROUND_ROW" ]]; then
  echo "FAIL: No TW4_history.round row for $SEASON round $ROUND" >&2
  exit 2
fi

ROUND_DATE="${ROUND_ROW%%|*}"

echo "Checking round_eclectic_context..."
CTX_ROW="$(run_mysql_scalar "SELECT CONCAT(include_eclectic,'|',COALESCE(configured_ident_eclectic,''),'|',COALESCE(played_course_name,''),'|',COALESCE(combined_name,''),'|',COALESCE(course_report_files_json,''),'|',COALESCE(combined_report_filename,'')) FROM TW4_history.round_eclectic_context WHERE season_year='$SEASON' AND number_round=$ROUND LIMIT 1;")"

if [[ -z "$CTX_ROW" ]]; then
  echo "FAIL: No TW4_history.round_eclectic_context row for $SEASON round $ROUND" >&2
  exit 3
fi

INCLUDE_FLAG="${CTX_ROW%%|*}"
REST="${CTX_ROW#*|}"
CFG_IN_CTX="${REST%%|*}"
REST="${REST#*|}"
PLAYED_NAME="${REST%%|*}"
REST="${REST#*|}"
COMBINED_NAME="${REST%%|*}"
REST="${REST#*|}"
COURSE_JSON="${REST%%|*}"
COMBINED_FILE="${REST##*|}"

echo "  include_eclectic: $INCLUDE_FLAG"
echo "  configured_ident_eclectic: $CFG_IN_CTX"
echo "  played_course_name: $PLAYED_NAME"
echo "  combined_name: $COMBINED_NAME"
echo "  course_report_files_json: ${COURSE_JSON:-[]}" 
echo "  combined_report_filename: ${COMBINED_FILE:-<empty>}"

if [[ "$EXPECT" == "include" && "$INCLUDE_FLAG" != "1" ]]; then
  echo "FAIL: expected include_eclectic=1 but got $INCLUDE_FLAG" >&2
  exit 4
fi

if [[ "$EXPECT" == "bypass" && "$INCLUDE_FLAG" != "0" ]]; then
  echo "FAIL: expected include_eclectic=0 but got $INCLUDE_FLAG" >&2
  exit 5
fi

if [[ -z "$ROUND_DATE" ]]; then
  ROUND_SLUG="$(printf '%03d' "$ROUND")"
else
  ROUND_SLUG="$(printf '%03d' "$ROUND")_$(date -d "$ROUND_DATE" +%b_%d)"
fi

REPORT_DIR="$ROOT_DIR/public/reports/$SEASON/$ROUND_SLUG"

echo "Checking report directory: $REPORT_DIR"
if [[ ! -d "$REPORT_DIR" ]]; then
  echo "FAIL: report directory not found" >&2
  exit 6
fi

echo "  found files:"
find "$REPORT_DIR" -maxdepth 1 -type f -printf '    %f\n' | sort

COURSE_FILES="$(echo "$COURSE_JSON" | tr -d '[]" ' | tr ',' '\n' | sed '/^$/d')"
if [[ -z "$COURSE_FILES" ]]; then
  echo "FAIL: expected at least one course eclectic filename in course_report_files_json" >&2
  exit 7
fi

while IFS= read -r file; do
  [[ -z "$file" ]] && continue
  if [[ ! -f "$REPORT_DIR/$file" ]]; then
    echo "FAIL: expected eclectic course file missing: $file" >&2
    exit 8
  fi
done <<< "$COURSE_FILES"

if [[ "$EXPECT" == "include" ]]; then
  if [[ -z "$COMBINED_FILE" ]]; then
    echo "FAIL: include expected but combined_report_filename is empty" >&2
    exit 9
  fi

  if [[ ! -f "$REPORT_DIR/$COMBINED_FILE" ]]; then
    echo "FAIL: expected eclectic combined file missing: $COMBINED_FILE" >&2
    exit 10
  fi

  echo "PASS: include scenario validated."
else
  if [[ -n "$COMBINED_FILE" && -f "$REPORT_DIR/$COMBINED_FILE" ]]; then
    echo "FAIL: bypass expected but combined eclectic file exists: $COMBINED_FILE" >&2
    exit 11
  fi

  echo "PASS: bypass scenario validated."
fi

echo "Done."