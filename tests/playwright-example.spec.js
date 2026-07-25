const { test, expect } = require('@playwright/test');

const adminUsername = process.env.E2E_ADMIN_USERNAME || 'admin';
const adminPassword = process.env.E2E_ADMIN_PASSWORD || 'hash_house';

async function loginAsAdmin(page) {
  await page.goto('/login');
  await page.getByLabel('Username').fill(adminUsername);
  await page.getByLabel('Password').fill(adminPassword);
  await page.getByRole('button', { name: 'Login' }).click();
  await expect(page).toHaveURL(/\/admin\/menu$/);
}

test('home page responds', async ({ page }) => {
  const response = await page.goto('/');
  expect(response && response.status()).toBeLessThan(400);
});

test('admin can log in and log out', async ({ page }) => {
  await loginAsAdmin(page);
  await expect(page.getByRole('heading', { name: 'Administrator Options' })).toBeVisible();
  await expect(page.getByText('Signed in as admin (Admin)')).toBeVisible();

  await page.getByRole('link', { name: 'Logout' }).click();
  await expect(page).toHaveURL(/\/$/);

  await page.goto('/admin/menu');
  await expect(page).toHaveURL(/\/login$/);
});

test('invalid login remains on the login form', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('Username').fill(adminUsername);
  await page.getByLabel('Password').fill('not-the-password');
  await page.getByRole('button', { name: 'Login' }).click();

  await expect(page).toHaveURL(/\/login$/);
  await expect(page.getByText('Invalid username or password')).toBeVisible();
});

test('card entry requires an authenticated scorer', async ({ page }) => {
  await page.goto('/scores/enter');
  await expect(page).toHaveURL(/\/login$/);

  await loginAsAdmin(page);
  await page.goto('/scores/enter');
  await expect(page).toHaveURL(/\/error\?code=403/);
  await expect(page.getByText(/requires the scorer role/i)).toBeVisible();
});
