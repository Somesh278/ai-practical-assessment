# Data Model

## Ticket (custom content entity)

| Field | Type | Notes |
|---|---|---|
| id | integer | primary key |
| title | string | required |
| description | text_long | required |
| priority | list_string | enum: Low/Medium/High (finalize values) |
| status | list_string | enum: Open/In Progress/Resolved/Closed/Cancelled |
| assignedTo | entity_reference (User) | optional |
| createdBy | entity_reference (User) | required, set on create |
| createdAt | created | Drupal base field |
| updatedAt | changed | Drupal base field |

## Comment (Drupal core Comment module)

Attached to the Ticket bundle. Uses core Comment entity fields
(subject, comment_body, uid, created) — no custom fields required unless
a need emerges.

## User (Drupal core)

Seeded only, no custom fields beyond core (id, name, email, role).

## Status Transition Table

| From | To | Allowed |
|---|---|---|
| Open | In Progress | ✅ |
| In Progress | Resolved | ✅ |
| Resolved | Closed | ✅ |
| Open | Cancelled | ✅ |
| In Progress | Cancelled | ✅ |
| (any other pair) | — | ❌ rejected |
