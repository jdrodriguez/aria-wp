# Aria Plugin Improvement Plan

## Scope & Objectives
- Stabilize database upgrades and scheduled automation so activation/deactivation is reliable on supported hosts.
- Restore full conversational context and ensure AI responses persist correctly for admins and site visitors.
- Finish the React-based admin experience so settings, design, and AI configuration screens are production ready.
- Reduce operational risk by aligning environment requirements, taming logging noise, and modernizing the automated test suite.

## Phase 1 – Critical Runtime Fixes (Week 1)
- **Fix database updater fatal** (`includes/class-aria-db-updater.php`): replace `self::$callback()` with `self::{$callback}()`; add unit coverage that exercises the upgrade map.
- **Clarify minimum database version**: ✅ Require MySQL ≥5.7 (or MariaDB ≥10.4); update docs/activation guards accordingly.
- **Normalize plugin versions**: set the plugin header, `ARIA_VERSION`, `ARIA_DB_VERSION`, and PHPUnit assertions to the same release number; document release versioning in `AGENTS.md`.
- **Cron hygiene on deactivate**: unschedule `aria_daily_summary_email`, `aria_cleanup_cache`, `aria_initial_content_indexing`, and any processor hooks spawned by `Aria_Background_Processor`; confirm with automated tests.

## Phase 2 – Conversation Integrity (Week 2)
- **Conversation log schema fix**: migrate stored messages to use `role` instead of `sender`; adjust `save_to_conversation()` and add fallback for legacy rows.
- **Context reconstruction tests**: add PHPUnit cases validating `get_conversation_context()` returns last five role-tagged entries.
- **Background processor singleton guard**: prevent repeated instantiation on cron-tick (static flag or service container) to avoid duplicate queue processing.
- **Logging policy update**: wrap verbose `error_log()` statements (prompt dumps, IDs) behind a `aria_debug_logging` option or `WP_DEBUG` check; ensure PII is never logged.

## Phase 3 – Admin UX Completion (Weeks 3–4)
- **Real AJAX wiring for settings pages**: replace `setTimeout` mocks in `src/js/admin/pages/*` with the `makeAjaxRequest()` helpers; surface loading/error states via `<Notice>` components.
- **Knowledge base management**: implement create/update/delete flows in the React UI, including tag parsing and optimistic updates.
- **Regression coverage**: extend Jest to cover `utils/api.js` request builders and add Playwright cases that verify settings persist across reloads.
- **Accessibility & performance sweep**: audit admin bundles for console logging, confirm Suspense fallbacks cover all entry points, and ensure CSS bundles remain <200 KB gzipped after new work.

## Phase 4 – Testing & Release Readiness (Weeks 4–5)
- **Revamp PHPUnit suite**: replace `new Aria()` usage with `Aria_Core`, update table assertions (knowledge_entries, chunks, etc.), and add activation/deactivation smoke tests.
- **Add targeted PHP integration tests**: cover new cron unscheduling, conversation context, and background processor guards.
- **Document QA workflow**: update `README.md` and `TESTING-README.md` with Playwright/Jest/PHPUnit commands, required services (WP env, node), and release checklist.
- **Release dry run**: execute `npm run build`, `composer test`, `npm run test`, and package via `npm run zip`; verify on a staging WP install.

## Risks & Mitigation
- **Legacy host compatibility**: track MySQL decision—if 5.6 support is dropped, announce early and provide migration guidance; if retained, ensure schema changes are covered by automated tests.
- **Data migration safety**: back up `aria_conversations` before altering stored JSON; provide WP-CLI script to reprocess logs if needed.
- **Timeline pressure**: Phases 3–4 can overlap for different contributors; maintain a shared checklist in the repo’s project board to avoid blocking on UI changes.

## Ownership & Tracking
- Assign feature owners in the project board: core PHP (Backend), admin React (Frontend), QA/tooling (DevEx).
- Track each task as GitHub issues linked to this plan; close issues only after code, tests, and documentation land.
- Revisit plan weekly during standup to adjust scope or reprioritize outstanding work.
