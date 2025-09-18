# Repository Guidelines

## Project Structure & Module Organization
- `aria.php` bootstraps the plugin and wires WordPress hooks.
- `includes/` houses PHP services (loaders, AI providers, security, database updaters) autoloaded under the `Aria\` namespace.
- `admin/` renders the WP admin experience; shared UI assets live in `admin/js` and `admin/partials`.
- `src/` contains modern JS/React modules (`src/admin`, `src/public`) plus SCSS that webpack compiles into `dist/`.
- `assets/` stores media and icons, `languages/` holds translation files, and `tests/` includes PHPUnit specs plus `visual/` Playwright scenarios.
- We are completing the migration from legacy PHP admin views to the React interface. Treat React (`src/js/admin`) as the single source of truth for new work and consult `docs/FINAL_EXECUTION_PLAN.md` for sequencing.

## Build, Test, and Development Commands
- `npm install` and `composer install` fetch front-end and PHP dependencies.
- `npm run dev` watches webpack bundles for admin and chat widgets.
- `npm run build` emits optimized assets into `dist/`.
- `npm run test` or `npm run test:watch` execute the Jest suite.
- `npm run lint` (`npm run lint:fix`) enforces the JavaScript style guide.
- `npm run zip` runs a production build and packages the plugin for distribution.
- `composer test` runs PHPUnit with Brain Monkey bootstrap from `tests/bootstrap.php`.
- `composer phpcs` and `composer phpstan` guard WordPress coding standards and static analysis.

## Coding Style & Naming Conventions
- PHP follows WordPress standards (`phpcs.xml`), 4-space indentation, snake_case functions, and `class-aria-*.php` filenames for classes.
- JavaScript uses 2-space indentation with ESLint (`@wordpress/eslint-plugin`); prefer PascalCase components and camelCase utilities/props.
- Scope SCSS selectors with the `aria-` prefix to avoid theme collisions; never hand-edit generated files in `dist/`.
- Commit formatted code only; run linting/formatting before opening a PR.

## Testing Guidelines
- Add PHP tests in `tests/` as `test-{feature}.php`; use Brain Monkey to mock WordPress hooks and globals.
- Jest specs live beside JS modules in `tests/**/*.test.js`; favor React Testing Library patterns already present.
- Visual regression and e2e flows belong in `tests/visual`; execute with `npx playwright test --config=playwright.config.js`.
- Refresh fixtures via `tests/bootstrap.php` when introducing new data paths to keep coverage meaningful.

## Current Execution Plan
- Follow `docs/FINAL_EXECUTION_PLAN.md` for week-by-week priorities (runtime hardening, admin bundle consolidation, conversation integrity, UI standardization, component refactors, release prep).
- When touching admin assets, remove dependencies on `admin-react.js` and update diagnostics/tests accordingly; React should be the only shipped admin interface going forward.
- Keep UI changes aligned with the shared design primitives introduced during Phase 4 so all admin pages stay visually consistent.

## Versioning Guidelines
- Current development version: `1.6.0`. Keep `aria.php` header, `ARIA_VERSION`, and `ARIA_DB_VERSION` aligned to this value unless a new release is cut.
- When bumping the version, update PHPUnit assertions (e.g., `tests/test-aria-core.php`) and any release documentation in lockstep.
- Document version bumps and release readiness in `docs/FINAL_EXECUTION_PLAN.md` or progress logs so the team has a clear audit trail.

## Environment Requirements
- Database servers must run MySQL 5.7 or higher (or MariaDB 10.4+). The activator enforces this; do not introduce features that require falling back to 5.6 schemas.

## Commit & Pull Request Guidelines
- Keep commit subjects short, present-tense, and imperative (e.g., `Add personality caching layer`); include context in the body when behaviour changes.
- Reference related issues with `Fixes #123` and split unrelated changes into separate commits.
- Pull requests should summarize intent, list manual/automated test output, and attach screenshots or screen recordings for admin UI updates.
- Ensure `npm run build`, `npm run test`, and `composer test` succeed locally before requesting review.
