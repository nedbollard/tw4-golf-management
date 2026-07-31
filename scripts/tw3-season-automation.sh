#!/usr/bin/env bash
# TW4 AUTOMATION SCRIPT - For TW3-to-TW4 Season Replication Only
# This script is for development/testing of season replication.
# When moving TW4 to production, this script and the corresponding API endpoint
# (RoundController::automationFinishRound and route /api/automation/finish-round)
# should be removed or isolated to a separate automation-only environment.

set -Eeuo pipefail

SEASON="${TW3_REPLAY_SEASON:-25_26}"
SOURCE_DATABASE="${TW3_REPLAY_DATABASE:-TW3_replay_25_26}"
ACTION="${1:-}"
ROUND_NUMBER="${2:-}"
AUTOMATION_TOKEN="${AUTOMATION_TOKEN:-}"
APP_BASE_URL="${APP_BASE_URL:-http://localhost:8084}"

if [[ ! "$SOURCE_DATABASE" =~ ^[A-Za-z0-9_]+$ ]]; then
    echo "Invalid replay database name: $SOURCE_DATABASE" >&2
    exit 1
fi

if [[ ! "$ACTION" =~ ^(auto)$ ]] || [[ ! "$ROUND_NUMBER" =~ ^[1-9][0-9]*$ ]]; then
    echo "Usage: $0 auto <round-number>" >&2
    echo "Environment: TW3_REPLAY_SEASON, TW3_REPLAY_DATABASE, AUTOMATION_TOKEN, APP_BASE_URL" >&2
    exit 1
fi

# Use default dev token if not set (this is a development/testing script)
AUTOMATION_TOKEN="${AUTOMATION_TOKEN:-tw4_automation_dev_12345}"

mysql_query() {
    local database="$1"
    local sql="$2"

    docker compose exec -T db sh -lc \
        'mysql -u root -p"$MYSQL_ROOT_PASSWORD" --batch --raw --skip-column-names "$1" -e "$2"' \
        -- "$database" "$sql"
}

# Verify TW3 source is available
assert_source_available() {
    local found
    found="$(mysql_query information_schema \
        "SELECT COUNT(*) FROM SCHEMATA WHERE SCHEMA_NAME = '$SOURCE_DATABASE'")"
    if [[ "$found" != "1" ]]; then
        echo "Replay source $SOURCE_DATABASE is not available in Docker." >&2
        echo "Run scripts/import-tw3-replay-source.sh first." >&2
        exit 1
    fi
}

# ============================================================================
# STAGE 1: Start Round via API
# ============================================================================
start_round_via_api() {
    local round_num="$1"
    
    echo "=== STAGE 1: Starting Round $round_num via API ==="
    
    # Get round metadata from TW3
    local metadata
    metadata="$(mysql_query "$SOURCE_DATABASE" \
        "SELECT date_played, name_course
         FROM hist_round
         WHERE ident_season = '$SEASON' AND number_round = $round_num
         LIMIT 1")"
    
    if [[ -z "$metadata" ]]; then
        echo "ERROR: Round $round_num not found in TW3" >&2
        return 1
    fi
    
    # Parse metadata
    local round_date course_name
    IFS=$'\t' read -r round_date course_name <<< "$metadata"
    
    echo "  Round date: $round_date"
    echo "  Course: $course_name"
    
    # Get course_played_id from TW4
    local course_played_id
    course_played_id="$(mysql_query TW4_base \
        "SELECT row_id FROM course_played 
         WHERE name_course LIKE CONCAT('%', '$course_name', '%')
         LIMIT 1")" || true
    
    if [[ -z "$course_played_id" ]]; then
        echo "WARNING: Course '$course_name' not found, using NULL" >&2
        course_played_id="null"
    else
        echo "  Course ID in TW4: $course_played_id"
    fi
    
    # Build JSON payload
    local json_payload
    json_payload=$(cat <<EOF
{
  "number_round": $round_num,
  "round_date": "$round_date",
  "course_played_id": $course_played_id
}
EOF
)
    
    # Call start-round API endpoint (path-based routing)
    local response http_code body
    response="$(curl -s -X POST "$APP_BASE_URL/api/automation/start-round" \
        -H "X-Automation-Token: $AUTOMATION_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$json_payload" \
        -w "\n%{http_code}")"
    
    http_code="$(echo "$response" | tail -n1)"
    body="$(echo "$response" | head -n-1)"
    
    if [[ "$http_code" != "200" ]]; then
        echo "ERROR: Start round API returned HTTP $http_code" >&2
        echo "Response: $body" >&2
        return 1
    fi
    
    local success
    success="$(echo "$body" | jq -r '.success // false')"
    if [[ "$success" != "true" ]]; then
        local message
        message="$(echo "$body" | jq -r '.message // "Unknown error"')"
        echo "ERROR: $message" >&2
        return 1
    fi
    
    echo "✓ Round started via API (cleared live cards, staged best-five/eclectic)"
}

