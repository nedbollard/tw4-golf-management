<?php

use App\Services\CardScoringCalculator;

require __DIR__ . '/../../vendor/autoload.php';

$app_title = 'TW4 Test';
$csrf_token = 'test-token';
$csp_nonce = 'test-nonce';
$round = ['round_number' => 1, 'course_name' => 'Test Course'];
$entry = [
    'player' => ['row_id' => 1, 'first_name' => 'Test', 'last_name' => 'Player', 'handicap' => 9],
    'holes' => [],
    'totals' => ['par' => 36, 'score' => null, 'shots' => null, 'net' => null, 'points' => null],
];
for ($hole = 1; $hole <= 9; $hole++) {
    $entry['holes'][] = ['hole' => $hole, 'par' => 4, 'stroke' => $hole, 'score' => null, 'shots' => null, 'net' => null, 'points' => null];
}
$calculationComplete = ($argv[1] ?? '') === 'calculated';
$errors = [];
if ($calculationComplete || ($argv[1] ?? '') === 'invalid') {
    $scores = array_fill(1, 9, '4');
    if (!$calculationComplete) {
        $scores[9] = '';
    }
    $entry = (new CardScoringCalculator())->calculate($entry, $scores);
    $errors = $entry['errors'];
}
require __DIR__ . '/../../src/Views/scores/enter-card.php';
