#!/usr/bin/env bash

set -Eeuo pipefail

SEASON="${TW3_REPLAY_SEASON:-25_26}"
SOURCE_DATABASE="${TW3_REPLAY_DATABASE:-TW3_replay_25_26}"
ACTION="${1:-}"
ROUND_NUMBER="${2:-}"

if [[ ! "$SOURCE_DATABASE" =~ ^[A-Za-z0-9_]+$ ]]; then
    echo "Invalid replay database name: $SOURCE_DATABASE" >&2
    exit 1
fi

if [[ ! "$ACTION" =~ ^(preflight|load|compare)$ ]] || [[ ! "$ROUND_NUMBER" =~ ^[1-9][0-9]*$ ]]; then
    echo "Usage: $0 {preflight|load|compare} <round-number>" >&2
    exit 1
fi

mysql_query() {
    local database="$1"
    local sql="$2"

    docker compose exec -T db sh -lc \
        'mysql -u root -p"$MYSQL_ROOT_PASSWORD" --batch --raw --skip-column-names "$1" -e "$2"' \
        -- "$database" "$sql"
}

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

preflight_checks_sql() {
    cat <<SQL
SELECT 'live_round_identity', COUNT(*)
FROM TW4_live.round
WHERE season_year <> '$SEASON'
   OR number_round <> $ROUND_NUMBER
   OR workflow_step <> 'card_entry_open';

SELECT 'round_date', COUNT(*)
FROM TW4_live.round live_round
JOIN $SOURCE_DATABASE.hist_round source_round
  ON source_round.ident_season = '$SEASON'
 AND source_round.number_round = $ROUND_NUMBER
WHERE live_round.round_date <> source_round.date_played;

SELECT 'course', COUNT(*)
FROM TW4_live.round live_round
JOIN TW4_base.course_played course ON course.row_id = live_round.course_played_id
JOIN $SOURCE_DATABASE.hist_round source_round
  ON source_round.ident_season = '$SEASON'
 AND source_round.number_round = $ROUND_NUMBER
WHERE LOWER(course.name_course) COLLATE utf8mb4_general_ci
  <> LOWER(source_round.name_course) COLLATE utf8mb4_general_ci;

SELECT 'source_round_count', IF(COUNT(*) = 1, 0, 1)
FROM $SOURCE_DATABASE.hist_round
WHERE ident_season = '$SEASON' AND number_round = $ROUND_NUMBER;

SELECT 'live_tables_empty',
       (SELECT COUNT(*) FROM TW4_live.card)
       + (SELECT COUNT(*) FROM TW4_live.card_by_hole);

SELECT 'card_count', ABS(
    (SELECT count_entries FROM $SOURCE_DATABASE.hist_round
     WHERE ident_season = '$SEASON' AND number_round = $ROUND_NUMBER)
    -
    (SELECT COUNT(*) FROM $SOURCE_DATABASE.hist_card
     WHERE ident_season = '$SEASON' AND number_round = $ROUND_NUMBER)
);

SELECT 'hole_count', ABS(
    (SELECT COUNT(*) * 9 FROM $SOURCE_DATABASE.hist_card
     WHERE ident_season = '$SEASON' AND number_round = $ROUND_NUMBER)
    -
    (SELECT COUNT(*) FROM $SOURCE_DATABASE.hist_card_byhole
     WHERE ident_season = '$SEASON' AND number_round = $ROUND_NUMBER)
);

SELECT 'card_hole_totals', COUNT(*)
FROM $SOURCE_DATABASE.hist_card source_card
JOIN (
    SELECT ident_player, SUM(score) AS score, SUM(points) AS points
    FROM $SOURCE_DATABASE.hist_card_byhole
    WHERE ident_season = '$SEASON' AND number_round = $ROUND_NUMBER
    GROUP BY ident_player
) hole_totals ON hole_totals.ident_player = source_card.ident_player
WHERE source_card.ident_season = '$SEASON'
  AND source_card.number_round = $ROUND_NUMBER
  AND (source_card.score <> hole_totals.score OR source_card.points <> hole_totals.points);

SELECT 'existing_handicaps', COUNT(*)
FROM $SOURCE_DATABASE.hist_card source_card
JOIN TW4_base.roster roster
  ON roster.player_identifier COLLATE utf8mb4_general_ci = source_card.ident_player
WHERE source_card.ident_season = '$SEASON'
  AND source_card.number_round = $ROUND_NUMBER
  AND COALESCE(roster.handicap, 0) <> source_card.handicap
  AND (
      EXISTS (
          SELECT 1
          FROM $SOURCE_DATABASE.hist_card earlier_card
          WHERE earlier_card.ident_season = source_card.ident_season
            AND earlier_card.ident_player = source_card.ident_player
            AND earlier_card.number_round < source_card.number_round
      )
      OR NOT EXISTS (
          SELECT 1
          FROM $SOURCE_DATABASE.hist_handicap initialization
          WHERE initialization.ident_season = source_card.ident_season
            AND initialization.ident_player = source_card.ident_player
            AND initialization.number_round = 0
            AND initialization.handicap_to = source_card.handicap
      )
  );

SELECT 'missing_prior_players', COUNT(*)
FROM $SOURCE_DATABASE.hist_card source_card
LEFT JOIN TW4_base.roster roster
  ON roster.player_identifier COLLATE utf8mb4_general_ci = source_card.ident_player
WHERE source_card.ident_season = '$SEASON'
  AND source_card.number_round = $ROUND_NUMBER
  AND roster.row_id IS NULL
  AND EXISTS (
      SELECT 1
      FROM $SOURCE_DATABASE.hist_card earlier_card
      WHERE earlier_card.ident_season = source_card.ident_season
        AND earlier_card.ident_player = source_card.ident_player
        AND earlier_card.number_round < source_card.number_round
  );

SELECT 'new_player_metadata', COUNT(*)
FROM $SOURCE_DATABASE.hist_card source_card
LEFT JOIN TW4_base.roster roster
  ON roster.player_identifier COLLATE utf8mb4_general_ci = source_card.ident_player
LEFT JOIN $SOURCE_DATABASE.player source_player
  ON source_player.ident_player = source_card.ident_player
WHERE source_card.ident_season = '$SEASON'
  AND source_card.number_round = $ROUND_NUMBER
  AND roster.row_id IS NULL
  AND source_player.row_id IS NULL;

SELECT 'handicap_method', COUNT(*)
FROM TW4_base.config_application target_config
JOIN $SOURCE_DATABASE.config_basic source_config
  ON source_config.config_name = 'handicap_method'
WHERE target_config.config_name = 'handicap_method'
  AND NOT (
      LOWER(target_config.config_value_string) = 'modern'
      AND UPPER(source_config.config_value_string) = 'M'
  );
SQL
}

