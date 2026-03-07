# Bright Steps – Gamification API

## Overview
Badges, points, streaks, and leaderboard system. Uses existing `badge`, `child_badge`, `points_wallet`, `points_transaction`, and `points_refrence` tables, plus the new `streaks` table.

## Setup
1. Import `grad.sql` into phpMyAdmin (includes all tables)
2. Install dependencies: `pip install -r requirements.txt`
3. Run: `run_server.bat` or `uvicorn app:app --reload --port 8014`

## Endpoints

| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| GET | `/badges` | — | List all badges |
| POST | `/badges` | Bearer | Create a badge (admin) |
| GET | `/badges/child/{child_id}` | Bearer | Child's earned badges |
| POST | `/badges/award` | Bearer | Award a badge |
| GET | `/points/{child_id}` | Bearer | Point balance |
| GET | `/points/{child_id}/history` | Bearer | Transaction history |
| POST | `/points/award` | Bearer | Award points |
| GET | `/leaderboard` | — | Top children (anonymized) |
| GET | `/streaks/{child_id}` | Bearer | Tracking streaks |
| POST | `/streaks/update` | Bearer | Log streak activity |
| GET | `/dashboard/{child_id}` | Bearer | Full gamification dashboard |

## Streak Types
- 📊 `growth_tracking` — Log growth measurements daily
- ⭐ `milestone_logging` — Achieve milestones daily
- 🔥 `daily_login` — Login to the platform daily

## Swagger Docs
Visit `http://localhost:8014/docs` for interactive API documentation.
