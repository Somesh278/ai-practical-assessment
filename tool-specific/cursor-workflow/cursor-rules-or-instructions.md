# Cursor Rules / Instructions

_Intended to be used as `.cursorrules` or Cursor's project instructions._

- This is a Drupal project. Follow Drupal coding standards (drupal/coder),
  not generic PHP/Symfony conventions unless Drupal itself uses them.
- Status transition rules are defined ONLY in spec.md's state machine table.
  Never invent additional transitions or "helpful" auto-transitions.
- All validation (required fields, status transitions) must be enforced
  server-side. Do not rely on client-side/form-only validation as the
  source of truth.
- Never suggest committing real database credentials, API keys, or
  connection strings — use placeholders in examples.
- When generating tests, cover both the valid-transition and
  invalid-transition cases explicitly — don't only test the happy path.
- Prefer Drupal core APIs (Entity API, Comment module, Views, Form API)
  over introducing new dependencies unless justified in design-notes.md.
- When unsure about a requirement, flag it as a question rather than
  assuming — this project values explicit assumptions over silent guesses.
