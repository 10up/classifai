# E2E Tests

End-to-end tests for ClassifAI. Runs in Chromium by default via [Playwright](https://playwright.dev) and the official [`@wordpress/e2e-test-utils-playwright`](https://github.com/WordPress/gutenberg/tree/HEAD/packages/e2e-test-utils-playwright) helpers.

## Layout

```
tests/e2e/
├── assets/                  # Binary fixtures used by tests
├── config/
│   ├── global-setup.ts      # Authenticates, resets DB options, activates the mock plugin
│   └── flaky-tests-reporter.ts
├── fixtures/
│   ├── test.ts              # Exports the extended Playwright `test`/`expect` with `classifaiUtils`
│   ├── classifai-utils.ts   # Helpers that mirror the historical Cypress commands
│   └── test-data.ts         # Read canned JSON responses shipped by the test plugin
├── specs/
│   ├── admin/
│   ├── language-processing/
│   ├── image-processing/
│   └── recommendation-service/
├── playwright.config.ts
```

## Prerequisites

- **Node.js** (v22)
- **Docker**

## Running tests

1. Install dependencies:

   ```bash
   npm install
   npx playwright install --with-deps chromium
   ```

2. Start the WP test environment (must be running before tests):

   ```bash
   npm run env:start
   ```

3. Run the full suite:

   ```bash
   npm run test:e2e
   ```

   Or open Playwright UI mode:

   ```bash
   npm run test:e2e:debug
   ```

4. Run a single spec:

   ```bash
   npx playwright test --config tests/e2e/playwright.config.ts \
     tests/e2e/specs/admin/admin.spec.ts
   ```

5. Stop the environment:

   ```bash
   npm run env:stop
   ```

## Notes

- `tests/test-plugin/` short-circuits ClassifAI's outbound HTTP via `pre_http_request`
  and serves canned JSON fixtures so tests never hit real provider APIs.
- The global setup wipes `classifai_feature_*` options before each suite run so
  tests start from default provider settings/prompts/role permissions.
- Auth is established via storage state in `artifacts/storage-states/admin.json`,
  populated by the global setup — individual tests never need to log in.
