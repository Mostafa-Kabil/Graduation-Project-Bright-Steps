# Bright Steps – Appointment Booking API

## Overview
Full appointment scheduling system with specialist listings, available time slots, booking, rescheduling, cancellation, and feedback/rating. Uses existing `appointment`, `specialist`, `clinic`, and `feedback` tables.

## Setup
1. Install dependencies: `pip install -r requirements.txt`
2. Run: `run_server.bat` or `uvicorn app:app --reload --port 8012`

## Endpoints

| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| GET | `/specialists` | — | List specialists (filter by clinic/specialization) |
| GET | `/specialists/{id}` | — | Specialist profile with rating |
| GET | `/specialists/{id}/slots` | — | Available time slots |
| POST | `/appointments` | Bearer | Book appointment |
| GET | `/appointments/parent/{id}` | Bearer | Parent's appointments |
| GET | `/appointments/specialist/{id}` | Bearer | Specialist's schedule |
| GET | `/appointments/{id}` | Bearer | Appointment details |
| PUT | `/appointments/{id}` | Bearer | Reschedule |
| PUT | `/appointments/{id}/cancel` | Bearer | Cancel appointment |
| PUT | `/appointments/{id}/complete` | Bearer | Mark completed |
| POST | `/appointments/{id}/feedback` | Bearer | Submit feedback (1-5 stars) |

## Time Slots
Available slots are auto-generated: 9:00 AM – 5:00 PM, 30-minute intervals, weekdays only.

## Swagger Docs
Visit `http://localhost:8012/docs` for interactive API documentation.
