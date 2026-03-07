# Bright Steps – Milestone Tracking API

## Overview
Developmental milestone tracking with pre-seeded milestones across 5 categories. Parents can mark milestones as achieved and track progress with completion percentages.

## Setup
1. Import `grad.sql` into phpMyAdmin (includes all tables + seeds 48 milestones)
2. Install dependencies: `pip install -r requirements.txt`
3. Run: `run_server.bat` or `uvicorn app:app --reload --port 8013`

## Endpoints

| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| GET | `/milestones` | — | List all milestones (filter by category/age) |
| GET | `/milestones/category/{cat}` | — | Milestones by category |
| GET | `/milestones/child/{child_id}` | Bearer | Child's progress |
| POST | `/milestones/child/{child_id}/{milestone_id}` | Bearer | Mark achieved |
| DELETE | `/milestones/child/{child_id}/{milestone_id}` | Bearer | Remove achievement |
| GET | `/milestones/child/{child_id}/summary` | Bearer | Progress by category |
| GET | `/milestones/suggestions/{age_months}` | — | Suggestions for age |
| GET | `/categories` | — | List categories |

## Categories
🏃 Motor Skills · 🗣️ Language · 🧠 Cognitive · ❤️ Social-Emotional · 🪥 Self-Care

## Swagger Docs
Visit `http://localhost:8013/docs` for interactive API documentation.
