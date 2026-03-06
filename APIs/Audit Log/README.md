# Bright Steps – Audit Log API

## Overview
Immutable (insert-only) audit logging for all sensitive actions. Supports compliance monitoring, suspicious activity detection, and admin dashboards.

## Setup
1. Import `grad.sql` into phpMyAdmin (includes all tables)
2. Install dependencies: `pip install -r requirements.txt`
3. Run: `run_server.bat` or `uvicorn app:app --reload --port 8010`

## Endpoints

| Method | Route | Description |
|--------|-------|-------------|
| POST | `/log` | Record an action |
| POST | `/log/bulk` | Record multiple actions |
| GET | `/logs` | Query logs (filter by user, action, date, resource) |
| GET | `/logs/user/{user_id}` | All logs for a user |
| GET | `/logs/action/{action}` | All logs of an action type |
| GET | `/stats` | Statistics & suspicious activity |
| GET | `/actions` | List valid action types |

## Valid Actions
`login`, `logout`, `register`, `password_change`, `password_reset`, `profile_update`, `child_add`, `child_update`, `child_delete`, `growth_record_add`, `payment`, `appointment_book`, `appointment_cancel`, `data_export`, `admin_action`, `session_revoke`, `ip_block`, `ip_unblock`, `milestone_achieve`, `badge_award`, `settings_change`

## Integration Example
```python
import requests
requests.post("http://localhost:8010/log", json={
    "user_id": 1,
    "action": "login",
    "resource": "users",
    "ip_address": "192.168.1.1",
    "details": {"method": "email_password"}
})
```

## Swagger Docs
Visit `http://localhost:8010/docs` for interactive API documentation.
