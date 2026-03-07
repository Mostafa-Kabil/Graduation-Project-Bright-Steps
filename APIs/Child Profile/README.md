# Bright Steps – Child Profile API

## Overview
CRUD API for managing child profiles and growth records. Requires JWT authentication. Uses existing `child` and `growth_record` database tables.

## Setup
1. Import `grad.sql` into phpMyAdmin (includes all tables)
2. Install dependencies: `pip install -r requirements.txt`
3. Run: `run_server.bat` or `uvicorn app:app --reload --port 8009`

## Endpoints

| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| POST | `/children` | Bearer | Add a child profile |
| GET | `/children/{parent_id}` | Bearer | List children for a parent |
| GET | `/child/{child_id}` | Bearer | Get child details + growth + points |
| PUT | `/child/{child_id}` | Bearer | Update child profile |
| DELETE | `/child/{child_id}` | Bearer | Delete child |
| POST | `/child/{child_id}/growth` | Bearer | Add growth measurement |
| GET | `/child/{child_id}/growth` | Bearer | Growth history |
| GET | `/child/{child_id}/growth/latest` | Bearer | Latest growth record |
| GET | `/child/{child_id}/age` | Bearer | Age in months/years |

## Token Usage
Include in request headers: `Authorization: Bearer <access_token>`

## Swagger Docs
Visit `http://localhost:8009/docs` for interactive API documentation.
