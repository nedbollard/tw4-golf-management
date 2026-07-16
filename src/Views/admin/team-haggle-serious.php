<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Team Haggle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../../../public/assets/css/style.css')); ?>" rel="stylesheet">
</head>
<body class="page-admin-menu">
<div class="admin-layout">
    <header class="admin-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="admin-card">
            <h2 class="admin-card-title"><?php echo htmlspecialchars($app_title); ?> Team Haggle (Serious)</h2>
            <div class="admin-card-body">
                <p class="admin-intro mb-3">
                    Revision <?php echo (int) ($state['revision'] ?? 0); ?>
                    | Season <?php echo htmlspecialchars((string) ($state['round']['season_year'] ?? '')); ?>
                    | Round <?php echo (int) ($state['round']['number_round'] ?? 0); ?>
                    | Team Size <?php echo (int) ($state['team_size'] ?? 0); ?>
                </p>

                <?php if (!empty($errors)): ?>
                    <div class="admin-alert admin-alert-error" role="alert">
                        <?php foreach ((array) $errors as $error): ?>
                            <div><?php echo htmlspecialchars((string) $error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="admin-alert admin-alert-success" role="status">
                        <?php foreach ((array) $success as $message): ?>
                            <div><?php echo htmlspecialchars((string) $message); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php
                $messages = $state['messages'] ?? [];
                if (!empty($messages)):
                ?>
                    <div class="admin-alert admin-alert-success" role="status">
                        <?php foreach ((array) $messages as $message): ?>
                            <div><?php echo htmlspecialchars((string) $message); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/admin/team-haggle" id="teamHaggleForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="revision" value="<?php echo (int) ($state['revision'] ?? 0); ?>">
                    <input type="hidden" name="removed_order" id="removed_order" value="">

                    <div class="table-responsive mb-3">
                        <table class="table table-striped table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Select</th>
                                    <th>Team</th>
                                    <th>Slot</th>
                                    <th>Player</th>
                                    <th>Rounds</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $playerPool = $state['player_pool'] ?? [];
                                $teams = $state['teams'] ?? [];
                                $replacementPlayers = $state['replacement_players'] ?? [];
                                foreach ($teams as $teamNumber => $slots):
                                    foreach ($slots as $slotNumber => $slot):
                                        $identifier = (string) ($slot['player_identifier'] ?? '');
                                        $poolRow = $playerPool[$identifier] ?? null;
                                        $displayName = (string) ($slot['display_name'] ?? ($poolRow['display_name'] ?? $identifier));
                                        $roundsPlayed = (int) ($slot['rounds_played'] ?? ($poolRow['rounds_played'] ?? 0));
                                        $pointsTotal = (int) ($slot['player_points_total'] ?? ($poolRow['points_total'] ?? 0));
                                        $slotRef = (int) $teamNumber . ':' . (int) $slotNumber;
                                ?>
                                    <tr>
                                        <td>
                                            <input
                                                type="checkbox"
                                                class="form-check-input slot-checkbox"
                                                data-slot-ref="<?php echo htmlspecialchars($slotRef); ?>"
                                                name="selected_slots[]"
                                                value="<?php echo htmlspecialchars($slotRef); ?>"
                                            >
                                        </td>
                                        <td><?php echo (int) $teamNumber; ?></td>
                                        <td><?php echo (int) $slotNumber; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($displayName); ?>
                                            <div class="text-muted small"><?php echo htmlspecialchars($identifier); ?></div>
                                            <input type="hidden" name="draft[<?php echo (int) $teamNumber; ?>][<?php echo (int) $slotNumber; ?>]" value="<?php echo htmlspecialchars($identifier); ?>">
                                        </td>
                                        <td><?php echo $roundsPlayed; ?></td>
                                        <td><?php echo $pointsTotal; ?></td>
                                    </tr>
                                <?php
                                    endforeach;
                                endforeach;
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="admin-actions">
                        <button type="button" id="openReplacementModal" class="btn btn-outline-primary">Find Replacements</button>
                        <button type="submit" name="action" value="save" class="btn btn-primary">Save Teams</button>
                        <a href="/admin/team-haggle" class="btn btn-outline-secondary">Cancel</a>
                        <a href="/admin/menu" class="btn-secondary-pill">Back to Admin Menu</a>
                    </div>

                    <div class="modal fade" id="replacementModal" tabindex="-1" aria-labelledby="replacementModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3 class="modal-title h5" id="replacementModalLabel">Replacement Players</h3>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted mb-2">Select up to the number of removed slots. If fewer are selected, makeup players are inserted automatically.</p>
                                    <?php if (empty($replacementPlayers)): ?>
                                        <div class="alert alert-warning mb-0" role="alert">
                                            No replacement players are currently available. You can still apply to fill selected slots with makeup players.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Select</th>
                                                        <th>Rank</th>
                                                        <th>Player</th>
                                                        <th>Identifier</th>
                                                        <th>Rounds</th>
                                                        <th>Points</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($replacementPlayers as $index => $row): ?>
                                                        <tr>
                                                            <td>
                                                                <input
                                                                    type="checkbox"
                                                                    class="form-check-input"
                                                                    name="replacement_ids[]"
                                                                    value="<?php echo htmlspecialchars((string) ($row['player_identifier'] ?? '')); ?>"
                                                                >
                                                            </td>
                                                            <td><span class="badge bg-secondary rounded-pill"><?php echo (int) $index + 1; ?></span></td>
                                                            <td><strong><?php echo htmlspecialchars((string) ($row['display_name'] ?? '')); ?></strong></td>
                                                            <td class="text-muted"><?php echo htmlspecialchars((string) ($row['player_identifier'] ?? '')); ?></td>
                                                            <td><?php echo (int) ($row['rounds_played'] ?? 0); ?></td>
                                                            <td><?php echo (int) ($row['points_total'] ?? 0); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" name="action" value="apply" class="btn btn-outline-primary">Apply Replacements</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const checkboxes = Array.from(document.querySelectorAll('.slot-checkbox'));
    const hiddenOrder = document.getElementById('removed_order');
    const openReplacementModalButton = document.getElementById('openReplacementModal');
    const replacementModalElement = document.getElementById('replacementModal');
    const replacementModal = replacementModalElement ? new bootstrap.Modal(replacementModalElement) : null;
    const ordered = [];

    function sync() {
        hiddenOrder.value = ordered.join(',');
    }

    function hasSelection() {
        return ordered.length > 0;
    }

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', function () {
            const ref = this.getAttribute('data-slot-ref') || '';
            const existingIdx = ordered.indexOf(ref);

            if (this.checked) {
                if (existingIdx === -1) {
                    ordered.push(ref);
                }
            } else if (existingIdx >= 0) {
                ordered.splice(existingIdx, 1);
            }

            sync();
        });
    });

    if (openReplacementModalButton && replacementModal) {
        openReplacementModalButton.addEventListener('click', function () {
            if (!hasSelection()) {
                window.alert('Select one or more team member slots before finding replacements.');
                return;
            }

            replacementModal.show();
        });
    }

    sync();
})();
</script>
</body>
</html>
