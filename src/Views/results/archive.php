<!DOCTYPE html>
<!-- Comment by Ned: Will git recognise this as a change-->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Reports List</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="page-results-archive">
<?php
$sessionUsername = (string) ($_SESSION['username'] ?? 'scorer');
$sessionRole = (string) ($_SESSION['role'] ?? 'scorer');
$selectedSeason = trim((string) ($selectedSeason ?? ''));
$selectedRound = trim((string) ($selectedRound ?? ''));
?>
<div class="results-archive-layout">
    <header class="results-archive-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="results-archive-card">
            <h2 class="results-archive-card-title"><?php echo htmlspecialchars($app_title); ?></h2>
            <div class="results-archive-card-body">
                <div class="section-title-wrap text-center">
                    <h2>Reports List</h2>
                    <div class="section-title-accent"></div>
                </div>

                <div class="results-archive-session" role="status" aria-live="polite">
                    <strong>Session Active</strong>
                    <span>
                        Signed in as <?php echo htmlspecialchars($sessionUsername); ?>
                        (<?php echo htmlspecialchars(ucfirst($sessionRole)); ?>)
                    </span>
                </div>

                <?php if (!empty($success)): ?>
                    <div class="results-archive-alert results-archive-alert-success" role="status">
                        <?php echo htmlspecialchars((string) $success); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="results-archive-alert results-archive-alert-error" role="alert">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars((string) $error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($archiveTree)): ?>
                    <div class="results-archive-empty">
                        No rounds have been archived yet.
                    </div>
                <?php else: ?>
                    <div class="results-archive-tree" role="tree">
                        <?php foreach ($archiveTree as $season): ?>
                            <?php
                            $seasonCode = (string) ($season['season_year'] ?? 'unknown');
                            $seasonOpen = ($selectedSeason === '' || $selectedSeason === $seasonCode);
                            ?>
                            <details class="results-season" <?php echo $seasonOpen ? 'open' : ''; ?>>
                                <summary>
                                    Season: <?php echo htmlspecialchars($seasonCode); ?>
                                </summary>

                                <?php foreach (($season['rounds'] ?? []) as $round): ?>
                                    <?php
                                    $roundSlug = (string) ($round['round_slug'] ?? '000');
                                    $roundOpen = ($selectedRound !== ''
                                        && $selectedRound === $roundSlug
                                        && ($selectedSeason === '' || $selectedSeason === $seasonCode));

                                    $snapshots = (array) ($round['snapshots'] ?? []);
                                    usort(
                                        $snapshots,
                                        static fn(array $a, array $b): int => strcmp(
                                            (string) ($a['filename'] ?? ''),
                                            (string) ($b['filename'] ?? '')
                                        )
                                    );
                                    ?>
                                    <details class="results-round" <?php echo $roundOpen ? 'open' : ''; ?>>
                                        <summary>
                                            Round: <?php echo htmlspecialchars($roundSlug); ?>
                                            <span class="round-meta">
                                                <?php if (!empty($round['name_course'])): ?>
                                                    <?php echo htmlspecialchars((string) $round['name_course']); ?>
                                                <?php endif; ?>
                                                <?php if (!empty($round['card_count'])): ?>
                                                    (cards: <?php echo (int) $round['card_count']; ?>)
                                                <?php endif; ?>
                                            </span>
                                        </summary>

                                        <ul class="snapshot-list">
                                            <?php foreach ($snapshots as $snapshot): ?>
                                                <li>
                                                    <?php if (!empty($snapshot['exists'])): ?>
                                                        <a href="<?php echo htmlspecialchars((string) $snapshot['href']); ?>" class="snapshot-link">
                                                            <?php echo htmlspecialchars((string) $snapshot['filename']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="snapshot-missing">
                                                            <?php echo htmlspecialchars((string) $snapshot['filename']); ?> (not exported yet)
                                                        </span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </details>
                                <?php endforeach; ?>
                            </details>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="results-archive-actions">
                    <a href="/" class="btn-primary-pill">Home</a>
                    <a href="/leaderboard" class="btn-accent-pill">Leaderboard</a>
                </div>
            </div>
        </div>

        <footer class="results-archive-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

</body>
</html>
