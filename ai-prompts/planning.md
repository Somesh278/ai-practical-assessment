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
