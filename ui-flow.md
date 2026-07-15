# UI Flow

1. **Ticket List** → keyword search box + status filter dropdown, table/list
   of tickets with title, status, priority, assignee
2. **Create Ticket** → form with title, description, priority, assignee →
   submit → redirect to detail view
3. **Ticket Detail** → all fields, current status, comment thread, status
   change control (only valid next-states shown/enabled), edit link
4. **Edit Ticket** → form pre-filled, same validation as create
5. **Status Change** → dropdown/buttons limited to valid transitions from
   current state; attempting an invalid one client-side shouldn't even be
   presented, but backend still enforces regardless
6. **Add Comment** → inline form on detail view, appends to thread

## Error / Empty States
- No tickets match search/filter → empty state message, not blank page
- Invalid transition attempted (e.g. via direct request) → clear rejection
  message
- Missing required field on submit → inline form error
