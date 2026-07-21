# Debugging Notes

_Log real issues as they happen — don't backfill a too-clean history. The
guide specifically looks for genuine debugging evidence._

## Issue 1 — TicketListBuilder date formatter injection

### Problem
Generated `TicketListBuilder` referenced `$this->dateFormatter`, but
`EntityListBuilder` (the base class) doesn't provide that property —
would have caused a fatal error the first time the /tickets collection
route rendered a row with a date.

### How I Investigated
Caught by Cursor itself on a reconnect/re-check during the same
scaffolding session, before I ran the module locally.

### How AI Helped
Diagnosed that `date.formatter` needed to be explicitly injected via the
service container (dependency injection), not assumed available on the
parent class.

### What I Validated
Need to confirm locally: `drush en ticket_management -y && drush cr` and
load `/tickets`, then check the date column actually renders instead of
throwing.

### Final Fix
Injected `date.formatter` service into `TicketListBuilder` correctly
(constructor injection via `create()`), per Cursor's fix.

## Issue 2 — priority field not locked on resolved tickets

### Problem
Manually testing the edit-lock via the actual UI (not just the smoke
tests Cursor ran): on a `resolved` ticket, title and description were
correctly hidden from the edit form, but `priority` was still visible and
editable. Per EDIT_LOCKED_STATUSES, priority should be locked alongside
title/description/assignee on resolved/closed/cancelled. On `closed`, the
edit form correctly showed "access denied" entirely (status is both
edit-locked and transition-final, so no field is editable by anyone).

### How I Investigated
Manual UI testing by walking a ticket to resolved and closed and checking
the actual rendered edit form, rather than relying only on Cursor's
smoke-test report (title-change-on-resolved-rejected covered title, but
not priority, and not the "is it rendered at all" question separate from
"is a submitted change rejected").

### How AI Helped
Not yet — bug found by manual testing before asking Cursor to fix it.

### What I Validated
Confirmed closed-ticket "access denied" behavior is correct (matches
design: status both edit-locked and transition-final = no update access
for anyone). Confirmed the priority bug is isolated to checkFieldAccess()
somehow treating priority differently from title/description, not a
broader failure of the edit-lock mechanism.

### Final Fix
Fixed as part of the "disabled, not hidden" edit-lock rework (see the
design-notes.md/data-model.md update on visible-but-disabled fields) —
priority was included in the locked-field list alongside title/
description/assignee. Confirmed via the final code-review pass
(code-review-notes.md) that priority is correctly present in
`EDIT_LOCKED_FIELDS` and has a dedicated Kernel test
(`testEditLockRejectsPriorityChangeWhileResolved`) pinning it down. This
log entry was itself out of date (still said "Pending") until the final
review caught the discrepancy and it was corrected here.

## Issue 3 — no role had "view tickets" / "administer tickets" granted

### Problem
After scaffolding, every route returned access denied for all users, and
separately, no user could transition an unassigned ticket. Permissions
(`view tickets`, `create tickets`, `edit ticket fields`,
`administer tickets`) were defined in ticket_management.permissions.yml,
but defining a permission doesn't create or grant it to any role —
nothing was actually holding these permissions.

### How I Investigated
`drush role:list` to check whether ticket_staff existed at all (it
didn't). `drush role:list --filter=perm="administer tickets"` to check
whether any role held the admin permission (none did). Checked whether
seeded/test tickets had an assignee set, since an unassigned ticket can
only be transitioned by administer tickets per data-model.md's access
rule — confirmed this was compounding the problem, not the root cause by
itself.

### How AI Helped
Confirmed the fix approach: install-time config for the ticket_staff role
(same pattern already used for the Comment module config), and granting
"administer tickets" to Drupal's built-in Administrator role rather than
creating a separate custom admin role — simpler, avoids duplicating
Drupal's existing admin concept for a Core-scope tool.

### What I Validated
Ran drush role:list --filter=perm="administer tickets" after the fix to
confirm Administrator now holds it; checked ticket_staff exists with the
three non-admin permissions granted.

### Final Fix
Added user.role.ticket_staff.yml as install config (view tickets, create
tickets, edit ticket fields) and granted administer tickets to the
built-in Administrator role, both via install config with an update hook
for already-enabled sites — same pattern as the earlier Comment module
config, so a fresh clone works without a manual drush role:create step.

**Confirmed working:** ticket_staff holds create/edit/view; Administrator
holds administer tickets. Implementation also covered a case not
explicitly asked for — sites without a standard Administrator role (e.g.
Minimal install profile) get the permission via optional config instead
of failing silently. hook_install() covers fresh installs;
update_10003/10004 cover already-enabled sites.

## Issue 4 — assignee cannot transition their own assigned ticket

### Problem
Testing as a non-admin user, status transitions were blocked on ALL
tickets, including ones assigned to that exact user — only unassigned
tickets should be admin-only per data-model.md; assigned tickets should
be transitionable by their assignee. Comments and viewing worked
correctly, isolating the bug to the status checkFieldAccess() logic
specifically.

### How I Investigated
Manually tested as a non-admin user against both an assigned-to-them
ticket and an unassigned one, to isolate whether the bug was "assignee
check is broken" (would affect assigned tickets too) vs. "no assignee
means no one but admin can transition" (expected, assigned tickets should
work fine). Confirmed it was the former — every ticket blocked regardless
of assignment.

