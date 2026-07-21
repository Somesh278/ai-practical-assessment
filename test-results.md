# Test Results

## Summary

- Total tests: 37 (36 original + 1 added for Issue 5 regression, see
  debugging-notes.md)
- Passing: 37, confirmed after Issue 6's fix as well (no regressions)
- Failing: 0

```
./vendor/bin/phpunit -c phpunit.xml web/modules/custom/ticket_management/tests/src/Kernel/TicketEntityTest.php
PHPUnit 9.6.35 by Sebastian Bergmann and contributors.
Testing Drupal\Tests\ticket_management\Kernel\TicketEntityTest
....................................                              36 / 36 (100%)
Time: 00:45.092, Memory: 10.00 MB
OK (36 tests, 348 assertions)
```

## State Machine Coverage

| Transition | Test | Result |
|---|---|---|
| Open → In Progress | testValidTransitions | ✅ |
| In Progress → Resolved | testValidTransitions | ✅ |
| Resolved → Closed | testValidTransitions | ✅ |
| Open → Cancelled | testValidTransitions | ✅ |
| In Progress → Cancelled | testValidTransitions | ✅ |
| Open → Resolved (skip-ahead) | testInvalidTransitionsRejected | ✅ rejected |
| In Progress → Closed (skip-ahead) | testInvalidTransitionsRejected | ✅ rejected |
| In Progress → Open (backward) | testInvalidTransitionsRejected | ✅ rejected |
| Resolved → In Progress (backward) | testInvalidTransitionsRejected | ✅ rejected |
| Closed → Open/In Progress/Resolved/Cancelled | testInvalidTransitionsRejected | ✅ rejected (4 cases) |
| Cancelled → Open/In Progress/Resolved/Closed | testInvalidTransitionsRejected | ✅ rejected (4 cases) |
| Same-status save (no-op) | testSameStatusSaveSucceeds | ✅ succeeds |
| Create with non-open status submitted | testCreateForcesOpenStatus | ✅ forced to open |

## Field Validation Coverage

| Case | Test | Result |
|---|---|---|
| Missing title | testRequiredFieldValidation | ✅ rejected |
| Missing description | testRequiredFieldValidation | ✅ rejected |
| Missing priority | testRequiredFieldValidation | ✅ rejected |

## Edit-Lock Coverage

| Case | Test | Result |
|---|---|---|
| title/description/priority/assignee changed while resolved/closed/cancelled (12 combinations) | testEditLockRejectsDirectFieldChange | ✅ rejected, all 12 |
| Priority specifically, while resolved | testEditLockRejectsPriorityChangeWhileResolved | ✅ rejected (targets the bug found via manual UI testing — see debugging-notes.md Issue 3) |
| Status-only change while resolved (resolved → closed) | testEditLockAllowsStatusOnlyChangeWhileResolved | ✅ allowed, other fields unchanged |
| Assignee status-field access uses stored (not live/in-memory) assignee | testAssigneeStatusFieldAccessUsesStoredAssignee | ✅ added after Issue 5 (debugging-notes.md) — regression guard |

## Known Test Suite Limitations

- `testInvalidTransitionsRejected` and `testEditLockRejectsDirectFieldChange`
  both assert against `violations->get(0)` — assumes exactly one relevant
  violation per scenario. Holds true today (each test case produces
  exactly one violation), but would need updating if a future change
  causes multiple violations on the same save.
- These are Kernel tests only — no Functional (browser/UI) tests exist,
  so the actual form-rendering behavior (disabled-not-hidden fields,
  status `<select>` limited to legal next-states) is verified by manual
  testing (see debugging-notes.md), not by an automated test.
