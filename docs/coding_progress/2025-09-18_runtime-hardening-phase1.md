# 2025-09-18 – Runtime Hardening Phase 1 Progress

## Summary
- Locked runtime requirements: activation now enforces MySQL ≥5.7 (or MariaDB ≥10.4) before proceeding.
- Added `Aria_Logger` with option-gated debug logging; replaced ad hoc `error_log()` calls across PHP services with logger calls and surfaced an admin toggle.
- Exposed advanced settings AJAX endpoints + React wiring so `aria_debug_logging` can be toggled from the UI.
- Normalized conversation logs during upgrade (v1.4.0) and ensured readers/writers respect the new `role` field.
- Added PHPUnit scaffolding + new specs for migration/context handling (`tests/test-aria-db-updater.php`, `tests/test-conversation-context.php`).

## Test Status & Follow-ups
- WordPress test environment (Docker) provisioned; PHPUnit currently fails because legacy `Tests_Aria_Core` still instantiates the removed `Aria` class and dbDelta hits temporary FULLTEXT limits.
- Pending actions: update core tests to use `Aria_Core`, adjust db expectations for FULLTEXT creation, and re-run PHPUnit once fixed.

## Next Steps
- Shift focus to remaining Phase 1 runtime items (e.g., finalizing cron smoke tests, release dry-run prep) per `docs/FINAL_EXECUTION_PLAN.md`.
- Revisit PHPUnit fixes after closing out other plan items.
