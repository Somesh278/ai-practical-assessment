# AI Prompts — Design

## Prompt 1 — Design consistency check (Plan Mode)

**Prompt:**
"@design-notes.md @data-model.md @requirements-analysis.md — I've written
these two design docs myself based on decisions already locked in
requirements-analysis.md. Don't rewrite them. Just check: (1) does
anything contradict a decision already made in requirements-analysis.md,
(2) is TicketAccessControlHandler concrete enough to implement directly,
(3) is there a Drupal-specific gotcha in the Comment module attachment or
entity constraint approach. List only genuine issues."

**AI Response Summary:**
Flagged one real bug: the edit-lock rule ("all fields except status locked
on resolved/closed/cancelled") contradicts the transition table, since
closed/cancelled allow no further transitions at all — status should be
locked too on those two, only resolved should keep it writable. Flagged
TicketAccessControlHandler as underspecified: no rule for who can edit
non-status fields, no stated behavior for unassigned-ticket transitions,
no create permission, no concrete permission machine names, delete scope
undefined. Flagged four Drupal-specific gotchas: field-level edit-lock
needs checkFieldAccess() overrides (not a single checkAccess('update')),
Comment module setup needs more than addDefaultCommentField() (comment
type, per-bundle config, permissions), Views need accessCheck(TRUE)
explicit or they can leak inaccessible tickets, and — most importantly —
the transition constraint only guards status pairs, not tampering with
other fields on a terminal-status ticket via Drush/API (needs a separate
check against $entity->original for non-status fields). Also reported
three "contradictions" (comments-on-terminal-tickets still open, My
Understanding still saying "constraint or presave", informal Edit-Locked/
Transition-Final language undefined) that appear to be checking a stale
copy of requirements-analysis.md — those were already resolved in an
earlier round; flagged for Somesh to confirm his Cursor workspace file is
current before treating those three as real findings.

**Accepted:**
_Pending Somesh's review — status-lock bug, access-handler gaps, and the
four Drupal gotchas are being brought back for a decision on which to fold
into design-notes.md/data-model.md._

**Changed:**
_TBD_

**Rejected:**
_TBD_

**Why:**
_TBD — will log reasoning once Somesh confirms which findings to act on._

## Prompt 2 — Follow-up design check (Plan Mode, re-run after sync)

**AI Response Summary:**
More precise second pass (prior stale-file confusion resolved). Confirmed
one real gap carried over: terminal field-edit lock has no concrete
mechanism specified (entity-wide update access can't express "status
editable, other fields locked" on Resolved) — needs a field-diff
constraint or checkFieldAccess() override. Flagged access-handler wording
as ambiguous (view/update/delete bundled with assignee/admin-transition
language reads as gating non-status edits too, which isn't the intent).
Flagged unassigned-ticket transition consequence as undocumented (falls
through to admin-only, correct but unstated). Flagged permission machine
names and delete-handling as not concrete enough to implement from.
Caught a real Drupal nuance: $entity->original isn't guaranteed populated
at validation time (only guaranteed once storage sets it pre-save) —
validator needs a loadUnchanged() fallback for the validation-phase case,
not just the create-time case already handled. Caught that
addDefaultCommentField() is a test trait, not a production install API —
real install-time comment_type + field config needed instead. Suggested
(more cautiously) that the custom comment access handler might be
unnecessary given Comment module's default gating already covers the
Core-scope need.

**Accepted:** _Pending — brought back to Somesh for confirmation on which
of the 6 fixes + 1 simplification (drop custom comment access handler) to
fold into design-notes.md/data-model.md._
