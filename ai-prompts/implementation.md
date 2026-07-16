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