show_preflight_details() {
    mysql_query TW4_base "
SELECT source_card.ident_player,
       source_card.handicap AS tw3_handicap,
       roster.handicap AS tw4_handicap,
       CASE
           WHEN roster.row_id IS NULL THEN 'new_player'
           WHEN roster.handicap <> source_card.handicap
                AND NOT EXISTS (
                    SELECT 1
                    FROM $SOURCE_DATABASE.hist_card earlier_card
                    WHERE earlier_card.ident_season = source_card.ident_season
                      AND earlier_card.ident_player = source_card.ident_player
                      AND earlier_card.number_round < source_card.number_round
                )
                AND EXISTS (
                    SELECT 1
                    FROM $SOURCE_DATABASE.hist_handicap initialization
                    WHERE initialization.ident_season = source_card.ident_season
                      AND initialization.ident_player = source_card.ident_player
                      AND initialization.number_round = 0
                      AND initialization.handicap_to = source_card.handicap
                ) THEN 'season_debut_initialization'
           WHEN roster.handicap <> source_card.handicap THEN 'handicap_mismatch'
           ELSE 'ready'
       END AS status
FROM $SOURCE_DATABASE.hist_card source_card
LEFT JOIN TW4_base.roster roster
  ON roster.player_identifier COLLATE utf8mb4_general_ci = source_card.ident_player
WHERE source_card.ident_season = '$SEASON'
  AND source_card.number_round = $ROUND_NUMBER
ORDER BY source_card.ident_player;"
}

