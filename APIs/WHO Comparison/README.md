# Bright Steps – WHO Growth Comparison API

## Overview
Compares child growth measurements (weight, height, head circumference) against WHO Child Growth Standards for ages 0-60 months. Returns z-scores, percentiles, and traffic-light status indicators.

## Setup
1. Install dependencies: `pip install -r requirements.txt`
2. Run: `run_server.bat` or `uvicorn app:app --reload --port 8007`

## Endpoints

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/` | Health check |
| POST | `/compare` | Compare against WHO standards |
| GET | `/standards/{gender}/{age_months}` | Get WHO standard values |
| POST | `/percentile` | Calculate percentile rank |
| POST | `/growth-assessment` | Full assessment with recommendations |

## Traffic-Light System
- 🟢 **Green** — Normal range (z-score -1 to +1)
- 🟡 **Yellow** — Monitor (z-score -2 to -1 or +1 to +3)
- 🔴 **Red** — Needs attention (z-score beyond ±2)

## Example: Compare
```json
POST /compare
{
    "gender": "boy",
    "age_months": 12,
    "weight": 9.5,
    "height": 75.0,
    "head_circumference": 46.0
}
```

## Data Source
WHO Multicentre Growth Reference Study (MGRS)
https://www.who.int/tools/child-growth-standards

## Swagger Docs
Visit `http://localhost:8007/docs`
