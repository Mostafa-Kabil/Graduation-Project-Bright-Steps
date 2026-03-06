@echo off
echo Starting Bright Steps Rate Limiting API on port 8008...
pip install -r requirements.txt
uvicorn app:app --reload --port 8008
pause
