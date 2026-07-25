<?php

namespace App\Services;

use App\Core\Database;

class HandicapReferenceService
{
    public function __construct(private Database $db)
    {
    }

    public function getCurrentTees(int $clubNumber): array
    {
        return $this->db->fetchAll(
            'SELECT h.row_id, h.club_number, h.gender, h.tee_name,
                    h.course_rating, h.par, h.slope, h.handicap_allowance,
                    h.effective_from, h.effective_to
             FROM TW4_base.handicap_reference_tees h
             WHERE h.club_number = ?
               AND h.effective_from <= CURRENT_DATE
               AND (h.effective_to IS NULL OR h.effective_to >= CURRENT_DATE)
               AND NOT EXISTS (
                   SELECT 1
                   FROM TW4_base.handicap_reference_tees newer
                   WHERE newer.club_number = h.club_number
                     AND newer.gender = h.gender
                     AND newer.tee_name = h.tee_name
                     AND newer.effective_from <= CURRENT_DATE
                     AND (newer.effective_to IS NULL OR newer.effective_to >= CURRENT_DATE)
                     AND newer.effective_from > h.effective_from
               )
             ORDER BY h.gender, h.tee_name',
            [$clubNumber]
        );
    }

    public function getTee(int $rowId, int $clubNumber): ?array
    {
        return $this->db->fetchOne(
            'SELECT row_id, club_number, gender, tee_name, course_rating, par,
                    slope, handicap_allowance, effective_from, effective_to
             FROM TW4_base.handicap_reference_tees
             WHERE row_id = ? AND club_number = ?
               AND effective_from <= CURRENT_DATE
               AND (effective_to IS NULL OR effective_to >= CURRENT_DATE)',
            [$rowId, $clubNumber]
        );
    }

    public function calculate(float $index, bool $isPlus, array $tee): array
    {
        $signedIndex = $isPlus ? -abs($index) : abs($index);
        $allowance = ((float) ($tee['handicap_allowance'] ?? 100.0)) / 100;
        $courseHandicap = ($signedIndex * ((float) $tee['slope'] / 113))
            + (float) $tee['course_rating']
            - (int) $tee['par'];
        $published = (int) round($courseHandicap * $allowance, 0, PHP_ROUND_HALF_UP);

        return [
            'published_handicap' => $published,
            'published_display' => $published < 0 ? '+' . abs($published) : (string) $published,
            'tw4_handicap' => max(0, $published),
        ];
    }
}