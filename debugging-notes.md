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
