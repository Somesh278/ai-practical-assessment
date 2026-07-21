# Reflection

## What I Built

A Support Ticket Management System implemented as a custom Drupal 10
module (Project Option 1, backend-heavy). Users, Tickets, and Comments,
with a status state machine (Open → In Progress → Resolved → Closed, plus
two Cancel paths) enforced server-side via an entity constraint, a
field-level edit-lock that keeps resolved/closed/cancelled tickets
visible but non-interactive, role-based access split between general
staff and status-transition rights (assignee or admin), Views-backed
search and filtering, and Comment-module integration that stays available
even on closed tickets. 46 Kernel tests cover the state machine, edit
lock, required-field validation, and access rules.

## How I Used AI (across the lifecycle)

- **Requirements/design/planning:** I drafted these myself first, then
  used Cursor's Plan Mode specifically to critique what I'd written, not
  to generate it — two full review rounds on requirements-analysis.md,
  one on design-notes.md/data-model.md together. This kept "my own
  understanding" genuinely mine while still catching real gaps I'd
  missed (see below).
- **Code generation:** Cursor (Agent Mode) generated the actual module —
  entity, forms, access handler, constraints, Views config, Comment
  integration, seed data, and tests — from the locked design docs as the
  spec.
- **Testing:** AI-generated Kernel tests, verified against
  test-strategy.md's coverage list rather than accepted at face value.
- **Debugging:** a mix — some issues were caught by Cursor itself during
  generation (e.g. the date-formatter injection bug), most were caught
  by me manually testing the actual UI rather than trusting Cursor's own
  smoke-test reports, then diagnosed collaboratively.
- **Code review:** two rounds — an early field-by-field check against
  data-model.md, and a final full-codebase pass specifically prompted to
  hunt for a known bug pattern rather than do a generic review.

## What AI Helped With Most

Translating locked design decisions into working Drupal code quickly —
once design-notes.md and data-model.md were settled, generating the
entity, forms, and constraints from that spec was fast and largely
correct on the first pass. It was also genuinely useful at *finding*
problems when I gave it a specific thing to look for (a known bug
pattern, a specific contradiction to check) rather than a vague "review
this" — the sharpest catches in this project (the closed-ticket access
bug, the requirements/design contradictions) came from narrow, targeted
prompts, not open-ended ones.

## What AI Got Wrong

Several concrete, real mistakes, not manufactured ones:

1. **Priority field not locked on resolved tickets** (Issue 2) —
   Cursor's edit-lock implementation missed priority while correctly
   locking title/description. Its own smoke tests reported the edit-lock
   as working; I only found this by manually opening a resolved ticket's
   edit form and checking every field myself.
2. **Hidden vs. disabled fields** — the initial edit-lock implementation
   hid locked fields entirely (denying field access) instead of showing
   them disabled with their current values, which is what I actually
   wanted. Not wrong per the literal spec at the time, but a UX call
   Cursor made that didn't match intent, caught by clicking through it.
3. **Live-entity-vs-stored-entity bug** (Issue 4) — the assignee check
   for status-transition access read the in-memory form entity instead
   of the stored/original one, so an assignee could be incorrectly
   denied access to transition their own ticket depending on form
   rebuild state. Subtle, and Cursor's own verification didn't catch it;
   I found it by testing as an actual non-admin assignee.
4. **Missing UI wiring, reported as done** — after fixing the assignee
   access bug, there was still no Edit link/tab anywhere in the UI at
   all (Issue 5) — a gap in what was built, not what was checked, that
   only surfaced because I kept testing as a real user instead of
   trusting that "access now works" meant "the button exists."
5. **A real, higher-stakes access bug found only via targeted re-review**
   (final code-review pass) — `checkAccess('update')` granted update
   access via a general staff permission even when the ticket was
   closed/cancelled, contradicting the design's own "deny entirely" rule.
   This one is notable because the code looked correct in isolation; it
   only broke when checked against the *combination* of permission and
   status, and a generic review pass earlier in the project hadn't
   caught it.

The throughline: Cursor's own self-reported verification (smoke tests,
"Verified" checklists) was consistently less reliable than actually
clicking through the UI as a real user in a specific role. Config being
present, or a unit-level check passing, wasn't the same as the feature
working end to end for the person it was built for.

## How I Validated AI Output

- Field-by-field checks of generated entity code against data-model.md,
  not just skimming for plausibility
- Manual UI testing in every relevant role (staff, assignee, admin,
  non-admin) at every major milestone, not just after the "final" build
- Kernel tests checked against test-strategy.md's actual coverage list,
  not accepted just because they passed
- A final code-review pass deliberately scoped to hunt for a specific
  known bug class, which is what surfaced the most serious remaining bug

## What I Would Improve Next Time

- Manual UI testing per role should happen *immediately* after each
  access-control change, not batched up — several bugs (5, 6, and the
  final closed-ticket bug) were all variations on the same theme
  (access logic that's subtly wrong under a specific role/status
  combination) and could likely have been caught earlier with a standing
  checklist of "test as: staff, assignee, admin, on each status" run
  after every access-handler change, not just at natural checkpoints.
- I'd push targeted, pattern-specific review prompts earlier and more
  often — the technique that found the highest-value bugs (final
  code-review pass) was available the whole time but only used once,
  right at the end.

## Reusable Workflow

The pattern that worked best across this whole project: **write the
draft myself, then use AI to critique a specific, narrow thing about it
— not "review this," but "check this doc against that other doc for
contradictions" or "check the code for this specific bug pattern."**
That discipline is directly reusable on a real project, independent of
which AI tool is involved. `tool-specific/cursor-workflow/cursor-rules-
or-instructions.md` and the per-activity `ai-prompts/` logs capture the
concrete version of this for Cursor specifically.
