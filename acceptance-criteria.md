# Acceptance Criteria

## Core
- [ ] A user can create a ticket via the UI (title, description, priority, assignee)
- [ ] A user can view all tickets from the database
- [ ] A user can open a ticket detail view (including comments)
- [ ] A user can update ticket fields and reassign
- [ ] A user can add comments to a ticket
- [ ] Status changes only through valid transitions; invalid ones are rejected server-side
  - [ ] Open → In Progress
  - [ ] In Progress → Resolved
  - [ ] Resolved → Closed
  - [ ] Open → Cancelled
  - [ ] In Progress → Cancelled
  - [ ] All other transitions rejected (e.g. Closed → anything, Resolved → In Progress)
- [ ] Keyword search and status filter work
- [ ] Data remains available after restart
- [ ] Backend validation prevents invalid records (missing required fields)
- [ ] No secrets committed to the repo

## Validation
- [ ] Required fields (title, description) enforced server-side, not just in the form UI
- [ ] Invalid status transition attempts return a clear, actionable error
- [ ] Assigning a ticket to a nonexistent user is rejected

## Error Handling
- [ ] UI surfaces validation errors clearly (not a generic 500/white screen)
- [ ] Invalid transition attempts show a specific message, not a silent failure
- [ ] Empty search/filter results show an empty state, not an error

## Testing
- [ ] Kernel/Functional tests cover every valid transition (succeeds)
- [ ] Kernel/Functional tests cover invalid transitions (rejected)
- [ ] At least one test covers required-field validation

## Documentation
- [ ] README has working setup instructions from a fresh clone
- [ ] data-model.md documents all entity fields and types
- [ ] design-notes.md explains the state machine enforcement approach
