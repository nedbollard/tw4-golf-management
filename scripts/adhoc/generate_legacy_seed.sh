#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ "$(basename "$SCRIPT_DIR")" = "scripts" ]; then
  PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
else
  PROJECT_ROOT="$SCRIPT_DIR"
fi

ROOT="$PROJECT_ROOT/public/reports"
OUT="$PROJECT_ROOT/database/seeds/generated/seed_legacy_history.sql"
SEASONS=(22_23 23_24 24_25)
IMPORT_BY="legacy_import"

mkdir -p "$(dirname "$OUT")"

extract_date() {
  tr -d '\r' < "$1" \
    | grep -Eio 'Date:[[:space:]]*[0-9]{4}-[0-9]{2}-[0-9]{2}' \
    | head -n1 | sed -E 's/[Dd]ate:[[:space:]]*//'
}

{
  echo "-- Legacy history seed generated $(date -Is)"
  echo "START TRANSACTION;"
} > "$OUT"

for season in "${SEASONS[@]}"; do
  season_dir="$ROOT/$season"
  [ -d "$season_dir" ] || continue

  for round_dir in "$season_dir"/*/; do
    [ -d "$round_dir" ] || continue
    bn="$(basename "$round_dir")"

    if [[ "$bn" =~ ^([0-9]{1,3}) ]]; then
      num="$((10#${BASH_REMATCH[1]}))"
    else
      echo "-- SKIP unparseable: $round_dir" >> "$OUT"; continue
    fi

    results="${round_dir}10_Results.html"
    if [ ! -s "$results" ]; then
      echo "-- SKIP no results file: $round_dir" >> "$OUT"; continue
    fi

    rdate="$(extract_date "$results" || true)"
    if [ -z "$rdate" ]; then
      echo "-- SKIP no date: $results" >> "$OUT"; continue
    fi

    ecl="$(find "$round_dir" -maxdepth 1 -type f -name '41_Eclectic_*.html' -printf '%f\n' | head -n1 || true)"
    if [ -n "$ecl" ]; then json="[\"$ecl\"]"; else json="[]"; fi

    cat >> "$OUT" <<SQL
INSERT INTO TW4_history.round
    (season_year, number_round, round_date, card_count, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('$season', $num, '$rdate', 0, '$IMPORT_BY', NOW(), '$IMPORT_BY', NOW())
ON DUPLICATE KEY UPDATE round_date = VALUES(round_date);

INSERT INTO TW4_history.round_eclectic_context
    (season_year, number_round, include_eclectic, combined_name, course_report_files_json, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
VALUES ('$season', $num, 0, 'none', '$json', '$IMPORT_BY', NOW(), '$IMPORT_BY', NOW())
ON DUPLICATE KEY UPDATE combined_name = VALUES(combined_name), course_report_files_json = VALUES(course_report_files_json);
SQL
  done
done

echo "COMMIT;" >> "$OUT"
echo "Wrote $OUT"