### How AI Helped
Traced the exact bug: `checkFieldAccess()` for the `status` field called
`canTransitionStatus($entity, $account, $stored_status)` — note the
mismatch: `$stored_status` came from `getStoredStatus()` (DB/original/
loadUnchanged() fallback), but the assignee check inside
`canTransitionStatus()` read `$entity->get('assignee')->target_id` from
the **live, in-memory** entity, not the stored one. On the edit form,
Drupal can rebuild with an in-memory entity whose assignee field had been
cleared (e.g. via `filterEmptyItems()` or unsaved widget state) even
though the DB still had an assignee set — so `target_id` came back NULL/0
on the live entity while the DB still correctly had the current user as
assignee. Reporter (`uid`) was never involved — ruled that out. Type
casting `(int)` on both sides was also fine, not a string/int mismatch.

### What I Validated
Confirmed the mismatch was specifically live-entity-state vs.
stored-entity-state, not a wrong-field bug (assignee was always the
correct field being checked) and not a type-comparison bug.

### Final Fix
`canTransitionStatus()` now reads assignee via a new
`getStoredAssigneeId()` helper — same stored-entity pattern already used
by `getStoredStatus()` (original / `loadUnchanged()` fallback) — instead
of the live in-memory entity. Verified: clearing the assignee field in
memory (simulating the form-rebuild scenario) no longer blocks status
access for the actual stored assignee. Added a dedicated Kernel test,
`testAssigneeStatusFieldAccessUsesStoredAssignee`, to
`TicketEntityTest.php` to pin this down and prevent regression.

## Issue 5 — Edit link not visible for assignee-only users

### Problem
After fixing Issue 4 (assignee status-transition access), the assignee
still can't see an Edit button anywhere (ticket detail page, ticket
list) — despite entity update access now correctly working for them
per the Issue 4 fix. Suggests the Edit link's visibility is checked
separately from $entity->access('update'), possibly hardcoded to a
specific permission (e.g. "edit ticket fields") rather than delegating
to the real access handler logic.

### How I Investigated
Confirmed via Issue 4's fix that checkAccess('update')-equivalent logic
should now correctly grant access to the stored assignee. Isolated that
the remaining problem is link *visibility*, not the underlying access
grant — asked Cursor to identify where the Edit link is generated from
(Views operations field vs. local task vs. custom logic) before assuming
a fix.

### How AI Helped
Traced this to a genuine implementation gap, not a wrong access check:
no Edit link was ever generated anywhere. `ticket_management` has no
`*.links.task.yml` (the file that normally produces the local task "Edit"
tab on a content entity's canonical page, following the same pattern as
`node.links.task.yml`), and no contextual links file either. The ticket
list View has no `entity_link_edit` field and no operations column
(operations aren't available since the entity's `list_builder` handler
was removed when the list moved to Views). The edit route itself
(`entity.ticket.edit_form`) already correctly resolves access via
`_entity_access: 'ticket.update'` → `TicketAccessControlHandler::
checkAccess('update')`, which already allows assignees — so once a link
is actually wired up, it would use the correct access logic without
needing further changes.

Also surfaced a real doc inconsistency while investigating: api-contract.md
says the Edit link renders based on general update access, but
ui-flow.md's original wording narrowed that to "field-edit access,"
which would have incorrectly excluded assignee-only users even after
wiring the link correctly. Fixed in ui-flow.md to match api-contract.md's
actual (correct) access rule.

### What I Validated
Confirmed the edit route's access requirement already delegates to the
correct, already-fixed `checkAccess('update')` logic — so this is purely
a missing-UI-element gap, not a second access bug layered on top of
Issue 4.

### Final Fix
Add `ticket_management.links.task.yml` (local task "Edit" tab on the
canonical page, following the standard Drupal content-entity pattern)
and optionally an `entity_link_edit` field on the ticket list View.
Both will correctly resolve access via the existing, already-fixed
`checkAccess('update')` — no additional access logic needed, just the
missing UI wiring.

**Confirmed working:** manually tested as an assignee-only user — Edit
tab appears on `/ticket/{id}` and the edit link appears in the ticket
list's last column, both correctly gated on update access via the
already-fixed logic from Issue 4. 37 Kernel tests still passing after
the change.

## Issue 6 — closed/cancelled tickets incorrectly editable by general staff

### Problem
Found during the final code-review pass (code-review-notes.md, Round 2):
`checkAccess('update')` granted update access via the `edit ticket
fields` permission alone, without checking whether the ticket's status
was transition-final. This meant general staff got update access — and
saw the Edit tab/list link — on closed/cancelled tickets, contradicting
the documented rule that those statuses should deny access entirely.

### How I Investigated
A code-review pass specifically scoped to re-check access logic against
the specs, rather than a generic review — verified live against a seed
ticket (ticket_staff user on a closed ticket → access('update') was
ALLOWED, which shouldn't happen).

### How AI Helped
Traced the exact logic gap: `$can_edit_fields || $can_transition`
returned true whenever the user held `edit ticket fields`, regardless of
status. Confirmed this was the actual bug, not a duplication of access
logic elsewhere.

### What I Validated
Confirmed after the fix that `edit ticket fields` no longer grants
update access once status is `closed`/`cancelled`, while staff can still
open `resolved` tickets to see disabled fields (the correct, narrower
behavior).

### Final Fix
`checkAccess('update')` now denies access via `edit ticket fields` alone
on transition-final statuses. Two new Kernel tests added:
`testTransitionFinalTicketsDenyStaffUpdateAccess` (closed + cancelled)
and `testResolvedTicketAllowsStaffUpdateAccessForDisabledFields`.
