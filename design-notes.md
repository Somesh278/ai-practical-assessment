# Design Notes

## Architecture Overview (frontend, backend, database)

Single Drupal instance serves as the full stack: Drupal's Form API / Twig
templates as the frontend, Entity API + a custom entity constraint
validator as the backend, MySQL/MariaDB as the database — see Database
Design below.

## Frontend Design

- Entity add/edit forms via Drupal's Form API (auto-generated from field
  definitions), with field-level edit access enforced via
  `checkFieldAccess()` (see data-model.md's field-level edit lock table —
  this is per-field, not a single form-wide `#access` toggle, since
  `resolved` keeps `status` editable while locking everything else)
- Status change control on the edit form limited to a `<select>` populated
  only with legal next-states for the ticket's current status (UX
  convenience only — backend still re-validates regardless of what's
  submitted)
- Views for ticket list (default sort: newest first), keyword search
  (title + description, exposed filter, CONTAINS operator), and status
  filter (exposed filter)
- Ticket detail via entity canonical route, including inline comment
  thread (Comment module's default field formatter)

## Backend Design

- Custom content entity: `Ticket` (see data-model.md for fields)
- Comment module attached to the `ticket` bundle via install-time config
  (comment type + field, `open` mode) — no custom comment access handler;
  Comment module's default gating on parent `view` access is sufficient
  (see data-model.md)
- `TicketStatusTransition` **entity constraint** (not a presave hook) —
  chosen specifically because a constraint runs through Drupal's Typed
  Data validation API and applies uniformly regardless of entry point
  (form save, REST/JSON:API write, Drush script), whereas a presave-only
  hook can be silently bypassed by any code path that skips it
  - Reads previous status via `$entity->original`. **Not guaranteed
    populated at validation time** — only `EntityStorageBase` guarantees
    it's set, immediately before save. The constraint falls back to an
    explicit `loadUnchanged($entity->id())` when `original` is empty on
    an update (covers form-validation-phase calls), and treats a missing
    `original` on a genuinely new entity as the create-time case
  - Same-status saves (`original->status === new->status`) short-circuit
    as valid — not evaluated against the transition table at all
  - Create-time: constraint forces/validates `status === 'open'`
    regardless of submitted value
  - A second, separate constraint (or the same constraint, second check)
    diffs `$entity->original` against incoming values for title/
    description/priority/assignee when current status is edit-locked, and
    rejects the save if any changed — this is what actually stops a
    Drush/API write from editing a closed ticket's title, since the
    transition constraint alone only checks the status field
- `TicketAccessControlHandler` — two separate rules, not one bundled rule
  (see data-model.md's permission table): `edit ticket fields` (role-wide,
  any staff, subject to the edit-lock) governs non-status field edits;
  status transitions are gated by "is the ticket's assignee" or holds
  `administer tickets` — these are different checks and must not share one
  `checkAccess('update')` path. Views must use this handler (not a raw DB
  query) so the ticket list respects the same access rules
- User entity: Drupal core, no custom UI, one additional role (`ticket_staff`)

## Database Design

**Engine:** MySQL/MariaDB. Entity storage is DB-agnostic via Drupal's SQL
content entity storage, so no query code should be DB-specific. Note:
Drupal's PHPUnit Kernel tests default to SQLite regardless of the
production DB choice (test-runner behavior, not a project decision) — see
test-strategy.md.

Ticket entity persisted via Drupal's default SQL content entity storage
(auto-generated schema from `baseFieldDefinitions()`). See data-model.md
for the field schema; no manual schema/migration files needed beyond the
entity class itself and an `update_N` hook if the schema changes later.

## Validation Strategy

- Required fields (`title`, `description`, `priority`) enforced via
  `setRequired(TRUE)` on base field definitions
- Status transitions enforced via `TicketStatusTransition` constraint,
  checked on every save (create and update) — rejects with a specific,
  human-readable validation message identifying the illegal from→to pair
- Edit-lock enforced via a save-time field-diff check (not just form
  `#access`) — rejects changes to non-status fields when status is
  `resolved`/`closed`/`cancelled`, and rejects changes to `status` itself
  when status is `closed`/`cancelled` (no further transitions possible)
- Assignee reference validated as an existing, non-blocked user
  (`status = 1`) via a `ValidReferenceConstraint`-style check
- Delete is not implemented — no delete route, form, or permission exists
  for Core, per requirements-analysis.md

## Error Handling Strategy

- Form-level validation errors surfaced via Drupal's standard form error
  messaging, tied to the specific field/status control that failed
- Transition rejection returns a specific message (e.g. "Cannot move a
  ticket from Resolved to In Progress") rather than a generic exception
  trace or silent no-op
- Successful transitions/updates show a confirmation message (status
  message + redirect to ticket detail)
- Empty search/filter results render an explicit "No tickets match" state
  in the View, not a blank table

## Known Trade-offs (accepted, not built)

- **Concurrent status updates:** last-write-wins, no locking implemented
  (see requirements-analysis.md Assumptions) — documented risk, not a gap
- **Search:** Views CONTAINS matching only, no ranked full-text
  (Search API) — sufficient for Core scope

## Testing Strategy Link

See test-strategy.md