# ============================================================================
# STAGE 2: Update Players (Create New, Adjust Handicaps)
# ============================================================================
update_players() {
    local round_num="$1"
    
    echo ""
    echo "=== STAGE 2: Updating Players ==="
    
    # Create new players from TW3 that don't exist in TW4
    local new_players
    new_players="$(mysql_query "$SOURCE_DATABASE" \
        "SELECT DISTINCT source.ident_player
         FROM hist_card source
         LEFT JOIN TW4_base.roster roster
           ON roster.player_identifier COLLATE utf8mb4_general_ci = source.ident_player
         WHERE source.ident_season = '$SEASON'
           AND source.number_round <= $round_num
           AND roster.player_identifier IS NULL
         GROUP BY source.ident_player")" || true
    
    if [[ -n "$new_players" ]]; then
        echo "  Creating new players:"
        while IFS= read -r player_id; do
            [[ -z "$player_id" ]] && continue

            # Look up full name and gender from TW3 source player table
            local player_row first_name last_name alias_val gender_val
            player_row="$(mysql_query "$SOURCE_DATABASE" \
                "SELECT name_first, name_last,
                        CASE WHEN ident_public IS NOT NULL AND TRIM(ident_public) <> ''
                                  AND ident_public <> ident_player
                             THEN ident_public ELSE NULL END AS alias,
                        IF(UPPER(gender) = 'F', 'female', 'male') AS gender
                 FROM player
                 WHERE ident_player = '$player_id'
                 LIMIT 1")" || true

            if [[ -n "$player_row" ]]; then
                IFS=$'\t' read -r first_name last_name alias_val gender_val <<< "$player_row"
                # mysql --batch prints SQL NULL as the literal text "NULL" — treat it as empty
                [[ "$alias_val" == "NULL" ]] && alias_val=""
            else
                # Fallback: split identifier (e.g. "JonG" -> "Jon" "G") — TW3 source unavailable
                alias_val=""
                gender_val="male"
                if [[ "$player_id" =~ ^([A-Za-z]+)([A-Z][a-z]*)$ ]]; then
                    first_name="${BASH_REMATCH[1]}"
                    last_name="${BASH_REMATCH[2]}"
                else
                    first_name="$player_id"
                    last_name=""
                fi
            fi
            
            # Check if already exists (in case of race)
            local exists
            exists="$(mysql_query TW4_base \
                "SELECT COUNT(*) FROM roster WHERE player_identifier = '$player_id'")"
            
            if [[ "$exists" == "0" ]]; then
                mysql_query TW4_base \
                    "INSERT INTO roster (player_identifier, first_name, last_name, alias, gender, status, handicap, date_first_played, created_at, updated_by)
                     VALUES ('$player_id', '$first_name', '$last_name', $([ -n "$alias_val" ] && echo "'$alias_val'" || echo "NULL"), '$gender_val', 'active', 18, CURDATE(), NOW(), 'automation')"
                echo "    ✓ Created $player_id ($first_name $last_name, default H:18)"
            fi
        done <<< "$new_players"
    fi
    
    # Adjust handicaps to start-of-round from TW3
    echo "  Adjusting handicaps to start-of-round state:"
    local adjustments
    adjustments="$(mysql_query "$SOURCE_DATABASE" \
        "SELECT source_card.ident_player,
                source_card.handicap AS tw3_handicap,
                roster.handicap AS tw4_handicap
         FROM hist_card source_card
         JOIN TW4_base.roster roster
           ON roster.player_identifier COLLATE utf8mb4_general_ci = source_card.ident_player
         WHERE source_card.ident_season = '$SEASON'
           AND source_card.number_round = $round_num
           AND roster.handicap <> source_card.handicap")" || true
    
    if [[ -n "$adjustments" ]]; then
        while IFS=$'\t' read -r player_id tw3_handicap tw4_handicap; do
            [[ -z "$player_id" ]] && continue
            
            mysql_query TW4_base \
                "UPDATE roster SET handicap = $tw3_handicap, updated_by = 'automation' WHERE player_identifier = '$player_id'"
            
            echo "    ✓ $player_id: $tw4_handicap -> $tw3_handicap"
        done <<< "$adjustments"
    else
        echo "    (All handicaps already match)"
    fi
    
    # Sync display aliases from TW3 (player.ident_public) where it differs from the identifier
    echo "  Syncing aliases from TW3 public names:"
    mysql_query TW4_base \
        "UPDATE roster r
         JOIN $SOURCE_DATABASE.player p
           ON p.ident_player COLLATE utf8mb4_general_ci = r.player_identifier
         SET r.alias = p.ident_public,
             r.updated_by = 'automation'
         WHERE p.ident_public IS NOT NULL
           AND TRIM(p.ident_public) <> ''
           AND p.ident_public COLLATE utf8mb4_general_ci <> p.ident_player
           AND (r.alias IS NULL OR r.alias COLLATE utf8mb4_general_ci <> p.ident_public)"
    echo "    ✓ Aliases synced"
    
    echo "✓ Players updated"
}

