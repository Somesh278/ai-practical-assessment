# AI Prompts — Code Review

_Log prompts for this activity as you go. Each entry should capture the
prompt, AI's response summary, what you accepted/changed/rejected, and why._

## Prompt 1
**Prompt:** 
**AI Response Summary:** 
**Accepted:** 
**Changed:** 
**Rejected:** 
**Why:** 

## Prompt 1 — Entity/form field review (manual + Cursor fix)

**Prompt (to Cursor, after I reviewed the generated files myself):**
"The uid field has both setReadOnly(TRUE) and setDisplayConfigurable
('form', TRUE). Those pull in opposite directions — read-only says never
editable, but display-configurable says site builders can add this as an
editable widget via Field UI. Not a bug today, but a latent trap if
anyone touches Manage Form Display later."

**AI Response Summary:**
Agreed it was a real latent trap, changed uid's setDisplayConfigurable
('form', ...) to FALSE, left view display configurable since showing the
reporter on the detail page is fine to tune. Confirmed reporter
assignment logic in TicketForm::save() was unaffected.

**Accepted:**
The fix as given — single-line change, correctly scoped, didn't touch
anything else.

**Changed:**
Nothing further needed.

**Rejected:**
None.

**Why:**
This was a real inconsistency I caught by manually reading the generated
field definitions against data-model.md, not something Cursor flagged on
its own — worth noting as evidence of active review, not passive
acceptance of generated code.
