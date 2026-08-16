<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Delete Cards</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="page-present-results">
<?php
$roundNumber = (int) ($round['round_number'] ?? 0);
$roundDate = (string) ($round['round_date'] ?? '');
$courseName = trim((string) ($round['course_name'] ?? ''));
$confirmMessage = count($cards) === 1
    ? 'Do you really want to delete this card?'
    : 'Do you really want to delete these cards?';
?>
<div class="present-results-layout">
    <header class="present-results-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="present-results-card">
            <h2 class="present-results-card-title"><?php echo htmlspecialchars($app_title); ?> - Delete Cards</h2>
            <div class="present-results-card-body">
                <div class="section-title-wrap text-center">
                    <h2>Cards Entered for Round <?php echo $roundNumber; ?><?php if ($courseName !== '') echo ' · ' . htmlspecialchars($courseName); ?></h2>
                    <div class="section-title-accent"></div>
                </div>

                <form method="POST" action="/scores/delete-cards" id="delete-cards-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="confirm_delete" id="confirm-delete" value="no">

                    <div class="table-responsive mb-3">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Delete</th>
                                    <th>Order</th>
                                    <th>Player</th>
                                    <th>Score</th>
                                    <th>Points</th>
                                    <th>Handicap Applied</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cards as $idx => $card): ?>
                                    <?php
                                    $cardId = (int) ($card['card_id'] ?? 0);
                                    $player = (string) ($card['display_player'] ?? 'unknown');
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="selected_cards[]" value="<?php echo $cardId; ?>">
                                        </td>
                                        <td><?php echo $idx + 1; ?></td>
                                        <td><?php echo htmlspecialchars($player); ?></td>
                                        <td><?php echo (int) ($card['score'] ?? 0); ?></td>
                                        <td><?php echo (int) ($card['points'] ?? 0); ?></td>
                                        <td><?php echo (int) ($card['handicap_applied'] ?? 0); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="present-results-actions">
                        <button type="submit" class="btn-action-destructive" id="delete-selected-button" data-original-label="Delete Selected">Delete Selected</button>
                        <a href="/scorer/menu" class="btn-secondary-pill">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <footer class="present-results-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

<script nonce="<?php echo htmlspecialchars($csp_nonce, ENT_QUOTES, 'UTF-8'); ?>">
(() => {
    const form = document.getElementById('delete-cards-form');
    const confirmField = document.getElementById('confirm-delete');
    const deleteButton = document.getElementById('delete-selected-button');

    if (!form || !confirmField || !deleteButton) {
        return;
    }

    const originalLabel = deleteButton.dataset.originalLabel || 'Delete Selected';

    const setBusy = () => {
        deleteButton.textContent = 'Deleting ...';
        deleteButton.disabled = true;
        deleteButton.style.pointerEvents = 'none';
        deleteButton.style.opacity = '1';
        deleteButton.setAttribute('aria-disabled', 'true');
    };

    const resetBusy = () => {
        deleteButton.textContent = originalLabel;
        deleteButton.disabled = false;
        deleteButton.style.pointerEvents = '';
        deleteButton.style.opacity = '';
        deleteButton.removeAttribute('aria-disabled');
    };

    window.addEventListener('pageshow', resetBusy);

    form.addEventListener('submit', (event) => {
        if (confirmField.value === 'yes') {
            return;
        }

        event.preventDefault();
        const checkedCount = form.querySelectorAll('input[type="checkbox"][name="selected_cards[]"]:checked').length;
        if (checkedCount < 1) {
            alert('Please select at least one card to delete.');
            return;
        }

        const message = checkedCount === 1
            ? 'Do you really want to delete this card?'
            : 'Do you really want to delete these cards?';

        if (window.confirm(message)) {
            confirmField.value = 'yes';
            setBusy();
            form.submit();
        }
    });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>