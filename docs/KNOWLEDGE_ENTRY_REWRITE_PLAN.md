# Knowledge Entry Rewrite Plan

## Objectives
- Align the knowledge entry screen with the shared React admin architecture (PageShell/SectionCard patterns).
- Remove all inline styling in favour of a scoped SCSS module.
- Isolate AI generation workflow into testable, reusable units.
- Improve maintainability by splitting the page into focused components and hooks without regressing current behaviour.

## Major Workstreams

### 1. Component Architecture
- **Create `knowledge-entry-sections/` directory** housing:
  - `HeaderNotice` (page title + notice wrapper)
  - `GenerationWizard` (input, generating, review states)
  - `ManualEditor` panels split into logical chunks (Primary Info, AI Context, Organization)
  - `ActionFooter` for sticky action buttons
- Rewire `KnowledgeEntry.jsx` to orchestrate state, routing, and API calls only.

### 2. Styling Overhaul
- Introduce `src/scss/pages/_knowledge-entry.scss` for all knowledge-entry-specific styles (badges, grids, wizard states, sticky footer).
- Replace inline `style={{ ... }}` with semantic classes.
- Ensure spacing and typography leverage the shared token variables.

### 3. Workflow / Logic Cleanup
- Extract AI generation logic into a dedicated hook (`useKnowledgeGenerator`) handling:
  - Nonce retrieval / API calls
  - Step transitions (input → generating → review)
  - Error and notice reporting
- Normalize fetch/save flows to use shared API utilities (`fetchKnowledgeData`, `saveKnowledgeEntry`).
- Resolve lint warnings (unused imports, console logs, dependency arrays, nested ternaries).

### 4. Internationalization & Accessibility
- Replace literal ellipses with Unicode `…`.
- Add translator comments for placeholders and multi-line strings.
- Review headings/aria labels for wizard steps to ensure clarity.

### 5. Testing & Verification
- Add/Update Jest tests for new components and the generator hook.
- Confirm Playwright visual tests (if any) are updated to new layout.
- Manually verify:
  - Add vs. Edit flows
  - AI generation success/failure
  - Manual-only workflow
  - Cancel/Start Over actions

## Execution Notes
- Target this rewrite after current modularization sprint; budget a focused session due to intertwined UI states.
- Keep mock API behaviour until back-end endpoints are wired; wrap in TODO comments for future replacement.
- Coordinate with design to validate the new wizard layout before final SCSS polish.

## Deliverables
- Refactored components and SCSS file
- Updated docs (`AGENTS.md`, progress log) to reflect completion
- Passing lint/tests and confirmation of behaviour parity
