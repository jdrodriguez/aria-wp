# Knowledge Entry Rewrite Plan

## Objectives
- Align the knowledge entry screen with the shared React admin architecture (PageShell/SectionCard patterns).
- Remove all inline styling in favour of a scoped SCSS module.
- Isolate AI generation workflow into testable, reusable units.
- Improve maintainability by splitting the page into focused components and hooks without regressing current behaviour.

## Major Workstreams

### 1. Component Architecture
- **Done:** Added `knowledge-entry-sections/` directory containing `HeaderNotice`, `AiGenerationWizard`, `ManualEditor`, `ActionFooter`, and `KnowledgeEntryLoading` while rewriting `KnowledgeEntry.jsx` into an orchestrator.

### 2. Styling Overhaul
- **Done:** Created `src/scss/pages/_knowledge-entry.scss`, removed all inline styling, and aligned spacing/typography with shared tokens.

### 3. Workflow / Logic Cleanup
- **Done:** Introduced `useKnowledgeGenerator` hook for nonce-aware AI workflow, normalized API calls, and resolved existing lint issues.

### 4. Internationalization & Accessibility
- **Done:** Replaced literal ellipses, simplified placeholders, and ensured wizard copy/headings follow translation/accessibility conventions.

### 5. Testing & Verification
- **In Progress:** Jest/Playwright coverage still relies on legacy flows. Manual QA checklist completed for add/edit, AI success/failure, manual-only, and cancel/start-over scenarios; automated updates remain future work.

## Execution Notes
- Mock API behaviour retained; annotate TODOs when wiring live endpoints.
- Coordinate with design for further polish if the wizard flow receives new assets.

## Deliverables
- Refactored components/hooks and scoped SCSS (✅)
- Documentation updates (`AGENTS.md`, progress log) (✅)
- Lint suite passing (✅)
- Automated test updates (🔄 follow-up)
