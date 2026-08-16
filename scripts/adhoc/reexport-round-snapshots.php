<?php
/**
 * Re-export HTML report snapshots for one or more completed rounds.
 *
 * Usage (inside app container):
 *   php scripts/reexport-round-snapshots.php <season_year> <round_number> [round_number ...]
 *
 * Examples:
 *   php scripts/reexport-round-snapshots.php 25_26 16
 *   php scripts/reexport-round-snapshots.php 25_26 16 17 18
 *
 * Run via docker compose:
 *   docker compose -f docker-compose.systest.yml exec app \
 *       php scripts/reexport-round-snapshots.php 25_26 16 17 18
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Services\SnapshotExportService;

// ---------------------------------------------------------------------------
// Args
// ---------------------------------------------------------------------------
$args = array_slice($argv, 1);
if (count($args) < 2) {
    fwrite(STDERR, "Usage: php scripts/reexport-round-snapshots.php <season_year> <round_number> [round_number ...]\n");
    fwrite(STDERR, "Example: php scripts/reexport-round-snapshots.php 25_26 16 17 18\n");
    exit(1);
}

$seasonYear   = array_shift($args);
$roundNumbers = [];
foreach ($args as $arg) {
    $n = (int) $arg;
    if ($n < 1) {
        fwrite(STDERR, "Invalid round number: $arg\n");
        exit(1);
    }
    $roundNumbers[] = $n;
}

// ---------------------------------------------------------------------------
// Bootstrap DB
// ---------------------------------------------------------------------------
$config = require __DIR__ . '/../src/config/config.php';
$dbCfg  = $config['database'];

$db = new Database(
    (string) ($dbCfg['host']     ?? 'db'),
    (int)    ($dbCfg['port']     ?? 3306),
    (string) ($dbCfg['name']     ?? 'TW4_base'),
    (string) ($dbCfg['user']     ?? 'root'),
    (string) ($dbCfg['password'] ?? '')
);

// ---------------------------------------------------------------------------
// Export
// ---------------------------------------------------------------------------
$service = new SnapshotExportService($db);

foreach ($roundNumbers as $roundNumber) {
    echo "Exporting season=$seasonYear round=$roundNumber ... ";
    try {
        $result  = $service->exportRoundSnapshots($seasonYear, $roundNumber, true);
        $written = $result['written'] ?? [];
        $slug    = $result['round_slug'] ?? (string) $roundNumber;
        echo "OK – $slug (" . count($written) . " files written)\n";
        foreach ($written as $file) {
            echo "  wrote  $file\n";
        }
    } catch (\Throwable $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "Done.\n";