# ============================================================================
# STAGE 3: Load Cards (Existing Logic)
# ============================================================================
load_cards() {
    local round_num="$1"
    
    echo ""
    echo "=== STAGE 3: Loading Cards ==="
    
    # Run existing load logic from tw3-round-replay.sh
    bash scripts/tw3-round-replay.sh load "$round_num"
}

# ============================================================================
# STAGE 3.5: Present Results via API
# ============================================================================
present_results_via_api() {
    echo "=== STAGE 3.5: Presenting Results via API ==="
    
    local response http_code body
    response="$(curl -s -X POST "$APP_BASE_URL/api/automation/present-results" \
        -H "X-Automation-Token: $AUTOMATION_TOKEN" \
        -H "Content-Type: application/json" \
        -w "\n%{http_code}")"
    
    http_code="$(echo "$response" | tail -n1)"
    body="$(echo "$response" | head -n-1)"
    
    if [[ "$http_code" != "200" ]]; then
        echo "ERROR: Present results API returned HTTP $http_code" >&2
        echo "Response: $body" >&2
        return 1
    fi
    
    local success
    success="$(echo "$body" | jq -r '.success // false')"
    if [[ "$success" != "true" ]]; then
        local message
        message="$(echo "$body" | jq -r '.message // "Unknown error"')"
        echo "ERROR: $message" >&2
        return 1
    fi
    
    echo "✓ Results presented (workflow_step = results_presented)"
}

