# Implementation Plan

## Overview

_Brief description of the build order and overall approach for the
Drupal-based Support Ticket Management System._

## Task Breakdown

1. Scaffold custom module (`ticket_management`)
2. Define Ticket content entity + base fields
3. Wire up Comment module to Ticket bundle
4. Build create/edit forms
5. Build Views for list, search, and status filter
6. Implement status transition validator (server-side enforcement)
7. Seed data (Drush script or hook_install)
8. Write Kernel tests for the state machine
9. README + setup-notes.md
10. Debugging pass, code review pass, docs cleanup

## Milestones

- **M1:** Entity + forms working, manual create/list/view/update functional
- **M2:** State machine enforced + tested
- **M3:** Search/filter + comments functional
- **M4:** Full lifecycle docs complete, tests passing, README verified from fresh clone

## AI Usage Plan

- Plan Mode for feature-level planning before each milestone
- Full generation for entity/form/Views boilerplate
- Manual review + correction for the transition validator (highest-risk logic)
- AI-assisted test generation, manually verified against the 5 valid + rejected-invalid cases

## Risks

- Drupal's entity API has a learning curve if not encountered by the reviewer — need to make design-notes.md explain *why* choices were made, not just *what*
- Time pressure could tempt skipping lifecycle docs in favor of more code — guide explicitly warns against this

## Mitigation

- Time-box Core build to ~10 hours, reserve remaining week for docs/tests/reflection
- Write design-notes.md incrementally, not all at the end
