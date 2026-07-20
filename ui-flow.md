# UI Flow

1. **Ticket List** (`/tickets`) → keyword search box (title/description) +
   status filter dropdown, list showing title, status, priority, assignee,
   updated date. Only shows tickets the current user can view.
2. **Create Ticket** (`/ticket/add`) → title, description, priority,
   assignee (optional) → submit → redirect to detail view. No status field
   here, always starts Open.
3. **Ticket Detail** (`/ticket/{id}`) → all fields, current status,
   comment thread, Edit link (if user has field-edit access), inline
   comment form.
4. **Edit Ticket** (`/ticket/{id}/edit`) — one form, not two:
   - If ticket isn't edit-locked and user has `edit ticket fields`:
     title/description/priority/assignee are editable
   - If ticket **is** edit-locked (resolved/closed/cancelled): those same
     fields remain **visible with their current values, but disabled**
     (not hidden) — user can see what the ticket says without needing to
     view a separate read-only page
   - If user is the assignee (or admin) and status isn't closed/cancelled:
     status dropdown appears too, limited to legal next-states only
   - A user with only one of those permissions sees only their half of
     the form — see api-contract.md for the full matrix
   - If neither non-status-edit nor status-transition access applies at
     all (e.g. anyone on a `closed` ticket), the edit route denies access
     entirely — there's nothing left to show
5. **Add Comment** → inline form on detail view, appends to thread.
   Available even when the ticket is Closed/Cancelled — comments aren't
   locked the way other fields are.

## Error / Empty States
- No tickets match search/filter → empty state message, not blank page
- Invalid transition attempted (e.g. via direct request, bypassing the
  dropdown) → specific rejection naming the illegal from→to pair, not a
  generic error
- Missing required field on submit → inline form error
- Attempting to submit a change to a disabled/locked field (e.g. via
  browser devtools re-enabling it, or a direct API/Drush write) → rejected
  by the save-time edit-lock constraint, not silently accepted

