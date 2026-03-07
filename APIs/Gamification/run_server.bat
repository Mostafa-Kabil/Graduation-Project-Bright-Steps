@echo off
echo Starting Bright Steps Gamification API on port 8014...
pip install -r requirements.txt
uvicorn app:app --reload --port 8014
pause
