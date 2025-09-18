# Aria Consolidated Execution Plan

## Scope & Objectives
- Stabilize critical runtime paths (database upgrades, cron cleanup, logging) so activation/deactivation is safe across supported hosts.
- Consolidate the admin build into a single `aria-admin` bundle while preserving compatibility hooks and updating diagnostics/tests.
- Standardize the React admin UI via shared design primitives and component decomposition so every screen feels consistent and is maintainable.
- Restore conversation integrity (schema, context reconstruction) and harden background processing.
- Elevate automated coverage and release hygiene ahead of the production push.

## Phase 1 – Runtime Hardening (Week 1)
- Fix `Aria_DB_Updater` callback invocation (`self::{$callback}()`), add PHPUnit coverage for the upgrade map, and normalize `ARIA_VERSION`/`ARIA_DB_VERSION` and related assertions.
- Document the MySQL ≥5.7 (or MariaDB ≥10.4) requirement in activation/docs; no fallback path for 5.6 is supported.
- Replace unsupported `wp_cache_flush_group()` usage with guarded fallbacks (e.g., `wp_cache_flush()` or group-aware shims) so sites without persistent object cache do not fatal.
- Expand deactivation cleanup to unschedule **all** cron entries the plugin creates, including `aria_daily_summary_email`, `aria_cleanup_cache`, `aria_initial_content_indexing`, `aria_process_learning`, `aria_index_single_content`, and background processor hooks with arguments.
- Gate verbose logging (prompt dumps, IDs) behind a `aria_debug_logging` option or `WP_DEBUG`, ensuring no PII leaks to logs.

## Phase 2 – Admin Bundle Consolidation (Week 1–2)
- Update `webpack.config.js` to drop the duplicate `admin-react` entry; ensure emitted artifacts include `admin.js`, associated CSS, and any dynamic chunks (e.g., `src_js_admin_pages_KnowledgeEntry_jsx.js`).
- Adjust `admin/class-aria-admin.php` to enqueue/localize only the unified `aria-admin` handle and register a lightweight `aria-admin-react` alias for backward compatibility.
- Migrate internal tooling/tests: refresh `diagnostic-wordpress-integration.php`, Playwright fixtures in `tests/visual/`, and any scripts that fetch `dist/admin-react.js` so they target the consolidated asset.
- Rebuild and verify `dist/` outputs, then update `npm run dev/build` expectations and clean scripts to account for the new bundle set.
- Document rollback instructions and interim compatibility flags before removing legacy assets from version control.

## Phase 3 – Conversation Integrity & Background Processing (Week 2)
- Migrate stored conversation logs from `sender` to `role`, add backward-compatible reads in `save_to_conversation()` and `get_conversation_context()`.
- Add PHPUnit coverage for context reconstruction (last five messages) and queue guards to prevent duplicate background processor instantiation.
- Audit background processor scheduling/retry logic, ensuring singleton guards around `Aria_Background_Processor` and tests for stuck-task recovery.

## Phase 4 – UI Standardization (Week 2–3)
- Establish shared design primitives (`PageShell`, `SectionCard`, `FormActions`, etc.) under `src/js/admin/components/` and SCSS tokens for spacing, typography, and colors.
- Audit each React page to enforce the common layout (consistent headers, breadcrumbs, notice handling, loading/error states).
- Replace page-specific styles with scoped utility classes (`aria-*`) to keep bundle size under control and avoid visual drift.

## Phase 5 – Component Refactors (Week 3)
- Break down oversized pages (e.g., `Settings.jsx`, `Knowledge.jsx`, `ContentIndexing.jsx`, `Conversations.jsx`) into domain-driven subcomponents and hooks to reduce file length and improve testability.
  - ✅ Settings, Knowledge, Conversations, and Content Indexing now render via modular section directories that reuse shared primitives.
- Centralize AJAX/data-fetch patterns with `makeAjaxRequest()` helpers and shared loading/error components; eliminate `setTimeout` mocks once real endpoints are wired.
- Introduce lightweight Storybook-style preview or snapshot tests where beneficial for complex components.

## Phase 6 – Testing & Release Readiness (Week 4)
- Revise PHPUnit suite to rely on `Aria_Core`, add activation/deactivation smoke tests, and cover new cron cleanup/conversation code paths.
- Update Jest/Playwright suites to exercise the consolidated admin bundle and persistence flows; refresh fixtures impacted by schema/UI changes.
- Execute a release dry run (`npm run build`, `npm run test`, `composer test`, `npm run zip`), validate on staging, and refresh `README.md`/`TESTING-README.md` once the codebase stabilizes.

## Cross-Cutting Deliverables
- Update documentation (AGENTS.md, diagnostics) only after the new bundle ships; maintain a tracking checklist in the project board.
- Coordinate responsibilities across backend, frontend, and QA owners; review progress weekly and adjust timelines as UI work and refactors advance.
- Capture risk mitigation items (legacy host compatibility, data migration safety, bundle regression) and keep rollback scripts ready until the consolidation is proven stable.
