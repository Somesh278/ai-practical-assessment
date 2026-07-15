# Support Ticket Management System

A support ticket management system implemented as a custom Drupal module,
built for the AI Capability Exercise (Project Option 1 — Backend-heavy).
Internal users create, update, comment on, search, and progress tickets
through an enforced status lifecycle.

## Stack

- Drupal core (version TBD)
- Custom module: `ticket_management`
- Comment module (core) for ticket comments
- Views for list/search/filter
- Database: TBD (MySQL/MariaDB or SQLite)
- Testing: PHPUnit (Drupal Kernel tests)

## Setup

1. _TBD — Drupal install steps_
2. Enable the custom module: `drush en ticket_management`
3. Run seed data: _TBD_
4. Run tests: `phpunit --group ticket_management` (or Drupal test runner
   equivalent)

## Repository Structure

See the full lifecycle documentation in the root of this repo:
requirements-analysis.md, acceptance-criteria.md, design-notes.md,
data-model.md, api-contract.md, ui-flow.md, test-strategy.md, and the
`ai-prompts/` and `tool-specific/cursor-workflow/` folders for AI workflow
evidence.

The actual Drupal module lives under `src/modules/custom/ticket_management/`.

## Status State Machine

```
Open → In Progress → Resolved → Closed
Open → Cancelled
In Progress → Cancelled
```

Enforced server-side — see design-notes.md and
tool-specific/cursor-workflow/spec.md.
