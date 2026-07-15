# API Contract

_Note: if the implementation is UI/form-driven rather than exposing REST/
JSON:API endpoints, document that decision here and describe the route
contract instead (routes, form IDs, expected inputs/outputs) — or expose
Ticket/Comment via JSON:API for a cleaner contract (also counts toward
Stretch)._

## Endpoint: Create Ticket
Method: POST
Path: /ticket (or JSON:API: /jsonapi/ticket/ticket)
Purpose: Create a new support ticket

### Request
```
{
  "title": "string, required",
  "description": "string, required",
  "priority": "string, enum",
  "assignedTo": "user id, optional"
}
```

### Response
```
{
  "id": "int",
  "status": "Open",
  "createdAt": "timestamp"
}
```

### Validation Rules
- title, description required
- priority must be a valid enum value
- assignedTo, if provided, must reference an existing User

### Error Responses
- 400: missing required field
- 422: invalid priority/assignee reference

---

_(Repeat this block per endpoint: List Tickets, Get Ticket Detail, Update
Ticket, Change Status, Add Comment, Search/Filter Tickets.)_
