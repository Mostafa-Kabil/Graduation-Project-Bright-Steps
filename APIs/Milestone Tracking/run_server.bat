@echo off
echo Starting Bright Steps Milestone Tracking API on port 8013...
pip install -r requirements.txt
uvicorn app:app --reload --port 8013
pause
