# API Contract

**Decision: route/form contract, not REST/JSON:API.** Core is UI/form-
driven per design-notes.md (Drupal Form API + Views) — no REST or
JSON:API resources are exposed for Core. This section documents routes,
form IDs, and expected inputs/outputs/access instead. (Exposing Ticket/
Comment via JSON:API is a named Stretch candidate — see
implementation-plan.md.)

---

## Route: Create Ticket

**Route name:** `entity.ticket.add_form`
**Path:** `/ticket/add`
**Method:** GET (form) / POST (submit)
**Permission required:** `create tickets`

### Form Input
| Field | Required | Notes |
|---|---|---|
| title | Yes | string, max 255 |
| description | Yes | text |
| priority | Yes | enum: low / medium / high |
| assignedTo | No | valid, non-blocked user reference |

`status` is **not** a form field on create — always forced to `open`
server-side (see data-model.md).

### On Success
Ticket saved with `status = open`, `uid` (reporter) set to current user.
Redirect to `entity.ticket.canonical` (ticket detail) with a confirmation
message.

### On Failure
Form re-rendered with field-level validation errors (missing title/
description/priority).

---

## Route: List Tickets

**Route name:** `entity.ticket.collection` (Views-backed page)
**Path:** `/tickets`
**Method:** GET
**Permission required:** `view tickets`

### Input (query parameters via exposed Views filters)
| Param | Notes |
|---|---|
| `keyword` | matches title OR description, CONTAINS |
| `status` | exact match against one status value, optional |

### Output
Paginated list: title, status, priority, assignee, updated date, and an
Edit link column (when the user has entity `update` access). Access-
checked per row via `TicketAccessControlHandler` (Views uses entity
access, not a raw query) — see design-notes.md. Empty state rendered
when no rows match.

---

## Route: Ticket Detail

**Route name:** `entity.ticket.canonical`
**Path:** `/ticket/{ticket}`
**Method:** GET
**Permission required:** `view tickets`

### Output
All ticket fields, current status, comment thread (Comment module
default field formatter), and — conditionally rendered based on access —
an Edit link (local task) and a comment form. Status changes are made on
the edit form (`entity.ticket.edit_form`), not inline on the detail view.

---

## Route: Update Ticket (fields + status, single form)

**Route name:** `entity.ticket.edit_form`
**Path:** `/ticket/{ticket}/edit`
**Method:** GET (form) / POST (submit)
**Permission required:** varies per field — see Field Visibility below.
No separate route for status changes; one form handles both, with
Drupal's `checkFieldAccess()` deciding what each user can see/edit.
**As of the final code-review pass, `edit ticket fields` alone no longer
grants access when the ticket's status is transition-final
(`closed`/`cancelled`)** — on those statuses, the route denies access
entirely unless the user can also transition status (which is never true
for closed/cancelled), matching the original intent that closed tickets
should not be editable by general staff at all.

### Form Input
| Field | Required | Shown/editable when |
|---|---|---|
| title | Yes | user holds `edit ticket fields` AND ticket not edit-locked (status `open`/`in_progress`) |
| description | Yes | same as title |
| priority | Yes | same as title |
| assignedTo | No | same as title |
| status | — | user is the ticket's assignee OR holds `administer tickets`, AND ticket status is not `closed`/`cancelled` (see data-model.md). Rendered as a `<select>` filtered to the current status plus its legal next-states only (not all 5 values) — UX convenience; backend still re-validates regardless |

A user who can edit fields but isn't the assignee/admin sees the form
without a status control, **unless the ticket is resolved**, in which
case they see the form with title/description/priority/assignee
disabled and no status control (since they can't transition it either).
A user who is only the assignee (not general staff) sees only the status
control. Both together get the full form. This is one route, one form
class — visibility is entirely access-driven, not two separate code
paths.

### Validation (enforced server-side regardless of what the form renders)
- Required-field checks (title, description, priority) — as before
- Assignee, if set, must reference an existing **and non-blocked** user
  (`TicketAssignee` constraint — added during the final code-review pass;
  the original implementation only checked existence)
- If `status` changed: checked by the `TicketStatusTransition` entity
  constraint against the authoritative table in data-model.md (open →
  in_progress → resolved → closed, plus the two cancel paths; same-status
  always allowed; everything else rejected)
- If any of title/description/priority/assignedTo changed while the
  ticket is edit-locked: rejected by the field-diff constraint, even if
  submitted via direct API/Drush and not through this form

### On Success
Ticket saved, confirmation message ("Ticket %label has been updated."),
redirect to `entity.ticket.canonical`.

### On Failure
- Field-level validation errors (missing required fields, blocked
  assignee), or
- Specific rejection message naming the illegal from→to pair if an
  invalid status change was attempted (e.g. "Cannot move a ticket from
  Resolved to In Progress") — not a generic error

---

## Route: Add Comment

**Route:** Comment module's default routes for a non-node entity
(`comment/reply/{entity_type}/{entity}/{field_name}` pattern)
**Method:** GET (form) / POST (submit)
**Permission required:** `post comments` + `view tickets` (Comment
module's default gating on parent view access — see data-model.md; no
custom comment access handler)

### Form Input
| Field | Required |
|---|---|
| comment_body | Yes |

### Notes
Allowed regardless of ticket status — comments are **not** subject to
the field-edit lock (decided in requirements-analysis.md: commenting
isn't a field edit).

### On Success
Comment saved, thread updated, redirect back to ticket detail.

---

## Not Implemented (Core scope)

- Delete: no route, form, or permission exists for any role (see
  requirements-analysis.md — explicitly out of scope)
- REST/JSON:API resources (Stretch candidate only)
