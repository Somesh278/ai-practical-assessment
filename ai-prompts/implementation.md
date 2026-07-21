# AI Prompts — Implementation

_Log prompts for this activity as you go. Each entry should capture the
prompt, AI's response summary, what you accepted/changed/rejected, and why._

## Prompt 1
**Prompt:** 
**AI Response Summary:** 
**Accepted:** 
**Changed:** 
**Rejected:** 
**Why:** 

## Prompt 1 — Module scaffold (Agent Mode)

**Prompt:**
"@data-model.md @design-notes.md @api-contract.md — Scaffold the
ticket_management custom module: .info.yml, .module file, the Ticket
content entity class with base field definitions matching data-model.md
exactly (including the field-edit-lock and status-lock rules), and
routing.yml for entity.ticket.add_form, entity.ticket.canonical,
entity.ticket.edit_form, and entity.ticket.collection. Don't implement the
constraints, access handler, or Comment module wiring yet — just the
entity skeleton and routes."

**AI Response Summary:**
Generated ticket_management.info.yml, .module stub, permissions.yml
(derived from data-model.md's permission table), routing.yml (4 routes:
add_form, canonical, edit_form, collection), src/Entity/Ticket.php +
TicketInterface.php (entity + base fields + lock-rule constants/helpers),
src/Form/TicketForm.php (hides status field on create, forces reporter +
open), and src/TicketListBuilder.php (needed for the collection route).
Explicitly left TicketAccessControlHandler as core's placeholder handler,
and did not implement the TicketStatusTransition/edit-lock constraints or
Comment module config — correctly respected the scope boundary in the
prompt.

**Accepted:** _Pending — reviewing Ticket.php field definitions and
TicketForm.php against data-model.md before accepting as-is._

**Changed:** _N/A this round — reviewing before deciding._

**Rejected:** _None yet._

**Why:** _TBD after review._

## Prompt 2 — Access handler + constraints (Agent Mode)

**Prompt:**
"@data-model.md @design-notes.md — Implement TicketAccessControlHandler
and two entity constraints for Ticket, matching data-model.md exactly:
(1) access handler with view/update/delete/create rules and
checkFieldAccess() override for status vs other fields vs uid; (2)
TicketStatusTransition constraint validating against
Ticket::ALLOWED_TRANSITIONS with original/loadUnchanged() fallback; (3)
edit-lock constraint diffing non-status fields against original when
edit-locked. Don't touch TicketForm.php or routing yet."

**AI Response Summary:**
Implemented TicketAccessControlHandler (view: "view tickets"; update:
edit-fields-and-not-locked OR assignee/admin-and-not-final; delete:
always denied; create: "create tickets"; checkFieldAccess() split by
field — status gated on assignee/admin + not closed/cancelled, other
fields gated on edit-fields + not edit-locked, uid always denied),
TicketStatusTransitionConstraint + validator (skips new entities, reads
original with loadUnchanged() fallback, same-status always valid,
validates against ALLOWED_TRANSITIONS, specific rejection message), and
TicketEditLockConstraint + validator (diffs non-status fields against
original when edit-locked, allows status-only changes). Registered both
constraints and the access handler on the Ticket entity annotation.
Verified via cache rebuild + smoke tests: invalid transition rejected,
title edit on resolved rejected, resolved→closed allowed, delete denied.

**Accepted:** _Pending — reviewing checkFieldAccess() timing behavior on
initial form load (not just submission) before fully accepting; smoke
tests cover rejection paths but not confirmed for form-render behavior on
a locked ticket's first page load._

**Changed:** _TBD._

**Rejected:** _None yet._

**Why:** _TBD after form-render check._

## Prompt 3 — Comment module integration (Agent Mode)

**Prompt:**
"Wire up the Comment module for tickets per data-model.md: create a
comment_type 'ticket_comment' targeting the ticket entity type, add a
comment field to the ticket bundle in field mode 'open', provisioned via
install config (not addDefaultCommentField()). No custom comment access
handler — rely on Comment module's default gating."

**AI Response Summary:**
Added drupal:comment dependency to .info.yml. Created install config:
comment.type.ticket_comment.yml, field.storage.ticket.comment.yml,
field.field.ticket.ticket.comment.yml (status: 2/open),
field.field.comment.ticket_comment.comment_body.yml, plus view/form
display config for both the ticket (comment formatter) and the comment
bundle itself (subject hidden, comment_body only). Added an update hook
(ticket_management_update_10001()) to import the config on already-
enabled sites, not just fresh installs. No custom access handler, as
requested. Kernel tests confirmed still passing (36/36) after the change.

