<?php
require '/var/www/html/vendor/autoload.php';

$app = App\Core\Application::getInstance();
$db = $app->getDatabase();

// Check if round 1 exists
$round = $db->fetchOne(
    'SELECT season_year, number_round, round_date, course_played_id FROM TW4_live.round WHERE season_year = ? AND number_round = ? LIMIT 1',
    ['25_26', 1]
);

if (!$round) {
    echo "Round 25_26/1 not found\n";
    exit(1);
}

echo "Round: " . $round['season_year'] . " / " . $round['number_round'] . "\n";
echo "Round date: " . $round['round_date'] . "\n";
echo "Course ID: " . $round['course_played_id'] . "\n";

// Check eclectic data
$whites = $db->fetchAll(
    'SELECT COUNT(*) as cnt FROM TW4_live.eclectic_scores WHERE season_year = ? AND ident_eclectic = ?',
    ['25_26', 'Whites']
);

$blues = $db->fetchAll(
    'SELECT COUNT(*) as cnt FROM TW4_live.eclectic_scores WHERE season_year = ? AND ident_eclectic = ?',
    ['25_26', 'Blues']
);

echo "Whites eclectic records: " . ($whites[0]['cnt'] ?? 0) . "\n";
echo "Blues eclectic records: " . ($blues[0]['cnt'] ?? 0) . "\n";

// Now try the export
try {
    $export = new App\Services\SnapshotExportService($db);
    $result = $export->exportRoundSnapshots('25_26', 1, true);
    echo "Export succeeded!\n";
    echo "Files written: " . count($result['written'] ?? []) . "\n";
    foreach ($result['written'] as $file) {
        echo "  - $file\n";
    }
} catch (\Throwable $e) {
    echo "Export failed: " . $e->getMessage() . "\n";
    echo "Exception: " . get_class($e) . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
