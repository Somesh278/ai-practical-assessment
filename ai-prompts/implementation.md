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
