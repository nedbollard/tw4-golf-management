const { test, expect } = require('@playwright/test');

const adminUsername = process.env.E2E_ADMIN_USERNAME || 'admin';
const adminPassword = process.env.E2E_ADMIN_PASSWORD || 'hash_house';

test('admin menu presents button-only actions with tooltips', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('Username').fill(adminUsername);
  await page.getByLabel('Password').fill(adminPassword);
  await page.getByRole('button', { name: 'Login' }).click();
  await expect(page).toHaveURL(/\/admin\/menu$/);

  const scoringState = page.getByRole('link', { name: 'Manage Scoring State' });
  const teamHaggle = page.getByRole('link', { name: 'Team Haggle (Serious)' });

  await expect(page.locator('.admin-panel-action')).toHaveCount(7);
  await expect(page.getByText('Admin-only controls for workflow and lock state.', { exact: true })).toBeHidden();
  await expect(page.getByText('Set and maintain fixed team membership with replacement controls.', { exact: true })).toBeHidden();

  await scoringState.hover();
  await expect(page.getByText('Admin-only controls for workflow and lock state.', { exact: true })).toBeVisible();

  await page.mouse.move(0, 0);
  await teamHaggle.focus();
  await expect(page.getByText('Set and maintain fixed team membership with replacement controls.', { exact: true })).toBeVisible();

  await page.evaluate(() => document.activeElement.blur());
  await page.screenshot({ path: 'test-results/admin-menu-desktop.png', fullPage: true });
  await page.setViewportSize({ width: 390, height: 844 });
  await expect(teamHaggle).toBeVisible();
  await page.screenshot({ path: 'test-results/admin-menu-mobile.png', fullPage: true });
});