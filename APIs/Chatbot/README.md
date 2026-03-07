# Bright Steps – AI Chatbot Assistant

## Overview
Parenting assistant chatbot with a built-in knowledge base covering child development topics. **No external API keys required** — uses keyword matching against a local knowledge base of expert-curated information.

## Setup
1. Install dependencies: `pip install -r requirements.txt`
2. Run: `run_server.bat` or `uvicorn app:app --reload --port 8015`

## Endpoints

| Method | Route | Description |
|--------|-------|-------------|
| POST | `/chat` | Send a message, get a response |
| GET | `/topics` | List available topics |

## Topics Covered
🏃 Motor Skills · 🗣️ Speech & Language · 🧠 Cognitive · 🍼 Feeding & Nutrition · 😴 Sleep · 📊 Growth · ❤️ Social-Emotional · 🚽 Self-Care · 🛡️ Safety · 📅 Appointments · 🌟 Platform Guide

## Usage Example
```bash
curl -X POST http://localhost:8015/chat -H "Content-Type: application/json" -d '{"message": "When should my baby start walking?", "child_age_months": 10}'
```

## Swagger Docs
Visit `http://localhost:8015/docs` for interactive API documentation.