run_preflight() {
    local results
    local failures

    results="$(mysql_query TW4_base "$(preflight_checks_sql)")"
    printf '%-24s %s\n' CHECK FAILURES
    printf '%s\n' "$results" | awk -F '\t' '{ printf "%-24s %s\n", $1, $2 }'

    failures="$(printf '%s\n' "$results" | awk -F '\t' '{ total += $2 } END { print total + 0 }')"
    echo
    show_preflight_details

    if [[ "$failures" != "0" ]]; then
        echo "Preflight failed with $failures issue(s); no data was changed." >&2
        return 1
    fi

    echo "Preflight passed for $SEASON round $ROUND_NUMBER."
}

load_round() {
    run_preflight

    mysql_query TW4_base "
START TRANSACTION;

INSERT INTO TW4_base.roster
    (player_identifier, first_name, last_name, alias, gender, status,
     handicap, date_first_played, updated_by)
SELECT source_card.ident_player,
       source_player.name_first,
       source_player.name_last,
       CASE
           WHEN source_player.ident_public IS NULL
                OR TRIM(source_player.ident_public) = ''
                OR source_player.ident_public = source_player.ident_player
               THEN NULL
           ELSE source_player.ident_public
       END,
       IF(UPPER(source_player.gender) = 'F', 'female', 'male'),
       'active',
       source_card.handicap,
       source_round.date_played,
       'tw3_replay'
FROM $SOURCE_DATABASE.hist_card source_card
JOIN $SOURCE_DATABASE.player source_player
  ON source_player.ident_player = source_card.ident_player
JOIN $SOURCE_DATABASE.hist_round source_round
  ON source_round.ident_season = source_card.ident_season
 AND source_round.number_round = source_card.number_round
LEFT JOIN TW4_base.roster roster
  ON roster.player_identifier COLLATE utf8mb4_general_ci = source_card.ident_player
WHERE source_card.ident_season = '$SEASON'
  AND source_card.number_round = $ROUND_NUMBER
  AND roster.row_id IS NULL;

INSERT INTO TW4_base.handicap_audit
    (season_year, number_round, row_id_player, handicap_previous, handicap_new,
     handicap_source, points_scored, points_effective, reason, updated_by)
SELECT '$SEASON',
       0,
       roster.row_id,
       roster.handicap,
       source_card.handicap,
       'system_import',
       0,
       0,
       'tw3_replay_round_0_initialization',
       'tw3_replay'
FROM $SOURCE_DATABASE.hist_card source_card
JOIN TW4_base.roster roster
  ON roster.player_identifier COLLATE utf8mb4_general_ci = source_card.ident_player
JOIN $SOURCE_DATABASE.hist_handicap initialization
  ON initialization.ident_season = source_card.ident_season
 AND initialization.ident_player = source_card.ident_player
 AND initialization.number_round = 0
 AND initialization.handicap_to = source_card.handicap
WHERE source_card.ident_season = '$SEASON'
  AND source_card.number_round = $ROUND_NUMBER
  AND roster.handicap <> source_card.handicap
  AND NOT EXISTS (
      SELECT 1
      FROM $SOURCE_DATABASE.hist_card earlier_card
      WHERE earlier_card.ident_season = source_card.ident_season
        AND earlier_card.ident_player = source_card.ident_player
        AND earlier_card.number_round < source_card.number_round
  );

UPDATE TW4_base.roster roster
JOIN $SOURCE_DATABASE.hist_card source_card
  ON roster.player_identifier COLLATE utf8mb4_general_ci = source_card.ident_player
JOIN $SOURCE_DATABASE.hist_handicap initialization
  ON initialization.ident_season = source_card.ident_season
 AND initialization.ident_player = source_card.ident_player
 AND initialization.number_round = 0
 AND initialization.handicap_to = source_card.handicap
SET roster.handicap = source_card.handicap,
    roster.updated_by = 'tw3_replay'
WHERE source_card.ident_season = '$SEASON'
  AND source_card.number_round = $ROUND_NUMBER
  AND roster.handicap <> source_card.handicap
  AND NOT EXISTS (
      SELECT 1
      FROM $SOURCE_DATABASE.hist_card earlier_card
      WHERE earlier_card.ident_season = source_card.ident_season
        AND earlier_card.ident_player = source_card.ident_player
        AND earlier_card.number_round < source_card.number_round
  );

INSERT INTO TW4_live.card
    (row_id_player, handicap_applied, score, points, handicap_updated, updated_by)
SELECT roster.row_id,
       source_card.handicap,
       source_card.score,
       source_card.points,
       source_card.handicap,
       'tw3_replay'
FROM $SOURCE_DATABASE.hist_card source_card
JOIN TW4_base.roster roster
  ON roster.player_identifier COLLATE utf8mb4_general_ci = source_card.ident_player
WHERE source_card.ident_season = '$SEASON'
  AND source_card.number_round = $ROUND_NUMBER;

INSERT INTO TW4_live.card_by_hole
    (row_id_card, hole, score, shots, points, updated_by)
SELECT target_card.row_id,
       source_hole.hole,
       source_hole.score,
       source_hole.shots,
       source_hole.points,
       'tw3_replay'
FROM $SOURCE_DATABASE.hist_card_byhole source_hole
JOIN TW4_base.roster roster
  ON roster.player_identifier COLLATE utf8mb4_general_ci = source_hole.ident_player
JOIN TW4_live.card target_card
  ON target_card.row_id_player = roster.row_id
WHERE source_hole.ident_season = '$SEASON'
  AND source_hole.number_round = $ROUND_NUMBER;

UPDATE TW4_base.roster roster
JOIN TW4_live.card target_card ON target_card.row_id_player = roster.row_id
SET roster.status = 'scored', roster.updated_by = 'tw3_replay';

UPDATE TW4_live.round
SET card_count = (SELECT COUNT(*) FROM TW4_live.card),
    updated_by = 'tw3_replay'
WHERE season_year = '$SEASON'
  AND number_round = $ROUND_NUMBER
  AND workflow_step = 'card_entry_open';

COMMIT;

SELECT COUNT(*) AS cards_loaded FROM TW4_live.card;
SELECT COUNT(*) AS holes_loaded FROM TW4_live.card_by_hole;"

    echo "Round $ROUND_NUMBER cards loaded. Present and finish the round in TW4, then run:"
    echo "  $0 compare $ROUND_NUMBER"
}

