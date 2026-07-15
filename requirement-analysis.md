# Requirement Analysis

## Selected Project Option

Option 1 — Backend-Heavy: Support Ticket Management System, implemented as a
custom Drupal module.

## My Understanding (in your own words)

This is a small internal tool for managing support tickets end to end.
Internal users (seeded, no self-registration) can raise a ticket, describe
the problem, set a priority, and assign it to someone. As work happens, the
ticket moves through a defined lifecycle — Open, In Progress, Resolved,
Closed — with the option to cancel from Open or In Progress. Anyone working
the ticket can leave comments on it, and users can search or filter the
ticket list rather than scrolling through everything.

The part the guide calls out as the real judgment test is the status state
machine: the backend must be the source of truth for which transitions are
legal, not the UI. If someone tries to jump straight from Open to Closed, or
reopen a Closed ticket, the backend has to reject it — the UI is just there
to reflect that clearly, not to enforce it.

In Drupal terms, this maps to a custom content entity (Ticket) with fields
for title, description, priority, status, assignee, and audit fields, a
Comment-module integration for ticket comments, Views for listing/searching,
and a validation layer (entity constraint or presave hook) that owns the
transition rules.

## Functional Requirements

- Create a ticket with title, description, priority, and assignee
- List all tickets
- View a single ticket's detail, including its comments
- Update ticket title, description, priority, and assignee
- Change ticket status only via a valid transition:
  - Open → In Progress
  - In Progress → Resolved
  - Resolved → Closed
  - Open → Cancelled
  - In Progress → Cancelled
- Add a comment to a ticket
- Keyword search across tickets
- Filter ticket list by status
- Data persists across restarts (no in-memory-only state)
- Required fields validated server-side; invalid input rejected
- UI surfaces meaningful errors (e.g. invalid transition, missing field)

## Non-Functional Requirements

- Backend is the enforcement point for business rules (status transitions,
  required fields) — not just client-side validation
- No secrets (DB credentials, API keys) committed to the repository
- Code follows Drupal coding standards or is explicitly documented where it
  deviates, with reasoning
- State-machine logic is covered by integration tests (Kernel or Functional)
- Setup is reproducible from the README alone (fresh clone → working app)

## Assumptions

- "User" is satisfied by Drupal's core User entity plus a role for internal
  staff; no custom user-management UI is required, per the guide
- A single Drupal instance serves as both backend and frontend (no separate
  SPA); this counts as a "full-stack combination" per the guide's allowed
  stacks
- Comments use Drupal's core Comment module attached to the Ticket bundle,
  rather than a fully custom Comment entity — documented as an architectural
  decision in design-notes.md
- Authentication beyond Drupal's default login is not required for Core
  (per the guide, auth is optional and only counts toward Stretch)
- Database choice (MySQL/MariaDB vs SQLite) will be finalized in
  design-notes.md / data-model.md before implementation begins

## Clarifications (questions for a product owner)

- Should ticket assignment be restricted to users with a specific role, or
  can any seeded user be assigned a ticket?
- Is there a maximum ticket description length, or any expected file/image
  attachment support (out of scope per guide, but worth confirming)?
- Should Cancelled tickets be excluded from default list views, or shown
  with a distinct visual state?
- Is there a required audit trail for status changes (who changed it, when),
  beyond the Comment on the ticket?
- Should comments be editable/deletable after posting, or append-only?

## Edge Cases

- Attempting a transition not in the allowed set (e.g. Resolved → In
  Progress, Closed → anything) must be rejected with a clear error
- Attempting to transition a ticket that doesn't exist / was deleted
  concurrently
- Two users updating the same ticket's status at the same time (race
  condition on transition validation)
- Creating a ticket with a missing required field (title, description)
- Searching/filtering with no matching results — empty state, not an error
- Adding a comment to a Closed or Cancelled ticket — decide and document
  whether this is allowed
- Very long keyword search strings or special characters in search input
- Assigning a ticket to a user that doesn't exist or has been deleted