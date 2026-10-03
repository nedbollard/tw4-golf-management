const { test, expect } = require('@playwright/test');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const fs = require('node:fs');

const root = path.resolve(__dirname, '../..');
const render = (state = '') => execFileSync('php', [path.join(root, 'tests/fixtures/score-entry-view.php'), state], { encoding: 'utf8' });

async function openEntry(page, state = '') {
  const actions = [];
  await page.route('**/*', async (route) => {
    const url = new URL(route.request().url());
    if (url.pathname === '/scores/enter/1') {
      if (route.request().method() === 'POST') {
        const action = new URLSearchParams(route.request().postData()).get('action');
        actions.push(action);
        await route.fulfill({ contentType: 'text/html', body: action === 'calculate' ? render('calculated') : '<h1>Card saved</h1>' });
      } else {
        await route.fulfill({ contentType: 'text/html', body: render(state) });
      }
    } else if (url.pathname === '/assets/css/style.css') {
      await route.fulfill({ contentType: 'text/css', body: fs.readFileSync(path.join(root, 'public/assets/css/style.css'), 'utf8') });
    } else {
      await route.abort();
    }
  });
  await page.goto('/scores/enter/1');
  return actions;
}

test('Save requires calculation and receives red focus and Enter activation', async ({ page }) => {
  const actions = await openEntry(page);
  const save = page.getByRole('button', { name: 'Save', exact: true });
  const calculate = page.getByRole('button', { name: 'Calculate', exact: true });
  await expect(page.locator('link[href^="/assets/css/style.css?v="]')).toHaveAttribute('href', /[?]v=\d+$/);
  await expect(save).toBeDisabled();
  for (let index = 0; index < 9; index++) {
    await page.locator('.score-input').nth(index).fill('4');
  }
  await expect(save).toBeDisabled();
  await expect(calculate).toBeFocused();
  await expect(calculate).toHaveCSS('outline-color', 'rgb(220, 38, 38)');
  await page.keyboard.press('Enter');
  await expect(save).toBeEnabled();
  await expect(save).toBeFocused();
  await expect(save).toHaveCSS('outline-color', 'rgb(220, 38, 38)');
  await expect(save).toHaveCSS('outline-style', 'solid');
  await expect(save).toHaveCSS('outline-width', '2px');
  await page.keyboard.press('Enter');
  await expect(page.getByRole('heading', { name: 'Card saved' })).toBeVisible();
  expect(actions).toEqual(['calculate', 'save']);
});

test('editing calculated scores disables Save until recalculated', async ({ page }) => {
  const actions = await openEntry(page, 'calculated');
  const save = page.getByRole('button', { name: 'Save', exact: true });
  await expect(save).toBeEnabled();
  await page.locator('.score-input').first().fill('5');
  await expect(save).toBeDisabled();
  await page.getByRole('button', { name: 'Calculate', exact: true }).click();
  await expect(save).toBeEnabled();
  await save.click();
  await expect(page.getByRole('heading', { name: 'Card saved' })).toBeVisible();
  expect(actions).toEqual(['calculate', 'save']);
});

test('incomplete and invalid calculations do not enable Save', async ({ page }) => {
  const actions = await openEntry(page, 'invalid');
  const save = page.getByRole('button', { name: 'Save', exact: true });
  await expect(save).toBeDisabled();
  await page.getByRole('button', { name: 'Calculate', exact: true }).click();
  expect(actions).toEqual([]);
  await expect(save).toBeDisabled();
  await expect(page.locator('.enter-card-alert')).toContainText('Hole 9: score is required.');
});

test('Enter from a score field calculates rather than bypassing the Save gate', async ({ page }) => {
  const actions = await openEntry(page);
  for (let index = 0; index < 9; index++) {
    await page.locator('.score-input').nth(index).fill('4');
  }
  await page.locator('.score-input').first().focus();
  await page.keyboard.press('Enter');
  await expect(page.getByRole('button', { name: 'Save', exact: true })).toBeFocused();
  expect(actions).toEqual(['calculate']);
});
