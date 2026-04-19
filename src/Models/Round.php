<?php

namespace App\Models;

class Round
{
    public int $roundId;
    public string $seasonType;
    public int $roundNumber;
    public string $roundDate;
    public ?int $coursePlayedId;
    public ?string $notes;
    public string $workflowStep;

    public static function fromArray(array $row): self
    {
        $r = new self();
        $r->roundId = (int) ($row['round_id'] ?? 0);
        $r->seasonType = (string) ($row['season_type'] ?? 'twilight');
        $r->roundNumber = (int) ($row['round_number'] ?? 0);
        $r->roundDate = (string) ($row['round_date'] ?? '');
        $r->coursePlayedId = isset($row['course_played_id']) ? (int) $row['course_played_id'] : null;
        $r->notes = $row['notes'] ?? null;
        $r->workflowStep = (string) ($row['workflow_step'] ?? 'not_started');
        return $r;
    }
}
