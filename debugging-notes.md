# Debugging Notes

_Log real issues as they happen — don't backfill a too-clean history. The
guide specifically looks for genuine debugging evidence._

## Issue 1

### Problem
_TBD_

### How I Investigated
_TBD_

### How AI Helped
_TBD_

### What I Validated
_TBD — what you checked yourself rather than trusting AI's fix blindly_

### Final Fix
_TBD_

<!-- Repeat ## Issue N for each real issue hit -->

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

<!-- Repeat ## Issue N for each real issue hit -->

## Issue 2 — Wrong route-inspection command suggested (Claude, not Cursor)

### Problem
Ran `drush route:debug | grep entity.ticket` to verify the scaffolded
routes registered correctly. Drush returned "There are no commands
defined in the route namespace" — command didn't exist.

### How I Investigated
Confirmed `drush status` / version first to rule out a bootstrap problem
(Drush 13, correctly installed). Then searched for the actual Drush 13
route command naming.

### How AI Helped
Claude (outside Cursor, in the planning/chat assistant) had originally
suggested `drush route:debug` — this was **Claude's mistake**, not
Cursor's: it conflated Drush's route command with Drupal Console's
`debug:router` naming convention. A web search surfaced the correct
command (`drush route`, aka `core:route`, available since Drush 10.5).

### What I Validated
Confirmed via Drush's own documentation that `drush route` is the correct
Drush 13 syntax; `route:debug` isn't and never was a real Drush command.

### Final Fix
Use `drush route | grep ticket` (or `drush route --name=...` /
`--path=...`) instead.

<!-- Repeat ## Issue N for each real issue hit -->

## Issue 3 — priority field not locked on resolved tickets

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
_Pending — flagged to Cursor, not yet fixed as of this log entry._

<!-- Repeat ## Issue N for each real issue hit -->

## Issue 4 — no role had "view tickets" / "administer tickets" granted

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
