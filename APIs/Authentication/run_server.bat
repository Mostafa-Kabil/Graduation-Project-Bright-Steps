@echo off
echo Starting Bright Steps Authentication API on port 8004...
pip install -r requirements.txt
uvicorn app:app --reload --port 8004
pause
