const { test, expect } = require('@playwright/test');

test('home page responds', async ({ page }) => {
  const response = await page.goto('/');
  expect(response && response.status()).toBeLessThan(400);
});
