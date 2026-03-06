# Bright Steps – Notifications API

## Overview
In-app notification system with create, read, bulk-send, and mark-as-read functionality.

## Setup
1. Install dependencies: `pip install -r requirements.txt`
2. Run migration: `../migration.sql` (creates `notifications` table)
3. Run: `run_server.bat` or `uvicorn app:app --reload --port 8005`

## Endpoints

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/` | Health check |
| POST | `/notifications` | Create notification |
| POST | `/notifications/bulk` | Bulk send |
| GET | `/notifications/{user_id}` | Get user notifications |
| PUT | `/notifications/{id}/read` | Mark one as read |
| PUT | `/notifications/{user_id}/read-all` | Mark all as read |
| DELETE | `/notifications/{id}` | Delete notification |

## Notification Types
`appointment_reminder`, `payment_success`, `growth_alert`, `milestone`, `system`

## Swagger Docs
Visit `http://localhost:8005/docs`
