# Spec

## Entities

### Ticket
- id, title (required), description (required), priority (enum),
  status (enum), assignedTo (ref: User), createdBy (ref: User),
  createdAt, updatedAt

### Comment
- Drupal core Comment module, attached to Ticket bundle

### User
- Drupal core User entity, seeded only

## Status State Machine (authoritative — do not deviate)

Allowed transitions ONLY:
- Open → In Progress
- In Progress → Resolved
- Resolved → Closed
- Open → Cancelled
- In Progress → Cancelled

All other transitions must be rejected server-side.

## Features (Core)
1. Create ticket
2. List tickets
3. View ticket detail
4. Update ticket fields
5. Change status (enforced transitions only)
6. Add comment
7. Keyword search + status filter
8. Persistence across restarts
9. Server-side required-field validation
10. Clear UI error states

## Non-Goals (Core)
- Authentication beyond Drupal default login
- User management UI
- Multiple entity types beyond Ticket/Comment/User
