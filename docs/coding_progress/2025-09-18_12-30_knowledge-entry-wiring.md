# 2025-09-18 12:30 ET – Knowledge Entry React Wiring

## Summary
- Added server-provided configuration on the PHP partial so the React root exposes action, entry ID, AJAX endpoints, and dedicated generation nonce through DOM data attributes (with a matching `window.ariaKnowledgeEntry` fallback).
- Refactored `KnowledgeEntry.jsx` to consume that config, centralise redirect/URL handling, and pass the generator-specific nonce/url into a revamped `useKnowledgeGenerator` hook.
- Updated the hook to submit generation requests via `FormData` (matching WordPress expectations), enforce HTTP status checks, and reuse the shared notice/reset wiring while keeping the AI/manual flows predictable.
- Tweaked UI glue so footer controls reset through the hook, manual editor toggles clear notices properly, and the wizard/sections stay in sync with the new state machine.

## Verification
- `npm run lint`
