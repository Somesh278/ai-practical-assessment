# Code Review Notes

## Round 1 — Entity/form field review (after initial scaffold)

### AI-Assisted Review Summary
Reviewed `src/Entity/Ticket.php` and `src/Form/TicketForm.php` against
data-model.md field-by-field after the initial scaffold. Everything
matched spec: title/description/priority field types and required flags,
status allowed-values and default, assignee as optional entity_reference,
uid (reporter) forced to current user only on create.

### My Review Observations
Two things worth noting, both mine, not flagged by Cursor's own generation:

1. **Positive finding:** `preCreate()` forces `$values['status'] = 'open'`
   at the storage layer, not just in the form. That's stronger than what
   design-notes.md asked for at this stage — it closes the create-time
   status bypass for *any* creation path (Drush, REST/JSON:API), not only
   the form, ahead of the formal entity constraint still to come.
2. **Real inconsistency found:** `uid` field had both `setReadOnly(TRUE)`
   and `setDisplayConfigurable('form', TRUE)` — contradictory signals
   (never editable vs. sitebuilder-configurable as an editable widget).
   Not an active bug (nothing currently exposes the field), but a latent
   trap if Manage Form Display is touched later.

### Changes Made After Review
Told Cursor about the `uid` field-config contradiction. It agreed and
changed `setDisplayConfigurable('form', FALSE)` on the `uid` field,
leaving `view` display configurable. Confirmed reporter assignment still
happens only in `TicketForm::save()` on create.

### Suggestions Rejected
None this round.

---

## Round 2 — Final full-codebase review (after all functional work complete)

### AI-Assisted Review Summary
Asked Cursor (Plan Mode) to specifically hunt for two known bug classes
rather than do a generic review: (1) the live-entity-vs-stored-entity
pattern that caused Issue 4, checked across every access/validation code
path; (2) any place duplicating TicketAccessControlHandler's logic
instead of delegating to it, similar to the Views access-tag hook flagged
earlier as an acceptable maintenance mirror. Also ran PHPCS
(Drupal, DrupalPractice standards) and cross-checked implementation
against design-notes.md/data-model.md/api-contract.md now that several
rounds of fixes had landed.

Findings:
1. **No remaining Issue-5-class bugs** — TicketAccessControlHandler and
   both constraint validators all correctly use stored/original state.
   One minor UX-only gap noted: `TicketForm::disableEditLockedFields()`
   used live status instead of stored, lower risk than Issue 4 since the
   save-time constraint still blocks any actual invalid save regardless.
2. **Genuine access-control bug (High):** `checkAccess('update')` granted
   update access via `edit ticket fields` permission alone, without
   checking whether the ticket's status was transition-final — meaning
   staff could get update access (and see the Edit tab/list link) on
   closed/cancelled tickets, contradicting api-contract.md/ui-flow.md's
   "deny access entirely" rule for those statuses. Verified live against
   a seed ticket, not just inferred from reading the code.
3. **Status `<select>` not filtered (Medium)** — showed all 5 status
   values regardless of current status; backend constraint already
   rejected illegal choices, so this was UX-only, not a security gap.
4. **Three low-priority items:** an orphaned `transition ticket status`
   permission defined but never actually checked anywhere (transition
   access is hardcoded to assignee-ID + `administer tickets` instead);
   missing blocked-assignee validation (spec called for rejecting
   blocked users, not just nonexistent ones — implementation only
   checked existence); 2 trivial PHPCS style violations.
5. **Doc drift, not code bugs:** debugging-notes.md's Issue 2 was still
   marked "Pending" despite the fix having landed; api-contract.md had a
   few small inaccuracies (canonical-page description, missing the Edit
   column added for Issue 5, an overly specific success-message example).

### My Review Observations
The review's framing — hunting for a *specific known bug pattern* rather
than asking for a generic pass — surfaced a real, previously-missed
access-control bug (item 2) that a generic "does this look right" review
likely would have missed, since the code reads correctly in isolation;
the bug only shows up when checked against the *combination* of
permission-holding and status. Worth noting as a technique, not just an
outcome: targeted "find bugs like X" prompts outperformed open-ended
review prompts throughout this project (see ai-prompts/design.md and
ai-prompts/code-review.md for the same pattern occurring earlier).

### Changes Made After Review
All six findings addressed in one Cursor pass:
- `checkAccess('update')` no longer grants access via `edit ticket
  fields` alone on transition-final statuses (closed/cancelled); staff
  can still open resolved tickets to see disabled fields. Two new Kernel
  tests added specifically for this (closed/cancelled denial, resolved
  allowed).
- Status `<select>` now filtered to current status + legal next states
  via `getSelectableStatuses()`; `disableEditLockedFields()` switched to
  stored status. New test: `testSelectableStatuses` (5 cases).
- PHPCS errors fixed (use-statement, docblock grouping) — now 0
  errors/warnings.
- Orphaned `transition ticket status` permission removed from
  `.permissions.yml` and `data-model.md`, rather than wired up — the
  hardcoded assignee-ID check was already correct and simpler; adding a
  second permission-based path would have created two ways to guard the
  same rule.
- New `TicketAssignee` constraint rejects blocked (`status = 0`)
  assignees, not just nonexistent ones. New test:
  `testBlockedAssigneeRejected`.
- `api-contract.md` and `debugging-notes.md` (Issue 2) corrected to
  match actual behavior.

Final verification: 46 Kernel tests, 426 assertions, all passing. PHPCS
clean.

### Suggestions Rejected
None — all six findings were valid and were fixed as recommended. The
review's own priority ranking (High/Medium/Low) was followed rather than
fixing everything with equal urgency, which was itself a judgment call:
the closed-ticket access bug was fixed immediately as a real security
gap, while the doc-drift items were treated as housekeeping rather than
urgent.
