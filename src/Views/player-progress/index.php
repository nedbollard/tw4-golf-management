<?php
ob_start();
?>
<div class="player-progress-panel player-progress-panel-controls">
    <form method="GET" action="/player-progress/chart" class="player-progress-controls">
        <div class="player-progress-control-row">
            <select name="player_id" id="player_id" class="form-select">
                <?php foreach ($playerOptions as $option): ?>
                    <?php
                    $optionId = (int) ($option['row_id'] ?? 0);
                    // Public-facing screen: never show real names. Alias if set, otherwise
                    // the player identifier.
                    $optionLabel = trim((string) ($option['alias'] ?? ''));
                    if ($optionLabel === '') {
                        $optionLabel = (string) ($option['player_identifier'] ?? '');
                    }
                    ?>
                    <option value="<?php echo $optionId; ?>" <?php echo (int) ($selectedPlayerId ?? 0) === $optionId ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($optionLabel); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary-pill">Open Chart</button>
        </div>
    </form>

    <div class="player-progress-summary">
        <div><strong>History</strong> All recorded seasons</div>
        <div><strong>Eligible Players</strong> <?php echo count($playerOptions); ?></div>
    </div>

    <div class="player-progress-control-row player-progress-control-row-center">
        <a href="/" class="btn-secondary-pill">Cancel</a>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="player-progress-alert" role="status">
            <?php echo htmlspecialchars((string) $notice); ?>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
$pageHeading = 'Player Progress Selector';
$pageStepLabel = 'Choose an active player to view their recorded history across all seasons. Unfinished live rounds are not included.';
require __DIR__ . '/../layouts/player-progress.php';
