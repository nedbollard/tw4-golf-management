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
                    <?php
                    $playerList   = $state['player_list'] ?? [];
                    $isRevisit    = (bool) ($state['is_revisit'] ?? false);
                    $hasUnassigned = false;
                    foreach ($playerList as $plRow) {
                        if ($plRow['assigned_team'] === null) { $hasUnassigned = true; break; }
                    }
                    ?>

                    <?php if ($isRevisit): ?>
                        <p class="text-muted small mb-2">
                            Players are shown in team &amp; slot order. Set a team to <strong>0</strong> to remove a player and return them to the unassigned pool. Blank = unassigned.
                        </p>
                    <?php else: ?>
                        <p class="text-muted small mb-2">
                            Enter a team number (1–99) next to each committed player. Leave blank to exclude. Short teams are padded with makeup players automatically.
                        </p>
                    <?php endif; ?>

                    <div class="table-responsive mb-3">
                        <table class="table table-striped table-sm align-middle">
                            <thead>
                                <tr>
                                    <th style="width:5rem">Team</th>
                                    <th>Player</th>
                                    <th>Rounds</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $unassignedSectionStarted = false;
                                foreach ($playerList as $plRow):
                                    $plIdentifier   = (string) ($plRow['player_identifier'] ?? '');
                                    $plDisplayName  = htmlspecialchars((string) ($plRow['display_name'] ?? ''));
                                    $plPoints       = (int) ($plRow['points_total'] ?? 0);
                                    $plRounds       = (int) ($plRow['rounds_played'] ?? 0);
                                    $plAssignedTeam = $plRow['assigned_team'];
                                    $plInputValue   = $plAssignedTeam !== null ? (string) $plAssignedTeam : '';
                                    $plIsMakeup     = (bool) ($plRow['is_makeup'] ?? false);

                                    if ($isRevisit && $plAssignedTeam === null && !$unassignedSectionStarted):
                                        $unassignedSectionStarted = true;
                                ?>
                                    <tr id="unassigned-section">
                                        <td colspan="4" class="text-muted small py-1 bg-light">— Unassigned players —</td>
                                    </tr>
                                <?php endif; ?>
                                    <tr<?php if ($plIsMakeup): ?> class="text-muted"<?php endif; ?>>
                                        <td>
                                            <input
                                                type="text"
                                                inputmode="numeric"
                                                pattern="[0-9]{0,2}"
                                                maxlength="2"
                                                name="<?php echo $plIsMakeup ? '' : 'team_assignments[' . htmlspecialchars($plIdentifier) . ']'; ?>"
                                                value="<?php echo htmlspecialchars($plInputValue); ?>"
                                                class="form-control form-control-sm<?php echo $plIsMakeup ? ' bg-light text-muted' : ' team-input'; ?>"
                                                style="width:4rem"
                                                <?php echo $plIsMakeup ? 'disabled' : ''; ?>
                                                aria-label="Team for <?php echo htmlspecialchars((string) ($plRow['display_name'] ?? $plIdentifier)); ?>"
                                            >
                                        </td>
                                        <td>
                                            <?php if ($plIsMakeup): ?>
                                                <em><?php echo $plDisplayName; ?></em>
                                                <span class="small ms-2">— <a href="#unassigned-section">assign a real player to team <?php echo htmlspecialchars($plInputValue); ?> to replace</a></span>
                                            <?php else: ?>
                                                <?php echo $plDisplayName; ?> <span class="text-muted small">(<?php echo htmlspecialchars($plIdentifier); ?>)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $plRounds; ?></td>
                                        <td><?php echo $plPoints; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="admin-actions">
                        <button type="submit" name="action" value="save" class="btn btn-primary">Save Teams</button>
                        <?php if ($isRevisit && $hasUnassigned): ?>
                            <a href="#unassigned-section" class="btn btn-outline-secondary">Find Replacements</a>
                        <?php endif; ?>
                        <a href="/admin/team-haggle" class="btn btn-outline-secondary">Cancel</a>
                        <a href="/admin/menu" class="btn-secondary-pill">Back to Admin Menu</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var form = document.getElementById('teamHaggleForm');
    if (!form) { return; }
    form.addEventListener('submit', function (e) {
        var inputs = form.querySelectorAll('.team-input');
        var firstBad = null;
        inputs.forEach(function (input) {
            var v = input.value.trim();
            if (v !== '' && !/^[0-9]{1,2}$/.test(v)) {
                input.classList.add('is-invalid');
                if (!firstBad) { firstBad = input; }
            } else {
                input.classList.remove('is-invalid');
            }
        });
        if (firstBad) {
            e.preventDefault();
            firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstBad.focus();
        }
    });
})();
</script>
</body>
</html>
