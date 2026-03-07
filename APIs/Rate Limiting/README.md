# Bright Steps – Rate Limiting API

## Overview
Centralized rate limiter with sliding-window algorithm, IP blocking, and configurable per-endpoint limits. Other APIs call `/check` before processing requests.

## Setup
1. Import `grad.sql` into phpMyAdmin (includes all tables)
2. Install dependencies: `pip install -r requirements.txt`
3. Run: `run_server.bat` or `uvicorn app:app --reload --port 8008`

## Endpoints

| Method | Route | Description |
|--------|-------|-------------|
| POST | `/check` | Check if a request should be allowed |
| GET | `/status/{ip}` | Rate limit status for an IP |
| POST | `/block` | Block an IP address |
| DELETE | `/block/{ip}` | Unblock an IP address |
| GET | `/blocked` | List all blocked IPs |
| GET | `/limits` | View rate limit configuration |
| PUT | `/limits` | Update a rate limit (runtime) |
| GET | `/stats` | Rate limiting statistics |

## Default Limits
- `/login` — 5 requests/minute
- `/register` — 3 requests/minute
- `/forgot-password` — 3 requests/5 minutes
- Everything else — 100 requests/minute

## Integration Example
Other APIs should call this API before processing:
```python
import requests
result = requests.post("http://localhost:8008/check", json={"ip_address": client_ip, "endpoint": "/login"}).json()
if not result["allowed"]:
    raise HTTPException(status_code=429, detail="Too many requests")
```

## Swagger Docs
Visit `http://localhost:8008/docs` for interactive API documentation.
