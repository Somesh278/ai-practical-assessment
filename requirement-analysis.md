# Requirement Analysis

## Selected Project Option

Option 1 — Backend-Heavy: Support Ticket Management System, implemented as a
custom Drupal module.

## My Understanding (in your own words)

This is a small internal tool for managing support tickets end to end.
Internal users (seeded, no self-registration) can raise a ticket, describe
the problem, set a priority, and assign it to someone. As work happens, the
ticket moves through a defined lifecycle — Open, In Progress, Resolved,
Closed — with the option to cancel from Open or In Progress. Anyone working
the ticket can leave comments on it, and users can search or filter the
ticket list rather than scrolling through everything.

The part the guide calls out as the real judgment test is the status state
machine: the backend must be the source of truth for which transitions are
legal, not the UI. If someone tries to jump straight from Open to Closed, or
reopen a Closed ticket, the backend has to reject it — the UI is just there
to reflect that clearly, not to enforce it.

In Drupal terms, this maps to a custom content entity (Ticket) with fields
for title, description, priority, status, assignee, and audit fields, a
Comment-module integration for ticket comments, Views for listing/searching,
and a validation layer (entity constraint or presave hook) that owns the
transition rules.

## Functional Requirements

- Users must authenticate (Drupal default login) before accessing tickets
- Create a ticket with title (required), description (required), priority
  (required, enum: Low/Medium/High), and assignee (optional)
- New tickets are always created with status **Open** — status is not a
  writable field on the create form; any other value submitted on create is
  ignored/rejected
- List all tickets — default sort newest first; empty state shown when none
  exist
- View a single ticket's detail, including its comments and reporter
  (creator)
- Update ticket title, description, priority, and assignee while the ticket
  is **not** in a terminal state (Resolved/Closed/Cancelled); once terminal,
  non-status fields are read-only (see Assumptions — treated as an archive,
  not further editable)
- Change ticket status only via a valid transition:
  - Open → In Progress
  - In Progress → Resolved
  - Resolved → Closed
  - Open → Cancelled
  - In Progress → Cancelled
  - Saving a ticket without changing its status (e.g. editing only the
    description) is **not** a transition and must not be rejected as one
  - All transitions not in the list above are rejected, including from any
    terminal state (Resolved, Closed, Cancelled)
- Add a comment to a ticket (including on Closed/Cancelled — see
  Clarifications)
- Keyword search scoped to ticket **title and description only** (not
  comments, not assignee name) — core Views CONTAINS-style matching, not
  ranked full-text search
- Filter ticket list by status (the only filter dimension in Core; priority/
  assignee filters are explicitly out of scope for Core, candidates for
  Stretch)
- Access control: which users can view/create/update/transition tickets is
  a functional requirement, not just an implementation detail — see
  Assumptions for the Core-scope answer
- Data persists across restarts (no in-memory-only state)
- Required fields validated server-side; invalid input rejected
- UI surfaces meaningful errors (e.g. invalid transition, missing field) and
  meaningful success confirmation (e.g. status updated message)
- Delete is explicitly **out of scope** for Core — ticket lifecycle ends at
  Closed/Cancelled, not deletion

## Non-Functional Requirements

- Backend is the enforcement point for business rules (status transitions,
  required fields) — not just client-side validation
- No secrets (DB credentials, API keys) committed to the repository
- Code follows Drupal coding standards or is explicitly documented where it
  deviates, with reasoning
- State-machine logic is covered by integration tests (Kernel or Functional)
- Setup is reproducible from the README alone (fresh clone → working app)

## Assumptions

- "User" is satisfied by Drupal's core User entity plus a role for internal
  staff; no custom user-management UI is required, per the guide
- **Access/visibility (Core scope):** all authenticated staff users can view
  all tickets and add comments; only the assignee or an admin-role user can
  change a ticket's status. This is a deliberate simplification for Core —
  a finer-grained per-ticket ACL is a Stretch candidate, not required here
