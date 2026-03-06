@echo off
echo Starting Bright Steps Payment API on port 8003...
pip install -r requirements.txt
uvicorn app:app --reload --port 8003
pause
