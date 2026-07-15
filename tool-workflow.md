# Tool Workflow

## 1. Primary AI Tool Used

Cursor, used as the primary development environment for this project. All code generation, refactoring, and inline assistance for the Support Ticket Management System (Drupal implementation) went through Cursor.

## 2. How I Provide Project Context to the Tool

- `@`-referenced key planning docs (`requirements-analysis.md`, `design-notes.md`, `data-model.md`, `spec.md`) directly into chat before asking for design or code help, rather than relying on Cursor inferring intent from the codebase alone.
- Maintained `tool-specific/cursor-workflow/project-context.md` as a standing reference describing the Drupal module structure, naming conventions, and architectural decisions (e.g. Comment module reuse, custom entity for Ticket), so context didn't need to be re-explained each session.
- Used `cursor-rules-or-instructions.md` to encode Drupal coding standards and project-specific constraints (e.g. "status transitions must be enforced server-side, never trust client input") so they applied automatically across sessions rather than being repeated per prompt.

## 3. How I Use AI for Requirement Analysis

_To be filled in with actual Cursor session — sharing `requirements-analysis.md` via `@`-reference and asking Cursor to review for gaps/ambiguities before any design work started._

## 4. How I Use AI for Planning and Design

_To be filled in — design-notes.md / data-model.md / spec.md drafting sessions._

## 5. How I Use AI for Code Generation

_To be filled in — module scaffold, entity definitions, forms, Views, status transition validator._

## 6. How I Validate AI-Generated Code

_To be filled in — what I checked manually before accepting (e.g. did the transition validator actually reject every invalid pair, not just the ones I tested; did generated field definitions match data-model.md)._

## 7. How I Use AI for Testing

_To be filled in — Kernel/Functional test generation for the state machine, and how I verified the tests actually exercised the right cases._

## 8. How I Use AI for Debugging

_To be filled in — real issues hit during implementation, logged in debugging-notes.md, cross-referenced here._

## 9. How I Use AI for Code Review

_To be filled in — self-review pass using Cursor before considering Core complete._

## 10. What Information I Avoid Sharing Unnecessarily with AI Tools

- No real database credentials, API keys, or `.env` values pasted into prompts — placeholders only, even when asking for config examples.
- No production data — all sample/seed data used in prompts is synthetic.
- Avoided pasting entire files when only a specific function or field definition was relevant, to keep unrelated code/business logic out of the AI's context unnecessarily.

## 11. How I Would Reuse This Workflow in a Real Project

- The `project-context.md` + `cursor-rules-or-instructions.md` pattern is directly reusable — front-loading standing context and constraints once rather than repeating them per prompt scales to larger, longer-lived projects.
- Writing requirements/design docs before prompting for code (rather than prompting first and documenting after) produces AI output that's easier to validate against a written spec, and is worth carrying into real project work regardless of AI tool.
- The discipline of logging prompts by activity (`ai-prompts/planning.md`, `implementation.md`, etc.) is useful beyond this assessment — it creates a searchable record of *why* a design decision was made, which normal commit messages don't capture.
