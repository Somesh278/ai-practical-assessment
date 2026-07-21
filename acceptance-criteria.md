# Acceptance Criteria

## Core
- [x] A user can create a ticket via the UI (title, description, priority, assignee) — verified manually
- [x] A user can view all tickets from the database — Views-backed /tickets list
- [x] A user can open a ticket detail view (including comments) — /ticket/{id}
- [x] A user can update ticket fields and reassign — via edit form, subject to edit-lock when resolved/closed/cancelled
- [x] A user can add comments to a ticket — including on closed/cancelled tickets (deliberate, per requirements-analysis.md)
- [x] Status changes only through valid transitions; invalid ones are rejected server-side
  - [x] Open → In Progress
  - [x] In Progress → Resolved
  - [x] Resolved → Closed
  - [x] Open → Cancelled
  - [x] In Progress → Cancelled
  - [x] All other transitions rejected (e.g. Closed → anything, Resolved → In Progress) — 12 cases tested
- [x] Keyword search and status filter work — title/description OR, status exact-match AND, verified against config and manual testing
- [x] Data remains available after restart — MySQL/MariaDB persistence, standard Drupal entity storage
- [x] Backend validation prevents invalid records (missing required fields) — title/description/priority required, tested
- [x] No secrets committed to the repo — DB credentials never written into README/setup-notes, placeholders only

## Validation
- [x] Required fields (title, description) enforced server-side, not just in the form UI — base field `setRequired(TRUE)`, tested
- [x] Invalid status transition attempts return a clear, actionable error — "Cannot move a ticket from @from to @to"
- [x] Assigning a ticket to a nonexistent user is rejected — entity reference validation on the assignee field

## Error Handling
- [x] UI surfaces validation errors clearly (not a generic 500/white screen) — Drupal's standard form error messaging
- [x] Invalid transition attempts show a specific message, not a silent failure
- [x] Empty search/filter results show an empty state, not an error — "No tickets match" configured empty text

## Testing
- [x] Kernel/Functional tests cover every valid transition (succeeds) — testValidTransitions, 5 cases
- [x] Kernel/Functional tests cover invalid transitions (rejected) — testInvalidTransitionsRejected, 12 cases
- [x] At least one test covers required-field validation — testRequiredFieldValidation, 3 cases (title/description/priority)

## Documentation
- [x] README has working setup instructions from a fresh clone — DB creation, site:install, module enable, seed, test run
- [x] data-model.md documents all entity fields and types — Ticket, Comment, User, transition table, edit-lock table
- [x] design-notes.md explains the state machine enforcement approach — entity constraint over presave, original/loadUnchanged handling

## Beyond the original checklist (found and fixed during implementation)
- [x] Field-level edit-lock actually enforced at save-time (not just entity-wide access) — closes a Drush/API bypass
- [x] Assignee (not just admin) can transition their own assigned tickets — Issue 4 fix
- [x] Edit link/tab actually visible to users with real update access — Issue 5 fix
- [x] Unassigned tickets correctly restricted to admin-only transition — by design, confirmed via manual testing
