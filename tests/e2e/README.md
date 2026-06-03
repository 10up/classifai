# E2E Tests

This directory contains end-to-end tests for the project, utilizing [Playwright](https://playwright.dev) and its test runner to run the tests in Chromium by default. [`@wordpress/e2e-test-utils-playwright`](https://github.com/WordPress/gutenberg/tree/HEAD/packages/e2e-test-utils-playwright) is used as a helper package to simplify the usage. See the documentation of both for more information.

## Prerequisites

- **Node.js** (v22)
- **Docker**

## Running Tests

### Test Setup

To prepare the test environment, follow these steps:

1. Run `npm install` to install the required dependencies.
2. Start the environment by running `npm run env:start`. *(Ensure Docker is running before executing this command.)*

### Test Execution

To execute the tests, use the following commands:

1. Run all tests:
`npm run test:e2e`

2. Run tests in UI mode:
`npm run test:e2e:debug`

3. Stop the environment by running `npm run env:stop`.
