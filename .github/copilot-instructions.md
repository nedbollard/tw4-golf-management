# Copilot instructions for TW4

This file helps Copilot-style assistants (and future contributors) quickly understand how to build, test, and navigate this repository.

---

## Build / Run
- Local deps: `composer install`
- Docker (development): from repository root:
  - `docker-compose up --build` (app on http://localhost:8084, phpMyAdmin on http://localhost:8085, MySQL on host:3307)
- To run commands INSIDE app container (useful when vendor/ is not present locally):
  - `docker compose exec app bash` then run commands (e.g. `./vendor/bin/phpunit`)

## Tests
- PHPUnit is used. Bootstrap: `vendor/autoload.php` (see phpunit.xml).
- Run all tests:
  - `./vendor/bin/phpunit`
- Run specific test file:
  - `./vendor/bin/phpunit tests/Unit/StaffTest.php`
- Run a specific testsuite (as defined in phpunit.xml):
  - `./vendor/bin/phpunit --testsuite Unit`
- Filter by test name (single method):
  - `./vendor/bin/phpunit --filter testMethodName`
- Coverage (HTML):
  - `./vendor/bin/phpunit --coverage-html coverage`
- Test DB / env (see phpunit.xml): DB_NAME=tw4_test, DB_HOST=db, DB_USER=root, DB_PASSWORD=secretpassword
- To create the test database via docker:
  - `docker compose exec db mysql -uroot -psecretpassword -e "CREATE DATABASE tw4_test;"`
  - Migrations: `docker compose exec db mysql -uroot -psecretpassword tw4_test < src/migrations/001_create_tables.sql`

## Linting / Static analysis
- No repository-provided linter or static-analysis configuration detected (no scripts in composer.json). Add PHPCS/PHPMD/PSALM if desired. Do not assume a linter is present.

## High-level architecture (big picture)
- Language: PHP (PSR-4 autoload via composer.json)
- Pattern: MVC (src/Controllers, src/Models, src/Views), with supporting folders: src/Core, src/Middleware, src/Services, src/Utility.
- Entry point / router: `public/index.php` — routing is query-parameter based (e.g. `?controller=auth&action=login`).
- Autoload mapping (composer.json): App\\Controllers\\ -> src/Controllers/, App\\Models\\ -> src/Models/, App\\Core\\ -> src/Core/, App\\Services\\ -> src/Services/ etc.
- Persistence: MySQL (docker-compose defines a `db` service). Migrations live under `src/migrations/`.
- Sessions: docker-compose uses a persistent volume `tw4_sessions` (see docker-compose.yml).
- Tests: organized into `tests/Unit` and `tests/Integration` and configured via phpunit.xml (testsuites named Unit and Integration).

## Key repository conventions (non-obvious)
- Namespaces follow PSR-4 and mirror directory names under `src/` with top-level prefix `App\\`.
- Controllers, Models, Views: add controllers to `src/Controllers/`, models to `src/Models/`, and views to `src/Views/`. Router expects controller/action via GET parameters.
- Configuration files: `src/config/config.php` is excluded from coverage and may contain environment-sensitive settings — avoid committing secrets.
- PHPUnit environment variables are set in phpunit.xml; tests expect a dedicated test DB `tw4_test` and APP_ENV=testing.
- Coverage/source rules: phpunit.xml excludes migrations and explicit config files from coverage.

## Where to look first
- `README.md` — high-level overview and docker quick-start
- `TESTING.md` — detailed test commands and examples
- `composer.json` and `phpunit.xml` — autoload, dev deps, test bootstrap and env
- `public/index.php` — basic routing entrypoint
- `src/migrations/` — DB schema for test and local setup

## Other assistant configs discovered
- No CLAUDE.md, .cursorrules, AGENTS.md, AIDER_CONVENTIONS.md, or similar AI-assistant config files found in repository root. If present, include here.

---

Playwright MCP server
- Playwright setup added: package.json, playwright.config.js, tests/playwright-example.spec.js, and CI workflow at .github/workflows/playwright.yml.
- Run locally:
  - npm ci
  - npx playwright install --with-deps
  - npm run test:e2e
- CI behavior:
  - The workflow brings up the app with docker-compose, waits for http://localhost:8084, installs Node and Playwright browsers, then runs tests with the GitHub reporter.

Summary: include the concrete commands above and the high-level pointers so a Copilot-style assistant can suggest changes, tests, or run/debug steps without opening many files.
