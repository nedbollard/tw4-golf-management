<?php

namespace App\Services;

class CardScoringCalculator
{
    /**
     * Validate posted scores and enrich the entry data with per-hole and card totals.
     *
     * @param array<string, mixed> $entryData Entry data produced by CardEntryQueryService::buildEntryData().
     * @param array<int|string, string> $postedScores Scores keyed by one-based hole number.
     * @return array<string, mixed> The entry data with errors, calculated hole values, and totals.
     */
    public function calculate(array $entryData, array $postedScores): array
    {
        $errors = [];
        $handicap = max(0, (int) ($entryData['player']['handicap'] ?? 0));

        $totalScore = 0;
        $totalShots = 0;
        $totalNet = 0;
        $totalPoints = 0;

        foreach ($entryData['holes'] as $idx => &$hole) {
            $holeNo = $idx + 1;
            $raw = $postedScores[$holeNo] ?? '';
            $raw = is_string($raw) ? trim($raw) : $raw;

            if ($raw === '' || $raw === null) {
                $errors[] = "Hole {$holeNo}: score is required.";
                $hole['score'] = null;
                $hole['shots'] = null;
                $hole['net'] = null;
                $hole['points'] = null;
                continue;
            }

            $score = null;
            if (is_string($raw) && strcasecmp($raw, 'x') === 0) {
                $score = 10;
            } elseif (is_string($raw) && strlen($raw) === 1 && ctype_digit($raw) && $raw !== '0') {
                $score = (int) $raw;
            }

            if ($score === null) {
                $errors[] = "Hole {$holeNo}: score must be 1-9 or X.";
                $hole['score'] = $score;
                $hole['shots'] = null;
                $hole['net'] = null;
                $hole['points'] = null;
                continue;
            }

            $strokeIndex = (int) ($hole['stroke'] ?? 18);
            $shots = intdiv($handicap, 18);
            $shots += ($strokeIndex <= ($handicap % 18)) ? 1 : 0;

            $net = $score - $shots;
            $points = max(0, 2 + ((int) $hole['par'] - $net));

            $hole['score'] = $score;
            $hole['shots'] = $shots;
            $hole['net'] = $net;
            $hole['points'] = $points;

            $totalScore += $score;
            $totalShots += $shots;
            $totalNet += $net;
            $totalPoints += $points;
        }
        unset($hole);

        $entryData['errors'] = $errors;
        $entryData['totals']['score'] = empty($errors) ? $totalScore : null;
        $entryData['totals']['shots'] = empty($errors) ? $totalShots : null;
        $entryData['totals']['net'] = empty($errors) ? $totalNet : null;
        $entryData['totals']['points'] = empty($errors) ? $totalPoints : null;

        return $entryData;
    }
}