compare_round() {
    local results
    local failures
    local corrupt_note

    # A TW3 haggle_eclectic row is provably corrupt when it stores a per-hole
    # score better (lower) than the player ever actually recorded up to that
    # round. This is caused by a TW3 bug in eclectic_update.php (eclectic_get_card
    # reads unfiltered live-card state and never re-derives from card history, so
    # amended cards leave phantom low scores). These rows are excluded from the
    # eclectic mismatch count and reported separately as info_eclectic_tw3_corrupt.
    # COALESCE(..., source_eclectic.holeX) makes each hole NULL-safe: when the
    # player has no card for that hole, the comparison collapses to false.
    local corrupt_clause="
       source_eclectic.holeA < COALESCE((SELECT MIN(c.score) FROM $SOURCE_DATABASE.hist_card_byhole c WHERE c.ident_season = '$SEASON' AND c.ident_player = latest.ident_player AND c.number_round <= latest.number_round AND c.hole = 1 AND c.score > 0), source_eclectic.holeA)
    OR source_eclectic.holeB < COALESCE((SELECT MIN(c.score) FROM $SOURCE_DATABASE.hist_card_byhole c WHERE c.ident_season = '$SEASON' AND c.ident_player = latest.ident_player AND c.number_round <= latest.number_round AND c.hole = 2 AND c.score > 0), source_eclectic.holeB)
    OR source_eclectic.holeC < COALESCE((SELECT MIN(c.score) FROM $SOURCE_DATABASE.hist_card_byhole c WHERE c.ident_season = '$SEASON' AND c.ident_player = latest.ident_player AND c.number_round <= latest.number_round AND c.hole = 3 AND c.score > 0), source_eclectic.holeC)
    OR source_eclectic.holeD < COALESCE((SELECT MIN(c.score) FROM $SOURCE_DATABASE.hist_card_byhole c WHERE c.ident_season = '$SEASON' AND c.ident_player = latest.ident_player AND c.number_round <= latest.number_round AND c.hole = 4 AND c.score > 0), source_eclectic.holeD)
    OR source_eclectic.holeE < COALESCE((SELECT MIN(c.score) FROM $SOURCE_DATABASE.hist_card_byhole c WHERE c.ident_season = '$SEASON' AND c.ident_player = latest.ident_player AND c.number_round <= latest.number_round AND c.hole = 5 AND c.score > 0), source_eclectic.holeE)
    OR source_eclectic.holeF < COALESCE((SELECT MIN(c.score) FROM $SOURCE_DATABASE.hist_card_byhole c WHERE c.ident_season = '$SEASON' AND c.ident_player = latest.ident_player AND c.number_round <= latest.number_round AND c.hole = 6 AND c.score > 0), source_eclectic.holeF)
    OR source_eclectic.holeG < COALESCE((SELECT MIN(c.score) FROM $SOURCE_DATABASE.hist_card_byhole c WHERE c.ident_season = '$SEASON' AND c.ident_player = latest.ident_player AND c.number_round <= latest.number_round AND c.hole = 7 AND c.score > 0), source_eclectic.holeG)
    OR source_eclectic.holeH < COALESCE((SELECT MIN(c.score) FROM $SOURCE_DATABASE.hist_card_byhole c WHERE c.ident_season = '$SEASON' AND c.ident_player = latest.ident_player AND c.number_round <= latest.number_round AND c.hole = 8 AND c.score > 0), source_eclectic.holeH)
    OR source_eclectic.holeI < COALESCE((SELECT MIN(c.score) FROM $SOURCE_DATABASE.hist_card_byhole c WHERE c.ident_season = '$SEASON' AND c.ident_player = latest.ident_player AND c.number_round <= latest.number_round AND c.hole = 9 AND c.score > 0), source_eclectic.holeI)"

    results="$(mysql_query TW4_base "
SELECT 'finished_history', IF(COUNT(*) = 1, 0, 1)
FROM TW4_history.round
WHERE season_year = '$SEASON' AND number_round = $ROUND_NUMBER;

SELECT 'handicap', COUNT(*)
FROM $SOURCE_DATABASE.hist_card source_card
JOIN TW4_base.roster roster
  ON roster.player_identifier COLLATE utf8mb4_general_ci = source_card.ident_player
LEFT JOIN $SOURCE_DATABASE.hist_handicap source_handicap
  ON source_handicap.ident_season = source_card.ident_season
 AND source_handicap.number_round = source_card.number_round
 AND source_handicap.ident_player = source_card.ident_player
WHERE source_card.ident_season = '$SEASON'
  AND source_card.number_round = $ROUND_NUMBER
  AND roster.handicap <> COALESCE(source_handicap.handicap_to, source_card.handicap);

SELECT 'best_five', COUNT(*)
FROM (
    SELECT latest.ident_player
    FROM (
        SELECT best.ident_player, MAX(best.number_round) AS number_round
        FROM $SOURCE_DATABASE.haggle_best_5 best
        WHERE best.ident_season = '$SEASON' AND best.number_round <= $ROUND_NUMBER
        GROUP BY best.ident_player
    ) latest
    JOIN $SOURCE_DATABASE.haggle_best_5 source_best
      ON source_best.ident_season = '$SEASON'
     AND source_best.ident_player = latest.ident_player
     AND source_best.number_round = latest.number_round
    LEFT JOIN TW4_base.roster roster
      ON roster.player_identifier COLLATE utf8mb4_general_ci = latest.ident_player
    LEFT JOIN TW4_live.best_five_scores target_best
      ON target_best.season_year = '$SEASON'
     AND target_best.row_id_player = roster.row_id
    WHERE target_best.row_id IS NULL
       OR target_best.points_total <> source_best.points_total
       OR target_best.points_best_1 <> source_best.best1
       OR target_best.points_best_2 <> source_best.best2
       OR target_best.points_best_3 <> source_best.best3
       OR target_best.points_best_4 <> source_best.best4
       OR target_best.points_best_5 <> source_best.best5

    UNION ALL

    SELECT roster.player_identifier COLLATE utf8mb4_general_ci
    FROM TW4_live.best_five_scores target_best
    JOIN TW4_base.roster roster ON roster.row_id = target_best.row_id_player
    LEFT JOIN $SOURCE_DATABASE.haggle_best_5 source_best
      ON source_best.ident_season = '$SEASON'
     AND source_best.ident_player = roster.player_identifier COLLATE utf8mb4_general_ci
     AND source_best.number_round <= $ROUND_NUMBER
    WHERE target_best.season_year = '$SEASON'
    GROUP BY target_best.row_id, roster.player_identifier
    HAVING COUNT(source_best.row_id) = 0
) mismatches;

SELECT 'eclectic', COUNT(*)
FROM (
    SELECT latest.ident_player
    FROM (
        SELECT eclectic.ident_eclectic, eclectic.ident_player,
               MAX(eclectic.number_round) AS number_round
        FROM $SOURCE_DATABASE.haggle_eclectic eclectic
        WHERE eclectic.ident_season = '$SEASON'
          AND eclectic.number_round <= $ROUND_NUMBER
        GROUP BY eclectic.ident_eclectic, eclectic.ident_player
    ) latest
    JOIN $SOURCE_DATABASE.haggle_eclectic source_eclectic
      ON source_eclectic.ident_season = '$SEASON'
     AND source_eclectic.ident_eclectic = latest.ident_eclectic
     AND source_eclectic.ident_player = latest.ident_player
     AND source_eclectic.number_round = latest.number_round
    LEFT JOIN TW4_base.roster roster
      ON roster.player_identifier COLLATE utf8mb4_general_ci = latest.ident_player
    LEFT JOIN TW4_live.eclectic_scores target_eclectic
      ON target_eclectic.season_year = '$SEASON'
     AND target_eclectic.row_id_player = roster.row_id
       AND LOWER(target_eclectic.ident_eclectic) COLLATE utf8mb4_general_ci
         = LOWER(latest.ident_eclectic)
    WHERE (
       target_eclectic.row_id IS NULL
       OR target_eclectic.score_total <> source_eclectic.score_total
       OR target_eclectic.score_hole_1 <> source_eclectic.holeA
       OR target_eclectic.score_hole_2 <> source_eclectic.holeB
       OR target_eclectic.score_hole_3 <> source_eclectic.holeC
       OR target_eclectic.score_hole_4 <> source_eclectic.holeD
       OR target_eclectic.score_hole_5 <> source_eclectic.holeE
       OR target_eclectic.score_hole_6 <> source_eclectic.holeF
       OR target_eclectic.score_hole_7 <> source_eclectic.holeG
       OR target_eclectic.score_hole_8 <> source_eclectic.holeH
       OR target_eclectic.score_hole_9 <> source_eclectic.holeI
    )
    AND NOT (
$corrupt_clause
    )
) mismatches;

SELECT 'info_eclectic_tw3_corrupt', COUNT(*)
FROM (
    SELECT eclectic.ident_eclectic, eclectic.ident_player,
           MAX(eclectic.number_round) AS number_round
    FROM $SOURCE_DATABASE.haggle_eclectic eclectic
    WHERE eclectic.ident_season = '$SEASON'
      AND eclectic.number_round <= $ROUND_NUMBER
    GROUP BY eclectic.ident_eclectic, eclectic.ident_player
) latest
JOIN $SOURCE_DATABASE.haggle_eclectic source_eclectic
  ON source_eclectic.ident_season = '$SEASON'
 AND source_eclectic.ident_eclectic = latest.ident_eclectic
 AND source_eclectic.ident_player = latest.ident_player
 AND source_eclectic.number_round = latest.number_round
WHERE (
$corrupt_clause
);")"

    printf '%-24s %s\n' CHECK MISMATCHES
    printf '%s\n' "$results" | awk -F '\t' '{ printf "%-24s %s\n", $1, $2 }'
    failures="$(printf '%s\n' "$results" | awk -F '\t' '$1 !~ /^info_/ { total += $2 } END { print total + 0 }')"
    corrupt_note="$(printf '%s\n' "$results" | awk -F '\t' '$1 == "info_eclectic_tw3_corrupt" { print $2 + 0 }')"

    if [[ -n "$corrupt_note" && "$corrupt_note" != "0" ]]; then
        echo "Note: $corrupt_note TW3 eclectic row(s) are known-corrupt source data" \
             "(stored a hole score better than the player ever recorded) and are" \
             "excluded from the mismatch count. This is a TW3 bug, not a TW4 defect." >&2
    fi

    if [[ "$failures" != "0" ]]; then
        echo "Round $ROUND_NUMBER comparison found $failures mismatch(es)." >&2
        return 1
    fi

    echo "Round $ROUND_NUMBER matches TW3 for handicap, best five, and eclectic scores."
}

assert_source_available

case "$ACTION" in
    preflight) run_preflight ;;
    load) load_round ;;
    compare) compare_round ;;
esac