# ============================================================================
finish_round_via_api() {
    local round_num="$1"
    
    echo ""
    echo "=== STAGE 4: Finishing Round via API ==="
    
    local response
    response="$(curl -s -X POST "$APP_BASE_URL/api/automation/finish-round" \
        -H "X-Automation-Token: $AUTOMATION_TOKEN" \
        -H "Content-Type: application/json" \
        -w "\n%{http_code}")"
    
    local http_code body
    http_code="$(echo "$response" | tail -n1)"
    body="$(echo "$response" | head -n-1)"
    
    if [[ "$http_code" != "200" ]]; then
        echo "ERROR: Finish round API returned HTTP $http_code" >&2
        echo "Response: $body" >&2
        return 1
    fi
    
    local success
    success="$(echo "$body" | jq -r '.success // false')"
    if [[ "$success" != "true" ]]; then
        local message
        message="$(echo "$body" | jq -r '.message // "Unknown error"')"
        echo "ERROR: $message" >&2
        return 1
    fi
    
    echo "✓ Round finished via API (all workflows executed: handicaps, best-five, eclectic, export)"
}

# ============================================================================
# STAGE 3.7: Import Results (Place / Twos / Closest-to-Pin)
# ============================================================================
# Loads the round's money (Place), Twos and Closest-to-Pin results from TW3 into
# TW4_live.results BEFORE the round is finished, so the finish workflow archives
# them into TW4_history.results and the snapshot export includes them.
import_results_to_live() {
    local round_num="$1"
    
    echo ""
    echo "=== STAGE 3.7: Importing Results (Place/Twos/C_P) ==="
    
    # Clear any stale live results for a clean, idempotent import
    mysql_query TW4_live "DELETE FROM results"
    
    mysql_query TW4_live \
        "INSERT INTO results
             (type_result, number_result, player_identifier, value_result, updated_by, updated_ts)
         SELECT type_result, number_result, ident_player, value_result, 'automation', NOW()
         FROM $SOURCE_DATABASE.hist_result
         WHERE ident_season = '$SEASON'
           AND number_round = $round_num
           AND type_result IN ('Place', 'Twos', 'C_P')"
    
    local imported
    imported="$(mysql_query TW4_live "SELECT COUNT(*) FROM results")" || imported="0"
    echo "    ✓ Imported $imported result rows (Place/Twos/C_P)"
    echo "✓ Results imported"
}

# ============================================================================
# STAGE 6: Compare Results
# ============================================================================
compare_results() {
    local round_num="$1"
    
    echo ""
    echo "=== STAGE 6: Comparing Results Against TW3 ==="
    
    bash scripts/tw3-round-replay.sh compare "$round_num"
}

# ============================================================================
# MAIN
# ============================================================================

echo "TW4 Automation: Full Season Replication"
echo "========================================"
echo "Season: $SEASON"
echo "Round: $ROUND_NUMBER"
echo "Source: $SOURCE_DATABASE"
echo ""

assert_source_available

if ! start_round_via_api "$ROUND_NUMBER"; then
    echo "FAILED at Stage 1: Could not start round" >&2
    exit 1
fi

if ! update_players "$ROUND_NUMBER"; then
    echo "FAILED at Stage 2: Could not update players" >&2
    exit 1
fi

if ! load_cards "$ROUND_NUMBER"; then
    echo "FAILED at Stage 3: Could not load cards" >&2
    exit 1
fi

if ! present_results_via_api; then
    echo "FAILED at Stage 3.5: Could not present results" >&2
    exit 1
fi

if ! import_results_to_live "$ROUND_NUMBER"; then
    echo "FAILED at Stage 3.7: Could not import results" >&2
    exit 1
fi

if ! finish_round_via_api "$ROUND_NUMBER"; then
    echo "FAILED at Stage 4: Could not finish round" >&2
    exit 1
fi

if ! compare_results "$ROUND_NUMBER"; then
    echo "WARNING at Stage 6: Comparison found mismatches (see above)" >&2
    exit 1
fi

echo ""
echo "========================================"
echo "✓ AUTOMATION COMPLETE FOR ROUND $ROUND_NUMBER"
echo "========================================"
