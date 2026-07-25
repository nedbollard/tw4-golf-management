const { test, expect } = require('@playwright/test');

const scorerPassword = process.env.E2E_SCORER_PASSWORD || 'hash_house';

test('scorer calculates and saves a complete card', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('Username').fill('scorer');
  await page.getByLabel('Password').fill(scorerPassword);
  await page.getByRole('button', { name: 'Login' }).click();
  await expect(page).toHaveURL(/\/scorer\/menu$/);

  await page.goto('/scores/enter');
  await expect(page.getByRole('heading', { name: /Select Player/ })).toBeVisible();
  await page.locator('#player_id').selectOption('1');
  await expect(page).toHaveURL(/\/scores\/enter\/1$/);

  const scoreInputs = page.locator('.score-input');
  await expect(scoreInputs).toHaveCount(9);
  for (let index = 0; index < 9; index += 1) {
    await scoreInputs.nth(index).fill('4');
  }

  await page.getByRole('button', { name: 'Calculate' }).click();
  await expect(page).toHaveURL(/\/scores\/enter\/1$/);
  await expect(page.locator('tfoot .total-blue').first()).toHaveText('36');

  await page.getByRole('button', { name: 'Save' }).click();
  await expect(page).toHaveURL(/\/scores\/enter$/);
  await expect(page.getByText('Card saved successfully for E2EPlayer.')).toBeVisible();
  await expect(page.locator('#player_id option[value="1"]')).toContainText('[saved]');
});
