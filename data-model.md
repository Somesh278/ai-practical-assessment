# Data Model

## Ticket (custom content entity: `ticket`)

| Field | Machine name | Type | Notes |
|---|---|---|---|
| ID | id | integer | primary key, auto |
| Title | title | string (255) | required |
| Description | description | text_long | required |
| Priority | priority | list_string | required; allowed values: `low`, `medium`, `high` |
| Status | status | list_string | required; allowed values: `open`, `in_progress`, `resolved`, `closed`, `cancelled`; default `open`; **not settable via create form** — forced server-side |
| Assignee | assignee | entity_reference (user) | optional |
| Reporter | uid | entity_reference (user) | required, set automatically to current user on create — not user-editable |
| Created | created | created (base field) | Drupal-provided |
| Changed | changed | changed (base field) | Drupal-provided |

**Field-level edit lock:** the rule is *not* a single entity-wide lock —
it differs by status, and requires field-level enforcement, not just
entity `update` access:

| Status | `status` field editable? | Other fields (title/description/priority/assignee) editable? |
|---|---|---|
| open, in_progress | Yes (per transition table) | Yes |
| resolved | Yes (→ closed only) | No |
| closed, cancelled | **No** (no further transitions) | No |

Mechanism: entity `update` access alone can't express this (it's
all-or-nothing per entity). Implemented as a `checkFieldAccess()`
override on the access control handler — denies write access to the
`status` field when current status is `closed`/`cancelled`, and denies
write access to all non-`status` fields when current status is
`resolved`/`closed`/`cancelled`. Backed by a save-time constraint that
diffs `$entity->original` against the incoming values for the locked
fields, so a direct API/Drush write can't bypass the form-level
`checkFieldAccess()` the way a bare `#access` callback could.

## Comment (Drupal core Comment module)

Comment type `ticket_comment`, targeting the `ticket` entity type,
provisioned at install time via config (a `comment.type.ticket_comment`
config entity + a `comment` field added to the `ticket` bundle, field
mode set to `open`) — **not** `addDefaultCommentField()`, which is a test
helper trait, not a production install API. Fields used: `subject`
(optional, can be hidden), `comment_body`, `uid`, `created` — no custom
fields needed for Core.

**No custom comment access handler.** Comment module's default access
already gates posting on the parent entity's `view` access plus the
`post comments` permission — that's sufficient for Core (staff can
comment if they can view the ticket; comment access doesn't need to know
about the field-edit lock, since commenting isn't a field edit). Simpler
than building a bespoke handler, and avoids two access paths (ticket list
Views vs. canonical route) drifting out of sync.

## User (Drupal core)

Seeded only, no custom fields beyond core (uid, name, mail). One role
added: `ticket_staff`.

**Concrete permissions (Core scope):**

| Permission | Grants | Held by |
|---|---|---|
| `view tickets` | View any ticket, view comments | `ticket_staff` role |
| `create tickets` | Create a new ticket (always starts `open`) | `ticket_staff` role |
| `edit ticket fields` | Edit title/description/priority/assignee when not edit-locked | `ticket_staff` role (any staff, **not** assignee-only — see below) |
| `transition ticket status` | Change status along a valid transition | granted per-ticket to the assignee only (not role-wide) |
| `administer tickets` | Transition status on **any** ticket, including unassigned ones | admin role only |
| (delete) | Not granted to any role — delete is out of scope for Core (see requirements-analysis.md); no delete route/permission implemented | nobody |

**Two separate rules, not one bundled rule:**
- *Editing non-status fields* is gated by `edit ticket fields` (role-wide,
  any staff) + the field-edit-lock table above — **not** restricted to
  the assignee
- *Changing status* is gated by being the ticket's assignee, or holding
  `administer tickets`

**Unassigned tickets:** since status-transition access checks
"current user is the assignee," a ticket with no assignee has no user who
passes that check — so an unassigned ticket can only be transitioned by
someone with `administer tickets`. This is a direct consequence of the
rule above, not a separate mechanism, and is worth surfacing in the UI
(e.g. "assign this ticket to enable staff transitions") rather than
leaving it as a silent dead end.

## Status Transition Table (authoritative)

| From | To | Allowed |
|---|---|---|
| open | in_progress | ✅ |
| in_progress | resolved | ✅ |
| resolved | closed | ✅ |
| open | cancelled | ✅ |
| in_progress | cancelled | ✅ |
| X | X (unchanged) | ✅ (not a transition — always allowed, see requirements-analysis.md) |
| (any other pair) | — | ❌ rejected by entity constraint validator |

## Edit-Locked vs. Transition-Final States (naming disambiguation)

Per requirements-analysis.md's naming caveat — these are two different
rule sets, not one:

| Status | Field-edit locked? | Can still transition? |
|---|---|---|
| open | No | Yes (→ in_progress, → cancelled) |
| in_progress | No | Yes (→ resolved, → cancelled) |
| resolved | **Yes** | Yes (→ closed only) |
| closed | **Yes** | No |
| cancelled | **Yes** | No |

Implementation implication: do not write a single `isTerminal($status)`
helper reused for both the form `#access` callback and the transition
validator — `resolved` needs `edit_locked = true` but
`transition_final = false`.

