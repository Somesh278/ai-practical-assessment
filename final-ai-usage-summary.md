# Final AI Usage Summary

_A concise closing summary of AI usage across the whole project — distinct
from reflection.md's narrative form, this is meant as a quick-reference
tally/summary a reviewer can scan fast._

## Usage by Phase

| Phase | AI Tool Used | Extent |
|---|---|---|
| Requirement Analysis | Cursor (Plan Mode) | I drafted first; 2 rounds of AI critique against my own draft, folded in selectively |
| Design | Cursor (Plan Mode) | I drafted first; 1 round of AI critique on design-notes.md/data-model.md together |
| Code Generation | Cursor (Agent Mode) | Primary generation of all module code from locked design docs; manually validated field-by-field |
| Testing | Cursor (Agent Mode) | AI-generated Kernel tests; checked against test-strategy.md's coverage list, not accepted on trust |
| Debugging | Cursor | 1 issue self-caught by Cursor during generation; 5 issues found via my own manual UI testing, then diagnosed with Cursor's help |
| Code Review | Cursor (Plan Mode) | 2 rounds — early field-level check, final full-codebase pass targeted at a specific known bug pattern |

## Key AI Corrections Made

- **Priority field not locked on resolved tickets** (Issue 2) — found by
  manually testing every field on a resolved ticket's edit form, not by
  trusting Cursor's own smoke-test report, which had only checked title.
- **Live-entity-vs-stored-entity assignee bug** (Issue 4) — the
  status-transition access check read the in-memory form entity instead
  of the stored one, incorrectly denying assignees access to their own
  tickets under certain form-rebuild conditions. Found by testing as an
  actual non-admin assignee, not by code review alone.
- **Closed-ticket update access bug** (Issue 6, final code-review pass) —
  general staff permission incorrectly granted update access on
  closed/cancelled tickets, contradicting the documented "deny entirely"
  rule. This one is notable because it was invisible to an earlier, more
  general review pass and only surfaced when I specifically asked Cursor
  to check for a narrow, defined condition (permission + status
  combination) rather than "review this."

## What This Suggests About the Workflow

Targeted, narrow prompts ("check X against Y for contradiction," "hunt
for bug pattern Z") consistently outperformed open-ended review requests
at finding real issues. Cursor's self-reported verification (smoke
tests, "Verified" checklists) was reliable for confirming config/plugin
registration, but not a substitute for manually testing the actual
feature as a real user in the actual role it was built for — every
significant bug in this project was ultimately caught that way, not by
an AI-reported test passing.
