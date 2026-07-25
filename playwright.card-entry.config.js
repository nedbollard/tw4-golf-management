const baseConfig = require('./playwright.config');

module.exports = {
  ...baseConfig,
  testDir: 'tests/e2e',
  testIgnore: [],
  use: {
    ...baseConfig.use,
    baseURL: process.env.E2E_BASE_URL || 'http://localhost:8086'
  }
};