- **Terminal-state edits:** once a ticket is Resolved, Closed, or Cancelled,
  non-status fields (title, description, priority, assignee) become
  read-only. Chosen over "always editable" to keep the archive meaningful
  once a ticket is done — documented here as a product decision, not
  discovered as a bug later
- **Transition enforcement mechanism:** an entity constraint validator
  (not a presave-hook-only approach), so the rule is enforced through
  Drupal's validation API regardless of entry point (form, REST, Drush) —
  chosen specifically because presave-only can be bypassed by code paths
  that skip form validation
- **Transition validation compares against persisted previous status**
  (`$entity->original` / `loadUnchanged()`) rather than form state, so a
  bundled field+status update can't smuggle in stale or spoofed status data
- **Concurrent status updates (race condition):** accepted risk for Core.
  Drupal's default entity save is last-write-wins; building explicit
  locking (`\Drupal::lock()`) or a check-and-set pattern is disproportionate
  for this assessment's scope and is called out as a Stretch candidate
  instead of implemented — see test-strategy.md "Tests Not Covered"
- A single Drupal instance serves as both backend and frontend (no separate
  SPA); this counts as a "full-stack combination" per the guide's allowed
  stacks
- Comments use Drupal's core Comment module attached to the Ticket bundle,
  rather than a fully custom Comment entity — documented as an architectural
  decision in design-notes.md
- Authentication beyond Drupal's default login is not required for Core
  (per the guide, auth is optional and only counts toward Stretch) — but
  users must still be logged in to reach ticket routes at all
- Database choice (MySQL/MariaDB vs SQLite) will be finalized in
  design-notes.md / data-model.md before implementation begins; Kernel
  tests will use SQLite regardless (Drupal's PHPUnit default), independent
  of the production DB choice

## Clarifications (questions for a product owner)

Higher-priority questions the critique surfaced (answered here as
documented assumptions rather than left open, since there's no real
product owner to ask):

- **Who can view which tickets?** → Answered in Assumptions: all staff can
  view all tickets for Core (simpler than per-user visibility rules)
- **Who can perform which transitions?** → Answered in Assumptions:
  assignee or admin role only, not any authenticated user
- **Is assignee required on create?** → No — optional; a ticket can start
  unassigned and be claimed later

Remaining open questions:
- Is there a maximum ticket description length, or any expected file/image
  attachment support (out of scope per guide, but worth confirming)?
- Should Cancelled tickets be excluded from default list views, or shown
  with a distinct visual state?
- Is there a required audit trail for status changes specifically (a log
  entity or auto-generated comment per transition), or is relying on
  Drupal's built-in `changed`/`uid` metadata on the entity sufficient?
- Should comments be editable/deletable after posting, or append-only?
- Are status-change notes required as a comment, or can a transition happen
  silently with no accompanying note?

## Edge Cases

- Attempting a transition not in the allowed set (e.g. Resolved → In
  Progress, Closed → anything, In Progress → Open) must be rejected with a
  clear error
- Saving a ticket with an unchanged status (e.g. only editing description)
  must succeed and must NOT be treated as an invalid transition
- Submitting a status value on ticket **create** other than Open must be
  ignored or rejected — new tickets are always Open
- Editing a non-status field on a Resolved/Closed/Cancelled ticket must be
  rejected (terminal state = read-only for those fields, per Assumptions)
- Attempting to transition a ticket that doesn't exist / was deleted
  concurrently
- Two users updating the same ticket's status at the same time (race
  condition) — accepted risk for Core, see Assumptions; not mitigated with
  locking in this scope
- A "Resolve"/"Cancel" quick-action (if built as a separate control from
  the edit form) must go through the same validator as the edit form —
  no separate/duplicate transition logic path
- Creating a ticket with a missing required field (title, description,
  priority)
- Searching/filtering with no matching results — empty state, not an error
- Adding a comment to a Closed or Cancelled ticket — decide and document
  whether this is allowed
- Very long keyword search strings or special characters (`%`, `_`) in
  search input — Views' CONTAINS operator use may need explicit escaping
  depending on the query plugin
- Assigning a ticket to a user that doesn't exist, or one that is blocked
  (`status = 0`), not just deleted
