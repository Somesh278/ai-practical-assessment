# AI Prompts — Planning

## Prompt 1 — Requirements review (Plan Mode)

**Prompt:**
"@requirements-analysis.md — Review this requirements analysis for a
Drupal-based Support Ticket Management System. I'm the one who wrote it —
I want you to critique it, not rewrite it. Specifically: (1) gaps in
functional requirements, (2) state machine edge cases I haven't accounted
for, (3) Drupal-specific gotchas in my assumptions, (4) whether my
clarification questions are the right ones. Don't rewrite the document —
just list what you'd push back on or add, with reasoning."

**AI Response Summary:**
Cursor (Plan Mode) confirmed the overall approach (backend-owned state
machine, Drupal entity mapping) was sound, then flagged 4 categories of
gaps: (1) functional requirements gaps — status not specified as Open on
create, terminal-state edit rules undefined, permissions/visibility never
stated as a functional requirement, search scope vague, priority enum
undefined, delete unmentioned; (2) state-machine details — same-status
saves must not be rejected as invalid transitions, create-time status must
be constrained, validation must compare against `$entity->original` not
form state, entity constraint preferred over presave-only, concurrent
update race condition flagged but no mitigation stance forced; (3)
Drupal-specific gotchas — Comment module needs a comment-type-per-host-
entity setup and separate access handling from ticket access, Views
CONTAINS filtering isn't full-text search, entity access control handler
needed for the View to respect per-user visibility; (4) clarification
questions — assignee-role question was too narrow, missing bigger
questions about ticket visibility and who can transition status.

**Accepted:**
- Constrain create-time status to Open; document as a functional
  requirement, not just an assumption
- Terminal-state fields become read-only (documented as a product decision)
- Permissions/visibility promoted to a functional requirement, answered
  in Assumptions (all staff view; assignee/admin transition)
- Search scope narrowed and documented (title + description only)
- Priority enum defined (Low/Medium/High)
- Same-status save must not be treated as an invalid transition
- Transition validation against `$entity->original`, not form state
- Entity constraint validator over presave-only hook
- Delete explicitly marked out of scope
- Higher-priority clarification questions (visibility, transition
  permissions) folded in and answered as documented assumptions

**Changed:**
- Rewrote several requirements-analysis.md sections myself after reading
  the critique, rather than accepting AI-suggested wording verbatim — kept
  "in my own words" for the parts that matter (My Understanding section
  untouched; Functional Requirements/Assumptions edited in my own phrasing
  based on AI's findings, not copy-pasted from its output)

**Rejected:**
- Building explicit concurrency control (`\Drupal::lock()` or check-and-set)
  for the race condition on simultaneous status updates

**Why:**
The critique was correct that last-write-wins is a real gap, but
implementing locking is disproportionate scope for a Core-level assessment
deliverable — it's a legitimate Stretch-tier concern, not a Core
requirement. Instead of building it, I documented it as an accepted risk
in requirements-analysis.md Assumptions and flagged it for
test-strategy.md's "Tests Not Covered" section. This was a deliberate
push-back on the AI's implied fix, not an oversight — I'm choosing to
document the trade-off rather than over-engineer Core.

## Prompt 2 — Targeted verification pass (Plan Mode)

**Prompt:**
"I've revised this based on your last review. Don't re-review the whole
document. Just verify: (1) does the state machine section now correctly
rule out same-status saves as invalid and constrain create-time status to
Open, (2) is the terminal-state edit rule unambiguous enough to implement
directly without guessing, (3) is there anything in Assumptions that
contradicts something else in the document."

**AI Response Summary:**
Confirmed (1) and (2) checked out cleanly — create-time status constraint
and same-status-save handling were both correctly specified, and the
terminal-state edit rule was implementable as written, with one naming
caveat: "terminal" was being used for two different rule sets (edit-lock
vs. transition-final states) that overlap on 2 of 3 values but aren't
identical, risking a single `isTerminal()` implementation bug. For (3), it
found a real contradiction: Functional Requirements allowed comments on
Closed/Cancelled tickets, but Clarifications and Edge Cases still listed
this as an undecided open question — direct conflict, not just missing
detail. Also flagged minor drift: "My Understanding" still said "entity
constraint or presave hook" while Assumptions had already mandated
constraint-only.

**Accepted:**
- Added an explicit naming-caveat note distinguishing edit-locked vs.
  transition-final terminal states, with an instruction not to implement
  a single shared `isTerminal()` check for both
- Resolved the comments contradiction: decided comments are allowed on
  Closed/Cancelled tickets (kept the Functional Requirements answer as
  the deliberate one), removed it from open Clarifications, and updated
  the Edge Cases bullet to state the decision instead of leaving it open
- Fixed "My Understanding" wording to say "entity constraint validator"
  only, matching Assumptions, removing the presave-hook alternative

**Changed:**
- Made the edit to the Edge Cases section by hand and had to fix a
  self-inflicted structural error (accidentally deleted the "## Edge
  Cases" header while editing) — caught and corrected by re-viewing the
  file before moving on, rather than assuming the edit landed cleanly

**Rejected:**
- None — this pass's findings were both valid and cheap to fix, no
  pushback needed this round

**Why:**
Both catches were real: the comments contradiction was an actual
inconsistency a reviewer or implementer would trip on, and the
`isTerminal()` naming trap is a plausible real implementation bug (one
shared boolean check silently blocking a valid Resolved→Closed transition
because it also gates edits). Worth noting the second-pass prompt (narrow,
"verify these specific things") produced a more actionable response than
the first broad review — no filler, no restating the document, just a
clear pass/fail per item plus one thing it wasn't asked about but caught
anyway (My Understanding drift).
