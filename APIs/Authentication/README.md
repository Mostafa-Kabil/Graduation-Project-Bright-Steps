# Bright Steps – Authentication API

## Overview
JWT-based authentication with register, login, token refresh, password management, and user profile endpoints.

## Setup
1. Install dependencies: `pip install -r requirements.txt`
2. Ensure `password_reset_tokens` table exists (run `../migration.sql`)
3. Run: `run_server.bat` or `uvicorn app:app --reload --port 8004`

## Endpoints

| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| GET | `/` | — | Health check |
| POST | `/register` | — | Create new account |
| POST | `/login` | — | Login, get tokens |
| POST | `/refresh` | — | Refresh access token |
| GET | `/me` | Bearer | Get user profile |
| POST | `/change-password` | Bearer | Change password |
| POST | `/forgot-password` | — | Request reset token |
| POST | `/reset-password` | — | Reset with token |

## Token Usage
Include in request headers: `Authorization: Bearer <access_token>`

## Swagger Docs
Visit `http://localhost:8004/docs` for interactive API documentation.
