const { test, expect } = require('@playwright/test');

const adminUsername = process.env.E2E_ADMIN_USERNAME || 'admin';
const adminPassword = process.env.E2E_ADMIN_PASSWORD || 'hash_house';

test('logs page loads entries and filters without an availability warning', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('Username').fill(adminUsername);
  await page.getByLabel('Password').fill(adminPassword);
  await page.getByRole('button', { name: 'Login' }).click();
  await expect(page).toHaveURL(/\/admin\/menu$/);

  await page.getByRole('link', { name: 'View Logs' }).click();
  await expect(page).toHaveURL(/\/logs$/);
  await expect(page.getByText(/Logs are temporarily unavailable/i)).toHaveCount(0);
  await expect(page.locator('.logs-summary')).toContainText('log entries found');
  await expect(page.locator('#level option')).not.toHaveCount(1);
  await expect(page.locator('#event_type option')).not.toHaveCount(1);
  await expect(page.locator('#username option')).not.toHaveCount(1);

  await page.screenshot({ path: 'test-results/logs-without-warning.png', fullPage: true });
});