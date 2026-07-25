// Playwright configuration for TW4
module.exports = {
  testDir: 'tests',
  testIgnore: '**/e2e/**',
  timeout: 30000,
  use: {
    baseURL: process.env.E2E_BASE_URL || 'http://localhost:8084',
    headless: true,
    viewport: { width: 1280, height: 720 },
    actionTimeout: 10000
  },
  reporter: [['list']]
};
