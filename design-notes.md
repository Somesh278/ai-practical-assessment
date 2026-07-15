# Design Notes

## Architecture Overview (frontend, backend, database)

Single Drupal instance serves as the full stack: Drupal's Form API / Twig
templates as the frontend, Entity API + custom services as the backend,
MySQL/MariaDB (or SQLite — finalize and state which) as the database.

## Frontend Design

- Entity add/edit forms via Drupal's Form API (auto-generated from field
  definitions, customized as needed)
- Views for ticket list, keyword search, and status filter (exposed filters)
- Ticket detail via entity canonical route, including inline comment thread

## Backend Design

- Custom content entity: Ticket (see data-model.md for fields)
- Comment module attached to the Ticket bundle for comments
- `TicketStatusTransitionValidator` service (or entity constraint) owns the
  transition rule table and is invoked on presave — this is the single
  source of truth for valid transitions, not duplicated in the frontend
- User entity: Drupal core, no custom UI

## Database Design

_Database engine: TBD (MySQL/MariaDB or SQLite) — document choice and why._

Ticket entity persisted via Drupal's entity storage (SQL content entity
storage). See data-model.md for the field schema.

## Validation Strategy

- Required fields enforced via base field definitions (`setRequired(TRUE)`)
- Status transitions enforced via custom validation constraint, checked on
  every save — rejects with a typed exception/validation error, not a
  silent no-op

## Error Handling Strategy

- Form-level validation errors surfaced via Drupal's standard form error
  messaging
- Transition rejection returns a specific, human-readable message (not a
  generic exception trace)

## Testing Strategy Link

See test-strategy.md
