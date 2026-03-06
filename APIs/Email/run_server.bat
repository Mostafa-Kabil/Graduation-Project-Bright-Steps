@echo off
echo Starting Bright Steps Email API on port 8006...
echo NOTE: Set SMTP_EMAIL and SMTP_PASSWORD environment variables before running.
pip install -r requirements.txt
uvicorn app:app --reload --port 8006
pause
