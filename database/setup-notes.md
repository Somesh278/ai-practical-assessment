# Database Setup Notes

**Engine:** TBD (MySQL/MariaDB or SQLite) — finalize and document here.

## Setup Steps

1. _TBD once environment is finalized — e.g. `drush si` / config import steps_
2. Enable custom module: `drush en ticket_management`
3. Run seed data script: _TBD_

## Environment Variables

_List any DB connection env vars here if applicable (e.g. settings.php
overrides via env), with placeholder values only — no real credentials._

## Schema / Migrations

Entity schema is defined via `baseFieldDefinitions()` in the Ticket entity
class — Drupal auto-generates the underlying SQL schema on module install.
See src/ for the entity class; see data-model.md for the field list.
