# 2025-09-17 – Runtime Hardening Phase 1 Kickoff

## Summary
- Replaced direct `wp_cache_flush_group()` usage with a guarded helper in `Aria_Cache_Manager`, preventing fatals on hosts without object cache support.
- Updated `Aria_Background_Processor` to reuse the new helper while still clearing the per-entry cache key.
- Corrected `Aria_DB_Updater` callback invocation and added PHPUnit coverage to ensure the updater advances `aria_db_version` safely.
- Normalized runtime version numbers (`ARIA_VERSION`, `ARIA_DB_VERSION`, plugin header) and updated tests/documentation guidelines.
- Began conversation log normalization: new entries store a `role`, legacy logs are upgraded via the 1.4.0 DB migration, and consumers now fall back gracefully.
- Locked in the database requirement (MySQL ≥5.7 / MariaDB ≥10.4) with activation guards and updated documentation.
- Introduced `Aria_Logger` gating so verbose logging only emits when debug mode is enabled, while preserving error-level visibility.

## Files Touched
- `includes/class-aria-cache-manager.php`
- `includes/class-aria-background-processor.php`
- `includes/class-aria-db-updater.php`
- `tests/test-aria-db-updater.php`

## Next Steps
- Verify cron cleanup with broader activation/deactivation smoke tests.
- Decide on MySQL compatibility strategy and normalize version constants/documentation.
- Begin conversation log migration work once runtime fixes are in place.
