const { test, expect } = require('@playwright/test');

test('main menu presents quiet public actions with tooltips', async ({ page }) => {
  await page.goto('/');

  await expect(page.getByRole('heading', { name: 'Recent Results' })).toHaveCount(0);
  await expect(page.getByRole('heading', { name: 'Leaderboard' })).toHaveCount(0);
  await expect(page.getByRole('heading', { name: 'Player Progress' })).toHaveCount(0);

  const results = page.getByRole('link', { name: 'View Results' });
  const leaderboard = page.getByRole('link', { name: 'View Leaderboard' });
  const playerProgress = page.getByRole('link', { name: 'View Player Progress' });

  await expect(results).toBeVisible();
  await expect(leaderboard).toBeVisible();
  await expect(playerProgress).toBeVisible();

  await expect(page.getByText('View latest competition results', { exact: true })).toBeHidden();
  await results.hover();
  await expect(page.getByText('View latest competition results', { exact: true })).toBeVisible();

  await leaderboard.focus();
  await expect(page.getByText('Live progress for the round currently being scored', { exact: true })).toBeVisible();

  await playerProgress.focus();
  await expect(page.getByText('Season-by-season handicap movement and scoring trends for one player.', { exact: true })).toBeVisible();

  await page.mouse.move(0, 0);
  await page.evaluate(() => document.activeElement.blur());
  await page.screenshot({ path: 'test-results/main-menu-desktop.png', fullPage: true });
  await page.setViewportSize({ width: 390, height: 844 });
  await expect(playerProgress).toBeVisible();
  await page.screenshot({ path: 'test-results/main-menu-mobile.png', fullPage: true });
});