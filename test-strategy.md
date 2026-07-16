# Test Strategy

## Test Scope

Core functionality: entity CRUD, status transition enforcement,
field-level edit lock, comment creation, search/filter, access control.

## Unit Tests

Not planning separate unit tests — the transition and edit-lock logic
live inside entity constraints, which need an entity context to run
against anyway. Kernel tests cover this more realistically than mocking
the entity API would.

## Component Tests

N/A — no standalone frontend framework, this is server-rendered Drupal.
Covered by Kernel/Functional tests instead.

## API / Integration Tests (mandatory tier)

Kernel tests in `tests/src/Kernel/`:

- All 5 valid transitions succeed (open→in_progress, in_progress→resolved,
  resolved→closed, open→cancelled, in_progress→cancelled)
- Every other from→to pair is rejected, including: skip-ahead transitions
  (open→resolved, in_progress→closed), backward transitions
  (in_progress→open, resolved→in_progress), and any transition attempted
  from closed or cancelled
- Same-status save (e.g. editing description without changing status)
  succeeds and is not flagged as an invalid transition
- Status submitted on create other than `open` is ignored/rejected
- Required-field validation rejects a ticket missing title, description,
  or priority
- Field-diff constraint rejects a save that changes title/description/
  priority/assignee while status is resolved/closed/cancelled — this is
  the one that actually proves the Drush/API bypass is closed, not just
  the form
- Field-diff constraint allows a status-only change while resolved
  (resolved → closed), confirming status isn't accidentally locked too
  early

## Edge Case Tests

- Transition attempt on a Closed ticket
- Comment posted on a Closed/Cancelled ticket succeeds (this is the
  decided behavior, not an open question — see requirements-analysis.md)
- Search with no results returns an empty set, not an error
- Assigning a ticket to a blocked user is rejected

## Tests Not Covered (and why)

- **Concurrent status updates.** Two users transitioning the same ticket
  at once isn't tested — no locking is implemented for Core (documented
  as an accepted risk in requirements-analysis.md), so there's nothing
  for a test to assert beyond "last write wins," which isn't a
  meaningful test. Stretch candidate if locking gets built later.
- **Comment editing/deletion.** Out of scope — Core only requires
  posting comments, not managing them after the fact.

