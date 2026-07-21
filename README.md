# Support Ticket Management System

A support ticket management system implemented as a custom Drupal module,
built for the AI Capability Exercise (Project Option 1 — Backend-heavy).
Internal users create, update, comment on, search, and progress tickets
through an enforced status lifecycle.

## Stack

- Drupal core 10.x
- Custom module: `ticket_management`
- Comment module (core) for ticket comments
- Views for list/search/filter
- Database: MySQL/MariaDB
- Testing: PHPUnit (Drupal Kernel tests)

## Setup

1. Create a MySQL/MariaDB database and user for the site (manually, via
   your local DB tool of choice)
2. Install Drupal 10 core + this module:
   ```
   drush site:install --db-url=mysql://DB_USER:DB_PASS@localhost/DB_NAME
   ```
   (Replace credentials with your local values; not committed anywhere
   in this repo.)
3. Copy/symlink `web/modules/custom/ticket_management/` from this repo
   into your Drupal site's `web/modules/custom/` directory
4. Enable the module:
   ```
   drush en ticket_management -y
   drush cr
   ```
5. Seed sample data (staff users + sample tickets across all statuses,
   including one unassigned ticket):
   ```
   drush ticket:seed
   ```
   See `database/setup-notes.md` for seed user credentials and the
   `--force` reset option.
6. Run the Kernel test suite:
   ```
   ./vendor/bin/phpunit -c phpunit.xml web/modules/custom/ticket_management/tests/src/Kernel/TicketEntityTest.php
   ```
   Expected: 37 tests, all passing (see `test-results.md`).
7. Visit `/tickets` to see the seeded list, or `/ticket/add` to create a
   new ticket. Log in as a seeded staff user (or uid 1 / admin) to
   exercise the full workflow — see `database/setup-notes.md` for
   credentials and role details.

## Repository Structure

See the full lifecycle documentation in the root of this repo:
requirements-analysis.md, acceptance-criteria.md, design-notes.md,
data-model.md, api-contract.md, ui-flow.md, test-strategy.md, and the
`ai-prompts/` and `tool-specific/cursor-workflow/` folders for AI workflow
evidence.

**Important:** the actual module code (entity, forms, access handler,
constraints, Views config, seed script) lives in a separate Drupal 10
site at `web/modules/custom/ticket_management/`, not inside this repo.
This repo (`ai-practical-assessment/`) holds the lifecycle documentation
only. _(If the final submission needs the code physically present in
this repo per the guide's `src/`/`tests/` structure, that copy step still
needs to happen before submitting — see the note in `implementation-plan.md`.)_

## Status State Machine

```
Open → In Progress → Resolved → Closed
Open → Cancelled
In Progress → Cancelled
```

Enforced server-side — see design-notes.md and
tool-specific/cursor-workflow/spec.md.
