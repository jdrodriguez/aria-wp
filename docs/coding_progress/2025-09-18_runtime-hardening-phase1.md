# 2025-09-18 – Runtime Hardening Phase 1 Progress

## Summary
- Locked runtime requirements: activation now enforces MySQL ≥5.7 (or MariaDB ≥10.4) before proceeding.
- Added `Aria_Logger` with option-gated debug logging; replaced ad hoc `error_log()` calls across PHP services with logger calls and surfaced an admin toggle.
- Exposed advanced settings AJAX endpoints + React wiring so `aria_debug_logging` can be toggled from the UI.
- Normalized conversation logs during upgrade (v1.4.0) and ensured readers/writers respect the new `role` field.
- Added PHPUnit scaffolding + new specs for migration/context handling (`tests/test-aria-db-updater.php`, `tests/test-conversation-context.php`).
- Rebuilt `tests/test-aria-core.php` around `Aria_Core`, expanded table coverage, and added an InnoDB/ROW_FORMAT safeguard in the PHPUnit bootstrap for FULLTEXT compatibility.
- Consolidated the admin webpack entry to a single `admin.js` bundle, registered a legacy handle alias, and removed the stale `dist/admin-react.js*` artifacts while updating diagnostics/tests.
- Introduced `PageShell`/`SectionCard` layout primitives with scoped SCSS and refactored the Settings React page to drop inline styles in favor of the shared design tokens.
- Reworked the React dashboard to use the shared shell/section patterns, added reusable metric/stack grids, and replaced long inline style blocks with design-token-backed classes.
- Noticed header warnings (`Cannot modify header information`) when navigating the admin; likely caused by stray output in legacy PHP partials. Needs follow-up once the React UI refactor is complete.
- Migrated the Knowledge Base screen onto the new layout primitives, introduced dedicated grid/empty-state styles, and moved the knowledge-card visuals out of inline styles into SCSS.
- Updated the Personality editor to the shared shell, added trait-grid styling, and replaced its inline formatting with tokenized SCSS utilities.

## Test Status & Follow-ups
- WordPress PHPUnit now executes inside the Docker stack (`aria-wordpress`); `Tests_Aria_Core` passes with 47 assertions once `WP_TESTS_PHPUNIT_POLYFILLS_PATH` is exported to the Yoast polyfills.
- Local host runs still need `wordpress-tests-lib` on disk (or the container command) and the duplicate trial insert/FULLTEXT warnings logged during activation should be tidied up later.

## Next Steps
- Shift focus to remaining Phase 1 runtime items (e.g., finalizing cron smoke tests, release dry-run prep) per `docs/FINAL_EXECUTION_PLAN.md`.
- Revisit PHPUnit fixes after closing out other plan items.
- Continue migrating remaining React admin screens (Conversations, Content Indexing, AI Config) onto the shared layout primitives and schedule a Sass modernization pass to eliminate legacy `@import`/color helper warnings.
