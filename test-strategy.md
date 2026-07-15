# Test Strategy

## Test Scope

Core functionality: entity CRUD, status transition enforcement, comment
creation, search/filter.

## Unit Tests

_TBD — candidates for unit testing: transition validator logic in
isolation, if extracted as a pure function/service._

## Component Tests

_N/A in current scope (no standalone frontend component framework) —
covered by Kernel/Functional tests instead._

## API / Integration Tests

**Mandatory tier (per guide):** Kernel tests proving:
- Each of the 5 valid transitions succeeds
- Every other transition is rejected
- Required-field validation rejects incomplete tickets

## Edge Case Tests

- Transition attempt on a Closed ticket (terminal state)
- Comment on a Cancelled ticket (decide + test expected behavior)
- Search with no results

## Tests Not Covered (and why)

_Document anything intentionally out of scope for Core, e.g. concurrency/
race-condition testing on simultaneous status updates — Stretch candidate._
