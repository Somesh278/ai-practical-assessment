# AI Prompts — Testing

_Log prompts for this activity as you go. Each entry should capture the
prompt, AI's response summary, what you accepted/changed/rejected, and why._

## Prompt 1
**Prompt:** 
**AI Response Summary:** 
**Accepted:** 
**Changed:** 
**Rejected:** 
**Why:** 

## Prompt 1 — Kernel tests (Agent Mode)

**Prompt:**
"@test-strategy.md @data-model.md — Write Kernel tests in
tests/src/Kernel/ for the Ticket entity, covering everything in
test-strategy.md's API/Integration Tests list: 5 valid transitions,
invalid transitions rejected, same-status save succeeds, create forces
open status, required-field validation, edit-lock rejects non-status
field changes via direct entity save, edit-lock allows status-only
change while resolved, priority correctly included in the edit-lock."

**AI Response Summary:**
Generated TicketEntityTest.php with data-provider-driven coverage: 5
valid transitions, 12 invalid-transition cases (skip-ahead, backward,
from closed, from cancelled), same-status save, create-forces-open,
required-field validation for title/description/priority, edit-lock
rejection across all EDIT_LOCKED_STATUSES × all 4 locked fields (12
cases via data provider), status-only change allowed while resolved, and
a dedicated test specifically for priority being rejected while resolved
(directly targeting the bug found via manual UI testing earlier).

**Test run result:** 36 tests, 348 assertions, all passing.
```
./vendor/bin/phpunit -c phpunit.xml web/modules/custom/ticket_management/tests/src/Kernel/TicketEntityTest.php
OK (36 tests, 348 assertions)
```

**Accepted:**
Full suite as generated — coverage matches test-strategy.md's list
completely, including the priority edit-lock case that came out of manual
bug-finding, not just the original spec.

**Changed:**
None needed.

**Rejected:**
None.

**Why:**
This is a stronger suite than a literal reading of test-strategy.md
required — it used data providers to cover the full combinatorial space
(12 invalid-transition cases, 12 edit-lock-field cases) instead of a
token example per category, and it specifically pinned down the priority
bug found earlier via manual testing as its own named test, not folded
into a generic loop where a regression could slip by unnoticed. One minor
fragility noted (not fixed): testInvalidTransitionsRejected and
testEditLockRejectsDirectFieldChange both assert on violations->get(0),
assuming the first violation is the relevant one — fine today since each
scenario only produces one violation, but would need updating if a future
change ever produces multiple violations for the same save.
