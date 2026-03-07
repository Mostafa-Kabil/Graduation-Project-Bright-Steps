@echo off
echo Starting Bright Steps Notifications API on port 8005...
pip install -r requirements.txt
uvicorn app:app --reload --port 8005
pause