**Accepted:** Full implementation as generated. Manually verified: comment
posts successfully on an open ticket; comment posts successfully on a
closed/cancelled ticket (confirms comments are correctly exempt from the
edit-lock constraint, per the requirements-analysis.md decision); subject
field genuinely hidden in the rendered UI, not just in display config.

**Changed:** None needed.

**Rejected:** None.

**Why:** Config-based install approach + manual verification confirmed
correct behavior on the first pass — no bugs found this round, unlike the
prior three implementation rounds. Comment access relying on Comment
module defaults (rather than a custom handler, per the earlier design
review's suggestion to simplify) held up correctly under real testing.

## Prompt 4 — Ticket list View, replacing list builder (Agent Mode)

**Prompt:**
"@api-contract.md @data-model.md — Create a Views config for the /tickets
route: title/status/priority/assignee/updated columns, exposed keyword
filter (title OR description, CONTAINS), exposed status filter (optional,
exact match), default sort updated-desc, empty state message, must use
entity access checking so it respects TicketAccessControlHandler.
TicketListBuilder.php currently serves this route — replace it entirely
and update routing.yml to point at the View."

**AI Response Summary:**
Repointed entity.ticket.collection in routing.yml to Views'
ViewPageController (view_id: ticket_list, display_id: page_1). Removed
the list_builder handler from the Ticket entity annotation and deleted
TicketListBuilder.php entirely — no orphaned code left referencing it.
Removed a redundant route_name from the view's page display so routing
is owned by routing.yml, not duplicated in two places. Verified
entity.ticket.collection resolves correctly and all 36 Kernel tests
still pass after the routing change.

**Accepted:** Full implementation, after reviewing the actual YAML.
Access: `disable_sql_rewrite: false` triggers Drupal's standard
EntityViewsData query-tag mechanism (`ticket_access`), handled by
`ticket_management_query_ticket_access_alter()` — legitimate, not a
workaround; matches how Views entity-access integration works generically
for custom entities. Keyword filter correctly OR's title and description
via a shared exposed identifier in filter group 1; status filter is
optional (`value: {}`) in group 2, AND'd with the keyword group. Empty
state configured via `area_text_custom`, `empty_table: false` ensures it
actually renders instead of a blank table.

**Changed:** None — accepted as generated.

**Rejected:** None.

**Why:** Verified rather than assumed correct, given accessCheck was
flagged as the highest-risk item in the earlier design review. One
maintenance note (not a fix): the access-tag hook manually reimplements
the "view tickets = see all" rule rather than calling
TicketAccessControlHandler::checkAccess() per row. Correct today because
view access is uniform (no per-ticket visibility logic in Core scope),
but if that ever changes, the hook and the access handler would need to
be updated together by hand — worth a code comment flagging the
dependency, not urgent enough to block on.

## Prompt 5 — Seed data (Agent Mode)

**Prompt:**
"Write a Drush command that seeds 3-4 users with the ticket_staff role
and 6-8 sample tickets across different statuses and priorities, with at
least one unassigned ticket to exercise the unassigned-transition rule."

**AI Response Summary:**
Created database/seed-data/tickets.seed.php (4 staff users, 8 tickets
spanning open/in_progress/resolved/closed, including one unassigned open
ticket), TicketSeedService.php (loads seed data, creates users/tickets,
walks valid status transitions rather than force-setting status directly
for the normal path), a Drush command (ticket:seed / tms:seed), wired via
ticket_management.services.yml, plus user.role.ticket_staff.yml install
config (superseding/aligning with the earlier standalone role fix) and
an update hook for existing sites. Idempotent by default (skips existing
users/tickets); --force resets tickets to open via a direct DB write
(since closed → open isn't a valid transition through the normal entity
API) before reapplying seed values. Documented in
database/setup-notes.md with run commands and seed user credentials.

**Accepted:**
The full implementation — the unassigned seed ticket was what actually
surfaced Issues 5 and 6 in debugging-notes.md, so the seed data did its
job as a real test fixture, not just filler data.

**Changed:**
None yet — flagged one thing to confirm with Cursor: that the --force
direct-DB-write reset path is isolated to that one reset operation and
documented as an intentional constraint bypass (not a pattern reused
elsewhere), since it's exactly the kind of write the TicketEditLock/
TicketStatusTransition constraints exist to prevent.

**Rejected:**
None.

**Why:**
Seed data doubling as the fixture that exposed two real access-control
bugs (Issues 5 and 6) is a stronger outcome than seed data existing just
to make the UI look populated — it was exercised against the actual
access rules, not just displayed.
