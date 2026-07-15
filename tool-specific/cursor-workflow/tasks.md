# Tasks

- [ ] Scaffold `ticket_management` module (.info.yml, .module, routing)
- [ ] Define Ticket content entity + base fields (data-model.md as source of truth)
- [ ] Attach Comment module to Ticket bundle
- [ ] Build create/edit forms
- [ ] Build Views: ticket list, keyword search, status filter
- [ ] Implement TicketStatusTransitionValidator (server-side enforcement)
- [ ] Wire validator into entity presave / constraint
- [ ] Seed data script (Drush command or hook_install)
- [ ] Kernel tests: all 5 valid transitions succeed
- [ ] Kernel tests: invalid transitions rejected
- [ ] Kernel tests: required-field validation
- [ ] README setup instructions, verified from fresh clone
- [ ] Debugging pass — log real issues in debugging-notes.md
- [ ] Self code-review pass via Cursor — log in code-review-notes.md
