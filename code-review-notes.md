# Code Review Notes

## AI-Assisted Review Summary

_Summarize what Cursor flagged when asked to review the implementation
(e.g. "review this transition validator for edge cases and Drupal
conventions")._

## My Review Observations

_What you noticed yourself, independent of AI's review pass._

## Changes Made After Review

_Concrete diffs/changes adopted from the review._

## Suggestions Rejected (and why)

_At least one AI suggestion you didn't take, and your reasoning — strong
signal of ownership, don't skip this section._

## AI-Assisted Review Summary

Reviewed `src/Entity/Ticket.php` and `src/Form/TicketForm.php` against
data-model.md field-by-field after the initial scaffold. Everything
matched spec: title/description/priority field types and required flags,
status allowed-values and default, assignee as optional entity_reference,
uid (reporter) forced to current user only on create.

## My Review Observations

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

## Changes Made After Review

Told Cursor about the `uid` field-config contradiction. It agreed and
changed `setDisplayConfigurable('form', FALSE)` on the `uid` field,
leaving `view` display configurable (showing reporter on the detail page
is fine to tune). Confirmed reporter assignment still happens only in
`TicketForm::save()` on create — field definition now matches "set
automatically, never user-editable" with no contradiction.

## Suggestions Rejected (and why)

None this round — the one issue raised was accepted and fixed as
suggested, no pushback needed.
