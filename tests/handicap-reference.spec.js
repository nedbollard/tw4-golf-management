const { test, expect } = require('@playwright/test');

const scorerUsername = process.env.E2E_SCORER_USERNAME || 'scorer';
const scorerPassword = process.env.E2E_SCORER_PASSWORD || 'hash_house';

async function loginAsScorer(page) {
  await page.goto('/login');
  await page.getByLabel('Username').fill(scorerUsername);
  await page.getByLabel('Password').fill(scorerPassword);
  await page.getByRole('button', { name: 'Login' }).click();
  await expect(page).toHaveURL(/\/scorer\/menu$/);
}

test('scorer finds standard and plus playing handicaps', async ({ page }) => {
  await loginAsScorer(page);

  const toolbarLabels = await page.locator('.scorer-toolbar a').allTextContents();
  expect(toolbarLabels.slice(1, 4)).toEqual(['View Roster', 'Find Handicap', 'Leaderboard']);

  await page.getByRole('link', { name: 'Find Handicap' }).click();
  await expect(page).toHaveURL(/\/handicap-reference$/);
  await expect(page.getByLabel('Club')).toHaveValue('294');

  await page.getByLabel('Handicap Index').fill('10.2');
  await page.getByRole('button', { name: 'Find Handicap' }).click();
  await expect(page.locator('.handicap-reference-result-value strong')).toHaveText('7');
  await expect(page.getByText('62.9', { exact: true })).toBeVisible();
  await expect(page.getByText('107', { exact: true })).toBeVisible();

  await page.locator('label[for="index_plus"]').click();
  await page.getByLabel('Handicap Index').fill('5.0');
  await page.getByRole('button', { name: 'Find Handicap' }).click();
  await expect(page.locator('.handicap-reference-result-value strong')).toHaveText('+8');
  await expect(page.getByText('TW4: Scratch', { exact: true })).toBeVisible();

  await page.locator('label[for="gender_f"]').click();
  await page.getByLabel('Tees').selectOption({ label: 'Yellow' });
  await page.locator('label[for="index_standard"]').click();
  await page.getByLabel('Handicap Index').fill('25.2');
  await page.getByRole('button', { name: 'Find Handicap' }).click();
  await expect(page.locator('.handicap-reference-result-value strong')).toHaveText('24');
  await expect(page.getByText('65.2', { exact: true })).toBeVisible();
  await expect(page.getByText('109', { exact: true })).toBeVisible();

  await page.screenshot({ path: 'test-results/handicap-reference-desktop.png', fullPage: true });
  await page.setViewportSize({ width: 390, height: 844 });
  await expect(page.getByRole('button', { name: 'Find Handicap' })).toBeVisible();
  await expect(page.locator('.handicap-reference-result-value strong')).toBeVisible();
  await page.screenshot({ path: 'test-results/handicap-reference-mobile.png', fullPage: true });
});