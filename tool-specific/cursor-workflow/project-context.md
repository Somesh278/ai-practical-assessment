# Project Context

## Project
Support Ticket Management System — Drupal implementation (Project Option 1,
Backend-heavy, per AI Capability Exercise Participant Guide).

## Stack
Drupal core + custom module (`ticket_management`), Comment module (reused
for ticket comments), Views (list/search/filter), Form API, MySQL/MariaDB
or SQLite (finalize in design-notes.md).

## Module Structure
```
src/modules/custom/ticket_management/
  ticket_management.info.yml
  ticket_management.module
  ticket_management.routing.yml
  src/Entity/Ticket.php
  src/TicketStatusTransitionValidator.php
  config/install/
  tests/src/Kernel/
```

## Conventions
- Follow Drupal coding standards (drupal/coder)
- Status transition rules live in a single service/validator — never
  duplicated in the frontend/forms
- No secrets in code or config — use settings.php overrides / env vars

## Key Architectural Decisions
- Ticket is a custom content entity, not a node — chosen for a leaner,
  purpose-built schema
- Comments reuse Drupal core's Comment module rather than a custom Comment
  entity, to avoid reinventing threading/permissions Drupal already provides
- Status transitions enforced server-side via presave validation, not
  trusted from client input

## How to Reference This File
`@project-context.md` at the start of any Cursor session working on this
module, before asking for design or code help.
