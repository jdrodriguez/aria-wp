# Repository Guidelines

## Project Structure & Module Organization
- `aria.php` bootstraps the plugin and wires WordPress hooks.
- `includes/` houses PHP services (loaders, AI providers, security, database updaters) autoloaded under the `Aria\` namespace.
- `admin/` renders the WP admin experience; shared UI assets live in `admin/js` and `admin/partials`.
- `src/` contains modern JS/React modules (`src/admin`, `src/public`) plus SCSS that webpack compiles into `dist/`.
- Local development: WordPress Studio install lives at `/Users/josuerodriguez/Studio/aria`; plugin code is symlinked into `wp-content/plugins/aria` so edits under the repo update Studio instantly. The Studio stack uses the Homebrew MySQL 8 instance configured in `wp-config.php` (DB host `127.0.0.1`, database `aria_studio`).
- `assets/` stores media and icons, `languages/` holds translation files, and `tests/` includes PHPUnit specs plus `visual/` Playwright scenarios.
- We are completing the migration from legacy PHP admin views to the React interface. Treat React (`src/js/admin`) as the single source of truth for new work and consult `docs/FINAL_EXECUTION_PLAN.md` for sequencing.
- Shared admin layout primitives live under `src/js/admin/components` (`PageShell`, `SectionCard`, `MetricCard`, etc.). All admin pages should compose page-specific sections from these primitives; new section modules belong in `src/js/admin/pages/<page>-sections/` with matching SCSS in `src/scss/pages/`.
  - Completed: `settings-panels/`, `knowledge-sections/`, `conversations-sections/`, `content-indexing-sections/`, and `knowledge-entry-sections/` provide the pattern to follow.

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
- When touching admin assets, depend on the unified `dist/admin.js` bundle (`aria-admin` handle); the legacy `aria-admin-react` handle is now just an alias for backward compatibility.
- Keep UI changes aligned with the shared design primitives introduced during Phase 4 so all admin pages stay visually consistent.

## Versioning Guidelines
- Current development version: `1.6.0`. Keep `aria.php` header, `ARIA_VERSION`, and `ARIA_DB_VERSION` aligned to this value unless a new release is cut.
- When bumping the version, update PHPUnit assertions (e.g., `tests/test-aria-core.php`) and any release documentation in lockstep.
- Document version bumps and release readiness in `docs/FINAL_EXECUTION_PLAN.md` or progress logs so the team has a clear audit trail.

## Environment Requirements
- Database servers must run MySQL 5.7 or higher (or MariaDB 10.4+). The activator enforces this; do not introduce features that require falling back to 5.6 schemas.

## Git Workflow & Commit Guidelines

### Standard Commit Process
```bash
# 1. Check current status
git status

# 2. Stage changes (choose one)
git add -A                    # Stage all changes
git add <file>                # Stage specific file
git add .                     # Stage current directory

# 3. Commit with descriptive message
git commit -m "Short summary of changes

- Detailed bullet point of what changed
- Another change description
- Reference issue if applicable"

# 4. Push to remote repository
git push origin main          # Push to main branch
```

### Important Git Files
- **`.gitignore`** - Critical file that prevents tracking of:
  - `node_modules/` and `vendor/` (dependencies)
  - `dist/` (build artifacts, can be regenerated)
  - `backup-before-reorg/` (temporary backup files)
  - `.DS_Store` and system files
  - **Never delete this file!** If accidentally deleted, restore immediately

### Commit Message Format
- **Subject line**: Short (50 chars), imperative mood (e.g., `Add personality caching layer`)
- **Body**: Explain what and why, not how
- **Footer**: Reference issues with `Fixes #123` or `Closes #456`

Example:
```
Complete Phase 4 UI standardization

- Migrated all admin pages to React components
- Implemented shared design primitives
- Reduced bundle size by 450KB
- Updated documentation

Fixes #123
```

### Before Committing Checklist
```bash
# 1. Run build to ensure everything compiles
npm run build

# 2. Check for linting issues
npm run lint

# 3. Run tests if applicable
npm test
composer test

# 4. Verify no sensitive data in changes
git diff --staged            # Review staged changes
```

### Common Git Commands
```bash
# View commit history
git log --oneline -10        # Last 10 commits

# Check remote status
git fetch
git status

# Undo last commit (keep changes)
git reset --soft HEAD~1

# Discard local changes
git checkout -- <file>        # Specific file
git reset --hard HEAD         # All changes (CAUTION!)

# Create and push a new branch
git checkout -b feature/new-feature
git push -u origin feature/new-feature
```

### Handling Large Commits
When making extensive changes:
1. Commit incrementally as you complete logical units of work
2. Use descriptive commit messages for each phase
3. Push regularly to avoid losing work
4. Consider using feature branches for major changes

### Pull Request Guidelines
- Summarize the intent and changes made
- List any breaking changes
- Include test results (`npm test`, `composer test`)
- Attach screenshots for UI changes
- Ensure all CI checks pass before merging
