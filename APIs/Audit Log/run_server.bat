@echo off
echo Starting Bright Steps Audit Log API on port 8010...
pip install -r requirements.txt
uvicorn app:app --reload --port 8010
pause
