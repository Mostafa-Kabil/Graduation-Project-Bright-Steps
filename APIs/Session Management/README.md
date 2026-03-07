# Bright Steps – Session Management API

## Overview
Active session tracking, device management, and JWT token revocation. Enables users to view and revoke their active sessions ("logout everywhere").

## Setup
1. Import `grad.sql` into phpMyAdmin (includes all tables)
2. Install dependencies: `pip install -r requirements.txt`
3. Run: `run_server.bat` or `uvicorn app:app --reload --port 8011`

## Endpoints

| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| POST | `/sessions` | — | Create session (after login) |
| GET | `/sessions/{user_id}` | Bearer | List active sessions |
| DELETE | `/sessions/{session_id}` | Bearer | Revoke a session |
| DELETE | `/sessions/user/{user_id}/all` | Bearer | Logout everywhere |
| POST | `/blacklist` | — | Blacklist a JWT token |
| GET | `/blacklist/check/{jti}` | — | Check if token is blacklisted |
| PUT | `/sessions/{session_id}/heartbeat` | — | Update last active time |
| GET | `/sessions/{user_id}/count` | — | Active session count |

## Integration
After login, the Auth API should call this API to create a session:
```python
requests.post("http://localhost:8011/sessions", json={
    "user_id": user_id, "token_jti": jti,
    "ip_address": client_ip, "user_agent": ua
})
```

## Swagger Docs
Visit `http://localhost:8011/docs` for interactive API documentation.
